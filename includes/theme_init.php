<?php

// if (session_status() === PHP_SESSION_NONE) {
//   session_start();
// }

$theme = $_SESSION['site_theme'] ?? ($_COOKIE['site_theme'] ?? 'light');
$theme = ($theme === 'dark') ? 'dark' : 'light';

$_SESSION['site_theme'] = $theme;
?>
