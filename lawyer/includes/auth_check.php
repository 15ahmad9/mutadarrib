<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lawyer') {
    header("Location: ../auth/login.php");
    exit;
}
