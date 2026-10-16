<?php
/**
 * Global Header View
 * TechPulse IMS - Hardware & Gaming Logistics
 * Author: Ainur Maida Syahri
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';

// Pastikan halaman internal terlindungi jika $public_page tidak diset
if (!isset($public_page) || !$public_page) {
    require_login();
}

$current_user = current_user();
$page_title = isset($page_title) ? $page_title . ' — TechPulse IMS' : 'TechPulse — PC Hardware & Gaming Gear Logistics';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Favicon icon SVG -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
</head>
<body>
<?php if (!isset($public_page) || !$public_page): ?>
    <?php include __DIR__ . '/navbar.php'; ?>
<?php endif; ?>
<main class="container">
    <?php include __DIR__ . '/alert.php'; ?>
