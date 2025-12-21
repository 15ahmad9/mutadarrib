<?php
session_start();
require_once __DIR__ . "/config/db.php";

// صلاحيات: المدير أو موظف النقابة فقط
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'syndicate_admin'], true)) {
  http_response_code(403);
  die("Access denied.");
}

// مدخلات
$entity = $_GET['entity'] ?? '';      // trainee | lawyer
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc    = $_GET['doc'] ?? '';         // no_conviction | good_conduct
$disp   = $_GET['disp'] ?? 'attachment'; // inline | attachment

if (!in_array($entity, ['trainee','lawyer'], true) || $id <= 0) {
  http_response_code(400);
  die("Bad request.");
}

$docMap = [
  'no_conviction' => 'no_conviction_doc',
  'good_conduct'  => 'good_conduct_doc',
];

if (!isset($docMap[$doc])) {
  http_response_code(400);
  die("Invalid doc type.");
}

$column = $docMap[$doc];
$relPath = null;

// جلب المسار من قاعدة البيانات حسب النوع
if ($entity === 'trainee') {
  $stmt = $pdo->prepare("SELECT {$column} AS p FROM trainees WHERE trainee_id = ? LIMIT 1");
  $stmt->execute([$id]);
  $relPath = $stmt->fetchColumn();
} else { // lawyer
  $stmt = $pdo->prepare("SELECT {$column} AS p FROM lawyers WHERE lawyer_id = ? LIMIT 1");
  $stmt->execute([$id]);
  $relPath = $stmt->fetchColumn();
}

if (!$relPath) {
  http_response_code(404);
  die("File not found.");
}

// تأمين المسار: يجب أن يكون مسارًا نسبيًا داخل uploads فقط
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

// المسارات المطلقة
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

// تحقق أن الملف داخل uploads فعلاً
$uploadsBaseNorm = rtrim(str_replace('\\', '/', $uploadsBase), '/') . '/';
$absNorm = str_replace('\\', '/', $absPath);

if (!str_starts_with($absNorm, $uploadsBaseNorm)) {
  http_response_code(403);
  die("Forbidden path.");
}

// MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($absPath) ?: 'application/octet-stream';

// اسم تحميل
$filename = basename($absPath);

// العرض داخل المتصفح أو تنزيل
$disp = ($disp === 'inline') ? 'inline' : 'attachment';

// رؤوس الاستجابة
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absPath));
header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// إرسال الملف
readfile($absPath);
exit;
