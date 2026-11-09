<?php
declare(strict_types=1);

require_once __DIR__ . '/firebase_client.php';

/**
 * @return list<array<string, mixed>>
 */
function tg_all_tree_records(): array
{
    // Memoized: several partials and pages need this within one request.
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $allRecordsByUser = firebase_db_get('tree_records');
    $result = [];
    if (!is_array($allRecordsByUser)) {
        return $result;
    }
    foreach ($allRecordsByUser as $uid => $records) {
        if (!is_array($records)) {
            continue;
        }
        foreach ($records as $recordId => $record) {
            if (!is_array($record)) {
                continue;
            }
            $record['id'] = (string)$recordId;
            $record['uid'] = (string)($record['uid'] ?? $uid);
            $result[] = $record;
        }
    }
    usort($result, static function ($a, $b) {
        return ((int)($b['createdAt'] ?? 0)) <=> ((int)($a['createdAt'] ?? 0));
    });
    $cache = $result;
    return $result;
}

/**
 * @return array<string, array<string, mixed>>
 */
function tg_all_users(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $users = firebase_db_get('users');
    $cache = is_array($users) ? $users : [];
    return $cache;
}

function tg_status_bucket(string $status): string
{
    $s = strtolower(trim($status));
    return match (true) {
        in_array($s, ['critically_endangered', 'critical', 'critically'], true) => 'critical',
        $s === 'endangered' => 'endangered',
        in_array($s, ['vulnerable', 'protected'], true) => 'vulnerable',
        default => 'vulnerable',
    };
}

function tg_status_label(string $bucket): string
{
    return match ($bucket) {
        'critical' => 'Critically Endangered',
        'endangered' => 'Endangered',
        default => 'Vulnerable',
    };
}

/**
 * @param list<array<string, mixed>> $records
 * @return array{total:int,critical:int,endangered:int,vulnerable:int}
 */
function tg_conservation_counts(array $records): array
{
    $critical = $endangered = $vulnerable = 0;
    foreach ($records as $r) {
        $b = tg_status_bucket((string)($r['status'] ?? ''));
        if ($b === 'critical') {
            $critical++;
        } elseif ($b === 'endangered') {
            $endangered++;
        } else {
            $vulnerable++;
        }
    }
    return [
        'total' => count($records),
        'critical' => $critical,
        'endangered' => $endangered,
        'vulnerable' => $vulnerable,
    ];
}

/**
 * Parse "lat,lng" or similar from mobile app.
 * @return array{0:float,1:float}|null
 */
function tg_parse_coordinates(?string $coords): ?array
{
    if ($coords === null || $coords === '' || $coords === '-') {
        return null;
    }
    $parts = preg_split('/[,\s]+/', trim($coords));
    if ($parts === false || count($parts) < 2) {
        return null;
    }
    $lat = filter_var($parts[0], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($parts[1], FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false) {
        return null;
    }
    return [$lat, $lng];
}

/**
 * Latitude/longitude from a tree record (explicit fields or coordinates string).
 *
 * @return array{0:float,1:float}|null [lat, lng]
 */
function tg_record_lat_lng(array $record): ?array
{
    $lat = $record['latitude'] ?? null;
    $lng = $record['longitude'] ?? null;
    if (is_numeric($lat) && is_numeric($lng)) {
        return [(float)$lat, (float)$lng];
    }
    return tg_parse_coordinates((string)($record['coordinates'] ?? ''));
}

function tg_scan_review_status(array $record): string
{
    $s = strtolower((string)($record['scanStatus'] ?? $record['reviewStatus'] ?? ''));
    if (in_array($s, ['pending', 'approved', 'reclassified', 'rejected'], true)) {
        return $s;
    }
    return 'approved';
}

/** Human-readable scan review label (legacy `rejected` → Reclassified). */
function tg_scan_review_label(string $status): string
{
    return match ($status) {
        'pending' => 'Pending',
        'approved' => 'Approved',
        'reclassified' => 'Reclassified',
        'rejected' => 'Reclassified (legacy)',
        default => ucfirst($status),
    };
}

function tg_scan_can_approve(string $status): bool
{
    return $status === 'pending';
}

/** Validators may correct species on pending, approved, legacy rejected, or prior reclassifications. */
function tg_scan_can_reclassify(string $status): bool
{
    return in_array($status, ['pending', 'approved', 'reclassified', 'rejected'], true);
}

/**
 * @param list<array<string, mixed>> $records
 * @return array{pending:int,approved:int,reclassified:int}
 */
function tg_scan_counts(array $records): array
{
    $p = $a = $rc = 0;
    foreach ($records as $rec) {
        $st = tg_scan_review_status($rec);
        if ($st === 'pending') {
            $p++;
        } elseif ($st === 'reclassified' || $st === 'rejected') {
            $rc++;
        } elseif ($st === 'approved') {
            $a++;
        }
    }
    return ['pending' => $p, 'approved' => $a, 'reclassified' => $rc];
}

function tg_user_display_name(string $uid, array $usersByUid): string
{
    $u = $usersByUid[$uid] ?? null;
    if (is_array($u) && !empty($u['fullName'])) {
        return (string)$u['fullName'];
    }
    return $uid !== '' ? $uid : '—';
}

function tg_role_label(string $role): string
{
    $r = strtolower(trim($role));
    return match ($r) {
        'admin' => 'Admin',
        'user', 'field', 'field_officer', 'officer' => 'Field Officer',
        'viewer' => 'Viewer',
        default => ucfirst($r ?: 'User'),
    };
}

function tg_role_badge_class(string $role): string
{
    $r = strtolower(trim($role));
    return match ($r) {
        'admin' => 'badge-purple',
        'user', 'field', 'field_officer', 'officer' => 'badge-blue',
        'viewer' => 'badge-gray',
        default => 'badge-gray',
    };
}
