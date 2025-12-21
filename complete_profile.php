<?php
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';

// إذا كان المستخدم مكمل ملفه، لا داعي لفتح الصفحة
$chk = $pdo->prepare("SELECT profile_completed FROM users WHERE user_id=? LIMIT 1");
$chk->execute([$userId]);
if ((int)$chk->fetchColumn() === 1) {
  header("Location: /mutadarrib/index.php");
  exit;
}

$message = "";

/** إعدادات رفع الملفات */
$allowedExt = ['jpg','jpeg','png','pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB

function ensureDir($path) {
  if (!is_dir($path)) {
    mkdir($path, 0755, true);
  }
}

function uploadDoc($fieldName, $userId, $prefix, $allowedExt, $maxSize) {
  if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
    return [null, "الملف المطلوب غير مرفوع: $fieldName"];
  }

  $tmp  = $_FILES[$fieldName]['tmp_name'];
  $name = $_FILES[$fieldName]['name'];
  $size = (int)$_FILES[$fieldName]['size'];

  if ($size <= 0 || $size > $maxSize) {
    return [null, "حجم الملف غير مسموح (الحد الأقصى 5MB)."];
  }

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) {
    return [null, "نوع الملف غير مسموح. المسموح: PDF / JPG / PNG"];
  }

  // تحقق MIME (أقوى من الامتداد)
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($tmp);
  $allowedMime = [
    'application/pdf',
    'image/jpeg',
    'image/png'
  ];
  if (!in_array($mime, $allowedMime, true)) {
    return [null, "محتوى الملف غير مسموح (MIME غير صحيح)."];
  }

  $baseDir = __DIR__ . "/uploads/user_" . $userId . "/docs";
  ensureDir($baseDir);

  $safeName = $prefix . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(6)) . "." . $ext;
  $destAbs  = $baseDir . "/" . $safeName;

  if (!move_uploaded_file($tmp, $destAbs)) {
    return [null, "فشل حفظ الملف على السيرفر."];
  }

  // مسار نسبي للتخزين في DB
  $destRel = "uploads/user_" . $userId . "/docs/" . $safeName;

  return [$destRel, null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {

    // حسب الدور: trainee و lawyer مطلوب منهم رفع الوثائق
    $needsDocs = in_array($role, ['trainee','lawyer'], true);

    $noConvPath = null;
    $goodPath   = null;

    if ($needsDocs) {
      [$noConvPath, $err1] = uploadDoc('no_conviction_doc', $userId, 'no_conviction', $allowedExt, $maxSize);
      if ($err1) throw new Exception($err1);

      [$goodPath, $err2] = uploadDoc('good_conduct_doc', $userId, 'good_conduct', $allowedExt, $maxSize);
      if ($err2) throw new Exception($err2);
    }

    $pdo->beginTransaction();

    if ($role === 'trainee') {
      // تحديث trainees
      $t = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id=? LIMIT 1");
      $t->execute([$userId]);
      $traineeId = (int)$t->fetchColumn();
      if ($traineeId <= 0) throw new Exception("لم يتم العثور على سجل المتدرب.");

      $up = $pdo->prepare("
        UPDATE trainees
        SET no_conviction_doc = ?, good_conduct_doc = ?, updated_at = NOW()
        WHERE trainee_id = ?
      ");
      $up->execute([$noConvPath, $goodPath, $traineeId]);

    } elseif ($role === 'lawyer') {
      // تحديث lawyers
      $l = $pdo->prepare("SELECT lawyer_id FROM lawyers WHERE user_id=? LIMIT 1");
      $l->execute([$userId]);
      $lawyerId = (int)$l->fetchColumn();
      if ($lawyerId <= 0) throw new Exception("لم يتم العثور على سجل المحامي.");

      $up = $pdo->prepare("
        UPDATE lawyers
        SET no_conviction_doc = ?, good_conduct_doc = ?, updated_at = NOW()
        WHERE lawyer_id = ?
      ");
      $up->execute([$noConvPath, $goodPath, $lawyerId]);

    } else {
      // للأدوار الأخرى حالياً: اعتبر الإكمال “موافقة/حفظ” بدون وثائق إلزامية
      // ويمكنك لاحقاً إضافة حقول أخرى هنا.
    }

    // تحديث users.profile_completed
    $upUser = $pdo->prepare("
      UPDATE users
      SET profile_completed = 1, profile_completed_at = NOW()
      WHERE user_id = ?
    ");
    $upUser->execute([$userId]);

    $pdo->commit();

    header("Location: /mutadarrib/index.php");
    exit;

  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إكمال الملف الشخصي</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <style>
    .wrap { max-width: 750px; margin: 25px auto; background:#fff; border:1px solid #ddd; border-radius:10px; padding:16px; }
    label { display:block; margin-top:12px; font-weight:600; }
    input[type="file"], button { width:100%; padding:10px; margin-top:6px; }
    button { background:#0077b6; border:none; color:#fff; border-radius:8px; cursor:pointer; }
    .note { font-size:13px; color:#666; margin-top:6px; }
  </style>
</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<div class="wrap">
  <h2>إكمال الملف الشخصي</h2>
  <?= $message ?>

  <?php if (in_array($role, ['trainee','lawyer'], true)): ?>
    <p class="note">
      يجب رفع الوثائق التالية لإكمال الحساب: عدم المحكومية + حسن السيرة والسلوك.
      الصيغ المسموحة: PDF/JPG/PNG — الحجم الأقصى: 5MB لكل ملف.
    </p>

    <form method="POST" enctype="multipart/form-data">
      <label>وثيقة عدم المحكومية</label>
      <input type="file" name="no_conviction_doc" accept=".pdf,.jpg,.jpeg,.png" required>

      <label>وثيقة حسن السيرة والسلوك</label>
      <input type="file" name="good_conduct_doc" accept=".pdf,.jpg,.jpeg,.png" required>

      <button type="submit">حفظ وإكمال الحساب</button>
    </form>
  <?php else: ?>
    <p class="note">لا توجد وثائق إلزامية لهذا الدور حالياً. اضغط إكمال.</p>
    <form method="POST">
      <button type="submit">إكمال</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
