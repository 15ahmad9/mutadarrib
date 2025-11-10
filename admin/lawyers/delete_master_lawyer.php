<?php
session_start();
require_once("../../config/db.php");

// حماية الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM lawyers_master WHERE master_id = ?");
    $stmt->execute([$id]);
}

header("Location: master_lawyers.php");
exit;
?>
