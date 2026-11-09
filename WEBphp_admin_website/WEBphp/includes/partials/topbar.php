<?php
declare(strict_types=1);
/** @var array{uid:string,email:string,fullName:string,role:string} $cu */
/** @var int $navUnread */
$navUnread = isset($navUnread) ? (int)$navUnread : 0;
?>
<header class="topbar">
    <div class="topbar-actions">
        <a
            href="notifications.php"
            class="icon-btn"
            title="<?= $navUnread > 0 ? h($navUnread . ' unread notifications') : 'Notifications' ?>"
            aria-label="<?= $navUnread > 0 ? h('Notifications, ' . $navUnread . ' unread') : 'Notifications' ?>"
        >
            <span class="bell">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </span>
            <?php if ($navUnread > 0) : ?>
                <span class="badge-count" aria-hidden="true"><?= $navUnread > 9 ? '9+' : (int)$navUnread ?></span>
            <?php endif; ?>
        </a>
        <div class="profile-dd" data-dd>
            <button type="button" class="user-pill user-pill--link" id="profileBtn" data-dd-btn aria-haspopup="menu" aria-expanded="false" title="Open profile menu">
                <div class="user-avatar" aria-hidden="true"><?= h($initials) ?></div>
                <div class="user-meta">
                    <div class="user-name"><?= h($cu['fullName'] ?: 'Admin User') ?></div>
                </div>
            </button>
            <div class="profile-menu" data-dd-menu role="menu" aria-label="Profile menu">
                <div class="profile-menu-head">
                    <div class="user-avatar user-avatar--sm" aria-hidden="true"><?= h($initials) ?></div>
                    <div>
                        <div class="user-name"><?= h($cu['fullName'] ?: 'Admin User') ?></div>
                        <div class="user-role"><?= h($cu['email'] ?? '') ?></div>
                    </div>
                </div>
                <div class="profile-menu-sep" role="separator"></div>
                <a class="profile-item logout-link logout-action logout-action--menu" role="menuitem" href="logout.php" onclick="openLogoutModal(event)">
                    <span class="logout-action__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    <span>Sign out</span>
                </a>
            </div>
        </div>
    </div>
</header>
