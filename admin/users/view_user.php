<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("المستخدم غير موجود");
}
?>
<html dir="rtl">
<head><meta charset="utf-8"><title>تفاصيل المستخدم</title></head>
<body>

<div class="container">
<h2>تفاصيل المستخدم</h2>

<p><strong>الاسم:</strong> <?= $user['full_name'] ?></p>
<p><strong>اسم المستخدم:</strong> <?= $user['username'] ?></p>
<p><strong>الدور:</strong> <?= $user['role'] ?></p>

<a href="users.php">العودة</a>
</div>

</body>
</html>
