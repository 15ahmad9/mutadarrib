<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: students_master.php"); exit; }

// جلب user_id المرتبط
$stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // سيتم الحذف تلقائيًا من جدول students بسبب CASCADE
    $stmt2 = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt2->execute([$user['user_id']]);
}

header("Location: students_master.php");
exit;
