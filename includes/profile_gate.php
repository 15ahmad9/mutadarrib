<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pdo)) require_once __DIR__ . "/../config/db.php";

if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];

  $stmt = $pdo->prepare("SELECT profile_completed FROM users WHERE user_id=? LIMIT 1");
  $stmt->execute([$uid]);
  $completed = (int)$stmt->fetchColumn();

  $current = $_SERVER['REQUEST_URI'] ?? '';

  // اسمح بصفحات معينة حتى لا يحدث Loop
  $allowed = [
    '/mutadarrib/complete_profile.php',
    '/mutadarrib/auth/logout.php',
    '/mutadarrib/auth/login.php'
  ];

  $isAllowed = false;
  foreach ($allowed as $a) {
    if (str_starts_with($current, $a)) { $isAllowed = true; break; }
  }

  if ($completed === 0 && !$isAllowed) {
    header("Location: /mutadarrib/complete_profile.php");
    exit;
  }
}
