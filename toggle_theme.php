<?php
require_once __DIR__ . '/includes/theme_init.php';

$current = $_SESSION['site_theme'] ?? 'light';
$new = ($current === 'dark') ? 'light' : 'dark';

$_SESSION['site_theme'] = $new;
// 180 days
setcookie('site_theme', $new, time() + 60*60*24*180, '/', '', false, true);

// Redirect safely
$redirect = $_GET['redirect'] ?? '';
if (is_string($redirect) && $redirect !== '') {
  // Allow only same-site relative paths
  if (strpos($redirect, '/') === 0 && strpos($redirect, '//') !== 0) {
    header('Location: ' . $redirect);
    exit;
  }
}

$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref) {
  header('Location: ' . $ref);
  exit;
}

header('Location: /mutadarrib/index.php');
exit;
?>
