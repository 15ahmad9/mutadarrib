<?php
session_start();
require_once("../../config/db.php");

// حماية الدخول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$lawyer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$v         = isset($_GET['v'])  ? (int)$_GET['v']  : -1;

if ($lawyer_id <= 0) {
    header("Location: lawyers.php?error=invalid_id");
    exit;
}

if (!in_array($v, [0, 1], true)) {
    header("Location: lawyers.php?error=invalid_value");
    exit;
}

// تأكد أن المحامي موجود
$chk = $pdo->prepare("SELECT lawyer_id FROM lawyers WHERE lawyer_id = ? LIMIT 1");
$chk->execute([$lawyer_id]);
$exists = $chk->fetchColumn();

if (!$exists) {
    header("Location: lawyers.php?error=not_found");
    exit;
}

// تنفيذ التحديث
$up = $pdo->prepare("
    UPDATE lawyers
    SET verified = ?, updated_at = NOW()
    WHERE lawyer_id = ?
");
$up->execute([$v, $lawyer_id]);

header("Location: lawyers.php?success=verify_updated");
exit;
