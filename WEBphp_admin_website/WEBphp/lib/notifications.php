<?php

declare(strict_types=1);

require_once __DIR__ . '/analytics.php';

/**
 * Notification Center.
 *
 * Notifications are *derived* from live Firebase data rather than stored as their
 * own feed, so the list stays correct even when records change outside the admin.
 * Only read/dismissed state is persisted, under `admin_notifications/{uid}`.
 */

/** Confidence below this fraction flags a detection for manual review. */
const TG_LOW_CONFIDENCE = 0.60;

/** Notification kinds, in descending order of urgency. */
function tg_notification_kinds(): array
{
    return [
        'pending' => ['label' => 'Pending validation', 'badge' => 'badge-pending', 'priority' => 1],
        'low_confidence' => ['label' => 'Low confidence', 'badge' => 'badge-critical', 'priority' => 2],
        'reclassified' => ['label' => 'Reclassified', 'badge' => 'badge-reclassified', 'priority' => 3],
        'new_user' => ['label' => 'New account', 'badge' => 'badge-blue', 'priority' => 4],
        'unmapped' => ['label' => 'Missing location', 'badge' => 'badge-gray', 'priority' => 5],
    ];
}

function tg_notification_kind_label(string $kind): string
{
    return (string)(tg_notification_kinds()[$kind]['label'] ?? ucfirst(str_replace('_', ' ', $kind)));
}

function tg_notification_kind_badge(string $kind): string
{
    return (string)(tg_notification_kinds()[$kind]['badge'] ?? 'badge-gray');
}

/** Stable id so read-state survives regeneration. */
function tg_notification_id(string $kind, string $ref): string
{
    return $kind . ':' . substr(sha1($ref), 0, 16);
}

/**
 * Read/dismissed state for an admin.
 *
 * @return array{read:array<string,bool>,dismissed:array<string,bool>}
 */
function tg_notification_state(string $adminUid): array
{
    if ($adminUid === '') {
        return ['read' => [], 'dismissed' => []];
    }
    $raw = firebase_db_get('admin_notifications/' . $adminUid);
    $read = is_array($raw['read'] ?? null) ? $raw['read'] : [];
    $dismissed = is_array($raw['dismissed'] ?? null) ? $raw['dismissed'] : [];
    return [
        'read' => array_map(static fn ($v): bool => (bool)$v, $read),
        'dismissed' => array_map(static fn ($v): bool => (bool)$v, $dismissed),
    ];
}

/** Mark one notification read. */
function tg_notification_mark_read(string $adminUid, string $id): bool
{
    if ($adminUid === '' || $id === '') {
        return false;
    }
    return firebase_db_patch('admin_notifications/' . $adminUid . '/read', [$id => true]);
}

/**
 * Mark many notifications read in a single request.
 *
 * @param list<string> $ids
 */
function tg_notification_mark_all_read(string $adminUid, array $ids): bool
{
    if ($adminUid === '' || $ids === []) {
        return false;
    }
    $payload = [];
    foreach ($ids as $id) {
        if ($id !== '') {
            $payload[$id] = true;
        }
    }
    return $payload !== [] && firebase_db_patch('admin_notifications/' . $adminUid . '/read', $payload);
}

/** Hide a notification permanently for this admin. */
function tg_notification_dismiss(string $adminUid, string $id): bool
{
    if ($adminUid === '' || $id === '') {
        return false;
    }
    return firebase_db_patch('admin_notifications/' . $adminUid . '/dismissed', [$id => true]);
}

/**
 * Build the notification feed from live data.
 *
 * @param list<array<string,mixed>> $records
 * @param array<string,array<string,mixed>> $usersByUid
 * @param array{read:array<string,bool>,dismissed:array<string,bool>} $state
 * @return list<array<string,mixed>>
 */
function tg_build_notifications(array $records, array $usersByUid, array $state): array
{
    $out = [];
    $read = $state['read'] ?? [];
    $dismissed = $state['dismissed'] ?? [];

    $push = static function (
        string $kind,
        string $ref,
        string $title,
        string $body,
        int $when,
        string $href
    ) use (&$out, $read, $dismissed): void {
        $id = tg_notification_id($kind, $ref);
        if (isset($dismissed[$id])) {
            return;
        }
        $out[] = [
            'id' => $id,
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
            'when' => $when,
            'href' => $href,
            'isRead' => isset($read[$id]),
            'priority' => (int)(tg_notification_kinds()[$kind]['priority'] ?? 9),
        ];
    };

    foreach ($records as $r) {
        $rid = (string)($r['id'] ?? '');
        $ruid = (string)($r['uid'] ?? '');
        if ($rid === '') {
            continue;
        }
        $when = (int)($r['createdAt'] ?? 0);
        $species = tg_record_species_label($r);
        $who = tg_user_display_name($ruid, $usersByUid);
        $status = tg_scan_review_status($r);
        $confidence = tg_record_confidence($r);
        $geo = tg_record_geo($r);

        if ($status === 'pending') {
            $push(
                'pending',
                $ruid . '/' . $rid,
                'Scan awaiting validation',
                sprintf('%s submitted %s in %s.', $who, $species, $geo['barangay']),
                $when,
                'scans.php?filter=pending'
            );
        }

        if ($confidence > 0 && $confidence < TG_LOW_CONFIDENCE && $status !== 'reclassified') {
            $push(
                'low_confidence',
                $ruid . '/' . $rid,
                'Low-confidence detection',
                sprintf('%s identified at %d%% confidence — verify the species.', $species, (int)round($confidence * 100)),
                $when,
                'scans.php?filter=pending'
            );
        }

        if ($status === 'reclassified') {
            $validatedAt = (int)($r['validatedAt'] ?? 0);
            $push(
                'reclassified',
                $ruid . '/' . $rid,
                'Detection reclassified',
                sprintf('%s was corrected to %s.', $who, $species),
                $validatedAt > 0 ? $validatedAt : $when,
                'scans.php?filter=reclassified'
            );
        }

        if (tg_record_lat_lng($r) === null) {
            $push(
                'unmapped',
                $ruid . '/' . $rid,
                'Record missing coordinates',
                sprintf('%s by %s has no usable GPS position.', $species, $who),
                $when,
                'trees.php'
            );
        }
    }

    $weekAgo = (time() - (7 * 86400)) * 1000;
    foreach ($usersByUid as $uid => $u) {
        if (!is_array($u)) {
            continue;
        }
        $created = (int)($u['createdAt'] ?? 0);
        if ($created <= 0 || $created < $weekAgo) {
            continue;
        }
        $push(
            'new_user',
            (string)$uid,
            'New account registered',
            sprintf('%s joined as %s.', (string)($u['fullName'] ?? 'A user'), tg_role_label((string)($u['role'] ?? 'user'))),
            $created,
            'users.php'
        );
    }

    // Unread first, then most urgent, then newest.
    usort($out, static function (array $a, array $b): int {
        return [$a['isRead'], $a['priority'], -$a['when']]
            <=> [$b['isRead'], $b['priority'], -$b['when']];
    });

    return $out;
}

/**
 * Unread count for the topbar bell.
 *
 * @param list<array<string,mixed>> $notifications
 */
function tg_notification_unread_count(array $notifications): int
{
    $n = 0;
    foreach ($notifications as $x) {
        if (empty($x['isRead'])) {
            $n++;
        }
    }
    return $n;
}

/**
 * Tally by kind, for the Notification Center summary tiles.
 *
 * @param list<array<string,mixed>> $notifications
 * @return array<string,int>
 */
function tg_notification_kind_counts(array $notifications): array
{
    $out = array_fill_keys(array_keys(tg_notification_kinds()), 0);
    foreach ($notifications as $x) {
        $k = (string)($x['kind'] ?? '');
        if (array_key_exists($k, $out)) {
            $out[$k]++;
        }
    }
    return $out;
}

/** Relative timestamp: "just now", "5 min ago", "3 days ago". */
function tg_time_ago(int $millis): string
{
    if ($millis <= 0) {
        return 'Unknown date';
    }
    $diff = time() - (int)floor($millis / 1000);
    if ($diff < 0) {
        return 'Just now';
    }
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int)floor($diff / 60);
        return $m . ' min ago';
    }
    if ($diff < 86400) {
        $hrs = (int)floor($diff / 3600);
        return $hrs . ($hrs === 1 ? ' hour ago' : ' hours ago');
    }
    if ($diff < 2592000) {
        $d = (int)floor($diff / 86400);
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }
    return date('M j, Y', (int)floor($millis / 1000));
}

