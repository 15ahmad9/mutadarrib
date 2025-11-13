<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'];

$newpass = password_hash("123", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
$stmt->execute([$newpass, $id]);

echo "<script>alert('تمت إعادة تعيين كلمة المرور إلى: 123456'); window.location='users.php';</script>";
