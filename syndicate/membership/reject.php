<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'syndicate_admin') {
//   header("Location: /mutadarrib/auth/login.php");
//   exit;
//}

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
$reason    = trim($_POST['reason'] ?? '');

if (!$requestId) {
  die("Request ID غير صالح.");
}
if ($reason === '') {
  $reason = "لم يتم استيفاء المتطلبات";
}

try {
  $stmt = $pdo->prepare("
    UPDATE membership_requests
    SET
      status = 'rejected',
      reviewed_at = NOW(),
      reviewed_by = ?,
      rejection_reason = ?,
      approved_syndicate_id = NULL,
      syndicate_id = NULL
    WHERE request_id = ?
      AND status = 'pending'
  ");
  $stmt->execute([
    (int)$_SESSION['user_id'],
    $reason,
    $requestId
  ]);

  if ($stmt->rowCount() !== 1) {
    throw new Exception("لم يتم رفض الطلب (قد يكون غير موجود أو ليست حالته pending).");
  }

  header("Location: /mutadarrib/syndicate/membership/requests.php?status=rejected");
  exit;

} catch (Exception $e) {
  die("خطأ أثناء رفض الطلب: " . htmlspecialchars($e->getMessage()));
}
