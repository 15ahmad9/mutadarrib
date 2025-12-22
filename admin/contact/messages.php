<?php
$layout_header_path  = __DIR__ . "/../includes/header.php";
$layout_sidebar_path = __DIR__ . "/../includes/sidebar.php";

$allowed_roles = ['admin'];
$page_title    = "رسائل تواصل معنا | الإدارة";

require_once __DIR__ . "/../../shared/contact/messages_page.php";
