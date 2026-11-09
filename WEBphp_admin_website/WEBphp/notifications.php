<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/notifications.php';

tg_require_login();

$cu = tg_current_user();
$adminUid = (string)($cu['uid'] ?? '');

// POST-redirect-GET so a refresh never replays an action.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['notif_action'] ?? '');
    $id = (string)($_POST['notif_id'] ?? '');
    $filter = (string)($_POST['filter'] ?? 'all');
    $flash = '';

    if ($action === 'read' && $id !== '') {
        $flash = tg_notification_mark_read($adminUid, $id) ? 'read' : 'error';
    } elseif ($action === 'dismiss' && $id !== '') {
        $flash = tg_notification_dismiss($adminUid, $id) ? 'dismissed' : 'error';
    } elseif ($action === 'read_all') {
        $state = tg_notification_state($adminUid);
        $all = tg_build_notifications(tg_all_tree_records(), tg_all_users(), $state);
        $unread = [];
        foreach ($all as $x) {
            if (empty($x['isRead'])) {
                $unread[] = (string)$x['id'];
            }
        }
        if ($unread === []) {
            $flash = 'none';
        } else {
            $flash = tg_notification_mark_all_read($adminUid, $unread) ? 'read_all' : 'error';
        }
    }

    $qs = 'filter=' . rawurlencode($filter);
    if ($flash !== '') {
        $qs .= '&msg=' . rawurlencode($flash);
    }
    header('Location: notifications.php?' . $qs, true, 302);
    exit;
}

$records = tg_all_tree_records();
$users = tg_all_users();
$state = tg_notification_state($adminUid);
$all = tg_build_notifications($records, $users, $state);

$kindCounts = tg_notification_kind_counts($all);
$unreadCount = tg_notification_unread_count($all);

$filter = (string)($_GET['filter'] ?? 'all');
$validFilters = array_merge(['all', 'unread'], array_keys(tg_notification_kinds()));
if (!in_array($filter, $validFilters, true)) {
    $filter = 'all';
}

if ($filter === 'unread') {
    $visible = array_values(array_filter($all, static fn (array $x): bool => empty($x['isRead'])));
} elseif ($filter !== 'all') {
    $visible = array_values(array_filter($all, static fn (array $x): bool => $x['kind'] === $filter));
} else {
    $visible = $all;
}

$flashMsg = (string)($_GET['msg'] ?? '');

$pageTitle = 'Notification Center';
$navActive = 'notifications';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Notification Center</h1>
        <p>Alerts derived from live field activity &mdash; validations, low-confidence detections, and account changes.</p>
    </div>
    <?php if ($unreadCount > 0) : ?>
        <form method="post" action="notifications.php">
            <input type="hidden" name="notif_action" value="read_all">
            <input type="hidden" name="filter" value="<?= h($filter) ?>">
            <button type="submit" class="btn btn-primary btn-sm">
                &#10003; Mark all as read (<?= (int)$unreadCount ?>)
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($flashMsg === 'read') : ?>
    <div class="alert alert-success" style="margin-bottom:16px">Notification marked as read.</div>
<?php elseif ($flashMsg === 'read_all') : ?>
    <div class="alert alert-success" style="margin-bottom:16px">All notifications marked as read.</div>
<?php elseif ($flashMsg === 'dismissed') : ?>
    <div class="alert alert-success" style="margin-bottom:16px">Notification dismissed.</div>
<?php elseif ($flashMsg === 'none') : ?>
    <div class="alert alert-info" style="margin-bottom:16px">Nothing unread right now.</div>
<?php elseif ($flashMsg === 'error') : ?>
    <div class="alert alert-error" style="margin-bottom:16px">Action failed. Check the Firebase connection and database rules.</div>
<?php endif; ?>

<div class="grid-4" style="margin-bottom:14px">
    <div class="card stat-card">
        <div class="lbl">Unread</div>
        <div class="big"><?= (int)$unreadCount ?></div>
    </div>
    <div class="card stat-card pending">
        <div class="lbl">Awaiting validation</div>
        <div class="big"><?= (int)$kindCounts['pending'] ?></div>
    </div>
    <div class="card stat-card critical">
        <div class="lbl">Low confidence</div>
        <div class="big"><?= (int)$kindCounts['low_confidence'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Missing location</div>
        <div class="big"><?= (int)$kindCounts['unmapped'] ?></div>
    </div>
</div>

<div class="filter-pills">
    <a href="notifications.php?filter=all" class="<?= $filter === 'all' ? 'is-active' : '' ?>">
        All (<?= count($all) ?>)
    </a>
    <a href="notifications.php?filter=unread" class="<?= $filter === 'unread' ? 'is-active' : '' ?>">
        Unread (<?= (int)$unreadCount ?>)
    </a>
    <?php foreach (tg_notification_kinds() as $kindKey => $meta) : ?>
        <a href="notifications.php?filter=<?= h($kindKey) ?>" class="<?= $filter === $kindKey ? 'is-active' : '' ?>">
            <?= h((string)$meta['label']) ?> (<?= (int)$kindCounts[$kindKey] ?>)
        </a>
    <?php endforeach; ?>
</div>

<?php if (count($visible) === 0) : ?>
    <div class="card" style="text-align:center;padding:40px">
        <p style="margin:0;color:var(--muted)">
            <?= $filter === 'all'
                ? 'No notifications. Field activity will appear here as scans come in.'
                : 'Nothing matches this filter.' ?>
        </p>
    </div>
<?php else : ?>
    <ul class="notif-list">
        <?php foreach ($visible as $x) : ?>
            <?php
            $kind = (string)$x['kind'];
            $isRead = !empty($x['isRead']);
            ?>
            <li class="notif-item<?= $isRead ? ' is-read' : '' ?>">
                <span class="notif-item__rail" aria-hidden="true"></span>
                <div class="notif-item__body">
                    <div class="notif-item__head">
                        <span class="badge <?= h(tg_notification_kind_badge($kind)) ?>">
                            <?= h(tg_notification_kind_label($kind)) ?>
                        </span>
                        <span class="notif-item__time"><?= h(tg_time_ago((int)$x['when'])) ?></span>
                        <?php if (!$isRead) : ?>
                            <span class="notif-item__unread" title="Unread" aria-label="Unread"></span>
                        <?php endif; ?>
                    </div>
                    <p class="notif-item__title"><?= h((string)$x['title']) ?></p>
                    <p class="notif-item__text"><?= h((string)$x['body']) ?></p>
                </div>
                <div class="notif-item__actions">
                    <a class="btn btn-outline btn-sm" href="<?= h((string)$x['href']) ?>">Review</a>
                    <?php if (!$isRead) : ?>
                        <form method="post" action="notifications.php">
                            <input type="hidden" name="notif_action" value="read">
                            <input type="hidden" name="notif_id" value="<?= h((string)$x['id']) ?>">
                            <input type="hidden" name="filter" value="<?= h($filter) ?>">
                            <button type="submit" class="btn btn-outline btn-sm">Mark read</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="notifications.php">
                        <input type="hidden" name="notif_action" value="dismiss">
                        <input type="hidden" name="notif_id" value="<?= h((string)$x['id']) ?>">
                        <input type="hidden" name="filter" value="<?= h($filter) ?>">
                        <button type="submit" class="btn btn-danger-outline btn-sm" title="Dismiss permanently">Dismiss</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
require __DIR__ . '/includes/layout_end.php';

