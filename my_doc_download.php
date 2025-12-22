<?php
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  die("Access denied.");
}

$userId = (int)$_SESSION['user_id'];

$doc  = $_GET['doc'] ?? ''; // no_conviction | good_conduct
$disp = $_GET['disp'] ?? 'attachment'; // inline | attachment

$map = [
  'no_conviction' => 'no_conviction_doc',
  'good_conduct'  => 'good_conduct_doc',
];

if (!isset($map[$doc])) {
  http_response_code(400);
  die("Invalid doc type.");
}

$column = $map[$doc];

// تحديد الجدول حسب الدور
$stmtRole = $pdo->prepare("SELECT role FROM users WHERE user_id=? LIMIT 1");
$stmtRole->execute([$userId]);
$role = $stmtRole->fetchColumn();

if (!in_array($role, ['trainee','lawyer'], true)) {
  http_response_code(403);
  die("Not allowed.");
}

if ($role === 'trainee') {
  $stmt = $pdo->prepare("SELECT {$column} FROM trainees WHERE user_id=? LIMIT 1");
  $stmt->execute([$userId]);
  $relPath = $stmt->fetchColumn();
} else {
  $stmt = $pdo->prepare("SELECT {$column} FROM lawyers WHERE user_id=? LIMIT 1");
  $stmt->execute([$userId]);
  $relPath = $stmt->fetchColumn();
}

if (!$relPath) {
  http_response_code(404);
  die("File not found.");
}

// تأمين المسار
$relPath = str_replace('\\', '/', trim($relPath));

if (
  str_starts_with($relPath, 'http://') ||
  str_starts_with($relPath, 'https://') ||
  str_contains($relPath, '..') ||
  str_starts_with($relPath, '/')
) {
  http_response_code(400);
  die("Invalid file path.");
}

$uploadsBase = realpath(__DIR__ . "/uploads");
if (!$uploadsBase) {
  http_response_code(500);
  die("Uploads directory not found.");
}

$absPath = realpath(__DIR__ . "/" . $relPath);
if (!$absPath || !file_exists($absPath)) {
  http_response_code(404);
  die("File not found on disk.");
}

// تحقق أن الملف داخل uploads
$uploadsBaseNorm = rtrim(str_replace('\\', '/', $uploadsBase), '/') . '/';
$absNorm = str_replace('\\', '/', $absPath);

if (!str_starts_with($absNorm, $uploadsBaseNorm)) {
  http_response_code(403);
  die("Forbidden path.");
}

// MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($absPath) ?: 'application/octet-stream';

$filename = basename($absPath);
$disp = ($disp === 'inline') ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absPath));
header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($absPath);
exit;
