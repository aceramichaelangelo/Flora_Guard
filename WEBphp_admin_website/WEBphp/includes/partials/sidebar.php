<?php
declare(strict_types=1);
/** @var string $navActive */
$icons = [
    'dashboard' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'trees' => '<img src="assets/img/leaf-icon.png" alt="Tree" style="width: 28px; height: 28px; margin: -5px; object-fit: contain;">',
    'map' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
    'scans' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
    'users' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'reports' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    'species' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'notifications' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
];
$items = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'trees' => ['label' => 'Tree Management', 'href' => 'trees.php'],
    'map' => ['label' => 'Map Monitoring', 'href' => 'map.php'],
    'scans' => ['label' => 'Scan Records', 'href' => 'scans.php'],
    'species' => ['label' => 'Species Guide', 'href' => 'species.php'],
    'users' => ['label' => 'User Management', 'href' => 'users.php'],
    'reports' => ['label' => 'Reports', 'href' => 'reports.php'],
    'notifications' => ['label' => 'Notifications', 'href' => 'notifications.php'],
];
/** Unread notification count, supplied by layout_start.php. */
$navUnread = isset($navUnread) ? (int)$navUnread : 0;
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="tg-logo tg-logo--sm" aria-hidden="true">
            <?php require __DIR__ . '/brand_logo_svg.php'; ?>
        </div>
        <div>
            <div class="sidebar-title"><?= h(APP_BRAND) ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($items as $key => $it) : ?>
            <a class="sidebar-link<?= $navActive === $key ? ' is-active' : '' ?>" href="<?= h($it['href']) ?>">
                <span class="sidebar-ico"><?= $icons[$key] ?? '' ?></span>
                <span><?= h($it['label']) ?></span>
                <?php if ($key === 'notifications' && $navUnread > 0) : ?>
                    <span class="sidebar-count" aria-label="<?= (int)$navUnread ?> unread notifications">
                        <?= $navUnread > 99 ? '99+' : (int)$navUnread ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <a class="sidebar-foot-link logout-link logout-action logout-action--sidebar" href="logout.php" onclick="openLogoutModal(event)">
            <span class="logout-action__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </span>
            <span>Sign out</span>
        </a>
    </div>
</aside>
