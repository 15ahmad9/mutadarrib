<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
$stmt->execute([$id]);

header("Location: users.php");
exit;
?>
