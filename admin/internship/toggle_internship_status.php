<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /mutadarrib/auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$source = $_GET['source'] ?? 'specialization';
$status = $_GET['status'] ?? '';

if ($id <= 0 || !in_array($source, ['specialization', 'it'], true)) {
    die("طلب غير صالح.");
}

if ($source === 'it') {
    if ($status === 'open') {
        $dbStatus = 'published';
    } elseif ($status === 'closed') {
        $dbStatus = 'closed';
    } else {
        die("حالة غير صالحة.");
    }

    $stmt = $pdo->prepare("
        UPDATE it_internships
        SET status = ?
        WHERE internship_id = ?
    ");
    $stmt->execute([$dbStatus, $id]);

} else {
    if (!in_array($status, ['open', 'closed'], true)) {
        die("حالة غير صالحة.");
    }

    $stmt = $pdo->prepare("
        UPDATE specialization_internships
        SET status = ?
        WHERE internship_id = ?
    ");
    $stmt->execute([$status, $id]);
}

header("Location: internships.php");
exit;