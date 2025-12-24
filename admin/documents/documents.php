<?php
// /mutadarrib/admin/documents/documents.php

$layout_header_path = __DIR__ . "/../includes/header.php";
$layout_sidebar_path = __DIR__ . "/../includes/sidebar.php";

$allowed_roles = ['admin']; // أو ['admin','syndicate_admin'] إذا بدك
$page_title = "وثائق المستخدمين | الإدارة";

require_once __DIR__ . "/../../shared/documents/documents_page.php";
