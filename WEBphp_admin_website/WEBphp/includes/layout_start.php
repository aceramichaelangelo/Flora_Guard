<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $navActive */
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
if (!isset($navActive)) {
    $navActive = '';
}
if (!isset($extraScripts)) {
    $extraScripts = '';
}
$cu = tg_current_user();

/**
 * Unread notification count for the topbar bell and sidebar badge.
 * Computed once per request; notifications.php reuses its own fuller build.
 */
require_once dirname(__DIR__) . '/lib/notifications.php';
$navUnread = 0;
try {
    $navUnread = tg_notification_unread_count(tg_build_notifications(
        tg_all_tree_records(),
        tg_all_users(),
        tg_notification_state((string)($cu['uid'] ?? ''))
    ));
} catch (Throwable $e) {
    // A Firebase outage must not blank the whole page chrome.
    $navUnread = 0;
}

$initials = 'AD';
if (($cu['fullName'] ?? '') !== '') {
    $parts = preg_split('/\s+/', trim($cu['fullName']));
    if (is_array($parts) && count($parts) >= 2) {
        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = strtoupper(substr($cu['fullName'], 0, 2));
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> · <?= h(APP_BRAND) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo-mark-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo-mark-180.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap">
    <?php $assetV = (string)@filemtime(__DIR__ . '/../assets/css/app.css'); ?>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= h($assetV) ?>">
</head>
<body class="app-body">
<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">&#9776;</button>
<div class="app-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="app-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        <main class="app-content">
