<?php
$layout_header_path  = __DIR__ . "/../includes/header.php";
$layout_sidebar_path = __DIR__ . "/../includes/sidebar.php";

$allowed_roles = ['syndicate_admin','admin'];
$page_title    = "وثائق المستخدمين | النقابة";

require_once __DIR__ . "/../../shared/documents/documents_page.php";
