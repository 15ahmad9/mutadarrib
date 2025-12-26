<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'syndicate_admin') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$id  = (int)($_GET['id'] ?? 0);
$doc = $_GET['doc'] ?? '';

$allowed = ['identity_front','identity_back','no_conviction_doc','good_conduct_doc'];
if ($id <= 0 || !in_array($doc, $allowed, true)) die("طلب غير صالح.");

$stmt = $pdo->prepare("SELECT {$doc} AS path FROM membership_requests WHERE request_id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['path'])) die("الملف غير موجود.");

$relPath = $row['path']; // مثل /uploads/...
$absPath = realpath(__DIR__ . "/../.." . $relPath);
$baseDir = realpath(__DIR__ . "/../.." . "/uploads");

if (!$absPath || !$baseDir || strpos($absPath, $baseDir) !== 0 || !is_file($absPath)) {
  die("مسار غير مسموح.");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $absPath);
finfo_close($finfo);

header("Content-Type: " . $mime);
header("Content-Disposition: inline; filename=\"" . basename($absPath) . "\"");
header("Content-Length: " . filesize($absPath));

readfile($absPath);
exit;
