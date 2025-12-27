<?php
session_start();
require_once __DIR__ . "/../../includes/theme_init.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lawyer') {
    header("Location: ../auth/login.php");
    exit;
}
