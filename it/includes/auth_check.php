<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../../includes/theme_init.php";
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'IT_Provider') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

// helpers
// function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// redirect for theme toggle
$redirect_uri = ($_SERVER['REQUEST_URI'] ?? '/mutadarrib/index.php');

// user display name
$displayName = $_SESSION['full_name'] ?? 'IT Provider';

// company name (optional)
$companyName = null;
if (!empty($_SESSION['user_id'])) {
  $stmtP = $pdo->prepare("SELECT company_name FROM it_providers WHERE user_id = ? LIMIT 1");
  $stmtP->execute([(int)$_SESSION['user_id']]);
  $companyName = $stmtP->fetchColumn() ?: null;
}
