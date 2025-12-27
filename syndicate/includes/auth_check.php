<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
require_once __DIR__ . "/../../includes/theme_init.php";
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'syndicate_admin') {
    header("Location: ../auth/login.php");
    exit;
}
