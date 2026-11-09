<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Mobile Interface';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> · <?= h(APP_NAME) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo-mark-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo-mark-180.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap">
    <?php $assetV = (string)@filemtime(__DIR__ . '/assets/css/app.css'); ?>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= h($assetV) ?>">
</head>
<body class="login-page login-page--split">
<div class="login-split-card" style="max-width:980px">
    <div class="login-brand-panel">
        <div class="login-brand-inner">
            <div class="tg-logo" aria-hidden="true">
                <?php $brandLogoSize = 256; require __DIR__ . '/includes/partials/brand_logo_svg.php'; ?>
            </div>
            <h1 class="login-brand-title">Flora Guard</h1>
            <p class="login-brand-tagline">Mobile App</p>
            <p class="login-brand-desc">NATIVE FLORA DETECTION AND MANAGEMENT SYSTEM USING COMPUTER VISION AND GEOANALYTICS IN TALAINGOD, DAVAO DEL NORTE</p>
            <ul class="login-brand-features">
                <li>Two-species tree scanning (Anahaw &amp; Narra)</li>
                <li>Taxonomic hierarchy reference</li>
                <li>Map monitoring with Mapbox · Firebase sync</li>
            </ul>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="login-form-inner">
            <h2 class="login-form-heading">Download</h2>
            <p class="login-form-sub">Android APK (release)</p>

            <?php
            $apkRel = 'downloads/EcoTreeGuard.apk';
            $apkAbs = __DIR__ . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'EcoTreeGuard.apk';
            $exists = is_file($apkAbs);
            $sizeMb = $exists ? round(filesize($apkAbs) / 1024 / 1024, 1) : 0.0;
            ?>

            <?php if (!$exists) : ?>
                <div class="alert alert-error">APK not found. Please build the app and copy it to <code>WEBphp/downloads/</code>.</div>
            <?php else : ?>
                <a class="btn btn-login-wide" href="<?= h($apkRel) ?>" download>
                    Download APK <span class="btn-login-arrow" aria-hidden="true">↓</span>
                </a>
                <p class="login-foot">File: <code><?= h(basename($apkRel)) ?></code> · <?= h((string)$sizeMb) ?> MB</p>
            <?php endif; ?>

            <div class="divider-or">OR</div>
            <a class="btn btn-outline btn-outline--block" href="login.php">Back to Admin Login</a>
        </div>
    </div>
</div>
</body>
</html>

