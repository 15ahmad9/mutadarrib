<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
