<?php
session_start();

// تحقق من أن المستخدم مسجل دخول وله صلاحية admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>
