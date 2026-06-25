<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /mutadarrib/auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$source = $_GET['source'] ?? 'specialization';

if ($id <= 0 || !in_array($source, ['specialization', 'it'], true)) {
    die("طلب غير صالح.");
}

try {
    $pdo->beginTransaction();

    if ($source === 'it') {
        $stmt = $pdo->prepare("DELETE FROM it_internships WHERE internship_id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM specialization_applications WHERE internship_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM specialization_internships WHERE internship_id = ?");
        $stmt->execute([$id]);
    }

    $pdo->commit();

    header("Location: internships.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("حدث خطأ أثناء الحذف: " . htmlspecialchars($e->getMessage()));
}