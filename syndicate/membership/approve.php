<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'syndicate_admin') {
//   header("Location: /mutadarrib/auth/login.php");
//   exit;
// }

// السماح للنقابة + الأدمن
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['syndicate_admin','admin'], true)) {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /mutadarrib/syndicate/membership/requests.php");
  exit;
}

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$requestId) {
  die("Request ID غير صالح.");
}

try {
  $pdo->beginTransaction();

  // 1) جلب الطلب وإقفاله لمنع القبول المكرر
  $stmt = $pdo->prepare("
    SELECT *
    FROM membership_requests
    WHERE request_id = ?
    FOR UPDATE
  ");
  $stmt->execute([$requestId]);
  $req = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$req) {
    throw new Exception("الطلب غير موجود.");
  }

  if ($req['status'] !== 'pending') {
    throw new Exception("لا يمكن قبول الطلب لأن حالته الحالية: " . $req['status']);
  }

  // تحقق أساسي من وجود ملفات الهوية في الطلب
  if (empty($req['identity_front']) || empty($req['identity_back'])) {
    throw new Exception("ملفات الهوية غير مكتملة في الطلب (أمامي/خلفي).");
  }

  // 2) تجهيز قيم الإدخال في lawyers_syndicate
  // lawyer_name في جدول النقابة NOT NULL، لذلك نضمن قيمة دائمًا حتى لو كان مقدم الطلب متدرب
  $lawyerName = trim((string)($req['lawyer_name'] ?? ''));
  if ($lawyerName === '') {
    $lawyerName = trim((string)($req['full_name'] ?? ''));
  }
  if ($lawyerName === '') {
    $lawyerName = "بدون اسم";
  }

  $nationalId = trim((string)$req['national_id']);
  if ($nationalId === '') {
    throw new Exception("الرقم الوطني مفقود في الطلب.");
  }

  // 3) هل يوجد سجل في النقابة مسبقًا بنفس الرقم الوطني؟
  $chk = $pdo->prepare("
    SELECT syndicate_id
    FROM lawyers_syndicate
    WHERE national_id = ?
    LIMIT 1
  ");
  $chk->execute([$nationalId]);
  $existing = $chk->fetch(PDO::FETCH_ASSOC);

  if ($existing) {
    // تحديث السجل الموجود
    // ملاحظة: إذا لم تضف role احذف هذا الحقل من التحديث
    $upd = $pdo->prepare("
      UPDATE lawyers_syndicate SET
        lawyer_name = ?,
        office_address = ?,
        phone = ?,
        email = ?,
        notes = ?,

        full_name = ?,
        first_name = ?,
        father_name = ?,
        grandfather_name = ?,
        family_name = ?,

        highschool_certificate = ?,
        university_degree = ?,

        no_conviction_doc = ?,
        good_conduct_doc = ?,

        social_security = ?,
        social_security_number = ?,

        identity_front = ?,
        identity_back  = ?,

        role = ?
      WHERE syndicate_id = ?
    ");

    $upd->execute([
      $lawyerName,
      $req['office_address'],
      $req['phone'],
      $req['email'],
      $req['notes'],

      $req['full_name'],
      $req['first_name'],
      $req['father_name'],
      $req['grandfather_name'],
      $req['family_name'],

      $req['highschool_certificate'] ?? 'لا',
      $req['university_degree'],

      $req['no_conviction_doc'],
      $req['good_conduct_doc'],

      $req['social_security'] ?? 'لا',
      $req['social_security_number'],

      $req['identity_front'],
      $req['identity_back'],

      $req['role'] ?? 'lawyer',
      (int)$existing['syndicate_id']
    ]);

    $syndicateId = (int)$existing['syndicate_id'];

  } else {
    // إدخال سجل جديد في النقابة
    // ملاحظة: إذا لم تضف role احذف هذا الحقل من INSERT
    $ins = $pdo->prepare("
      INSERT INTO lawyers_syndicate
        (lawyer_name, national_id, office_address, phone, email, notes,
         full_name, first_name, father_name, grandfather_name, family_name,
         highschool_certificate, university_degree,
         no_conviction_doc, good_conduct_doc,
         social_security, social_security_number,
         identity_front, identity_back,
         role,
         created_at)
      VALUES
        (?, ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, ?,
         ?, ?,
         ?, ?,
         ?, ?,
         ?,
         NOW())
    ");

    $ins->execute([
      $lawyerName,
      $nationalId,
      $req['office_address'],
      $req['phone'],
      $req['email'],
      $req['notes'],

      $req['full_name'],
      $req['first_name'],
      $req['father_name'],
      $req['grandfather_name'],
      $req['family_name'],

      $req['highschool_certificate'] ?? 'لا',
      $req['university_degree'],

      $req['no_conviction_doc'],
      $req['good_conduct_doc'],

      $req['social_security'] ?? 'لا',
      $req['social_security_number'],

      $req['identity_front'],
      $req['identity_back'],

      $req['role'] ?? 'lawyer'
    ]);

    $syndicateId = (int)$pdo->lastInsertId();
    if ($syndicateId <= 0) {
      throw new Exception("فشل استخراج رقم السجل (syndicate_id). تأكد أن syndicate_id في lawyers_syndicate AUTO_INCREMENT.");
    }
  }

  // 4) تحديث حالة الطلب إلى approved وربط رقم السجل الناتج
  $updReq = $pdo->prepare("
    UPDATE membership_requests
    SET
      status = 'approved',
      reviewed_at = NOW(),
      reviewed_by = ?,
      approved_syndicate_id = ?,
      syndicate_id = ?,
      rejection_reason = NULL
    WHERE request_id = ?
      AND status = 'pending'
  ");
  $updReq->execute([
    (int)$_SESSION['user_id'],
    $syndicateId,
    $syndicateId,
    $requestId
  ]);

  if ($updReq->rowCount() !== 1) {
    throw new Exception("لم يتم تحديث حالة الطلب (قد تكون تغيّرت قبل التنفيذ).");
  }

  $pdo->commit();

  header("Location: /mutadarrib/syndicate/membership/requests.php?status=approved");
  exit;

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  die("خطأ أثناء قبول الطلب: " . htmlspecialchars($e->getMessage()));
}
