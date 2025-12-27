<?php
session_start();
require_once __DIR__ . "/../../includes/theme_init.php";

// تحقق من أن المستخدم مسجل دخول وله صلاحية admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>
