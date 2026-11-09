<?php

declare(strict_types=1);

require_once __DIR__ . '/data_repository.php';

/**
 * Aggregation helpers for Dashboard, Reports & Analytics, and GeoAnalytics.
 *
 * All functions take the flat record list from `tg_all_tree_records()` so a page
 * only hits Firebase once.
 */

/** Administrative location of a record. Falls back to parsing `locationName`. */
function tg_record_geo(array $r): array
{
    $barangay = trim((string)($r['barangay'] ?? ''));
    $municipality = trim((string)($r['municipality'] ?? ''));
    $province = trim((string)($r['province'] ?? ''));

    if ($barangay === '' || $municipality === '' || $province === '') {
        // `locationName` from the mobile app is free text: "Brgy X, Town, Province".
        $parts = array_values(array_filter(array_map(
            'trim',
            explode(',', (string)($r['locationName'] ?? ''))
        ), static fn (string $p): bool => $p !== '' && $p !== '-'));
        $count = count($parts);
        if ($barangay === '' && $count >= 3) {
            $barangay = $parts[0];
        }
        if ($municipality === '' && $count >= 2) {
            $municipality = $parts[$count - 2];
        }
        if ($province === '' && $count >= 1) {
            $province = $parts[$count - 1];
        }
    }

    return [
        'barangay' => $barangay !== '' ? $barangay : 'Unspecified',
        'municipality' => $municipality !== '' ? $municipality : 'Unspecified',
        'province' => $province !== '' ? $province : APP_REGION,
    ];
}

/** Display name for the species of a record. */
function tg_record_species_label(array $r): string
{
    $t = trim((string)($r['title'] ?? ''));
    if ($t !== '' && $t !== '-') {
        return $t;
    }
    $s = trim((string)($r['scientific'] ?? ''));
    return $s !== '' && $s !== '-' ? $s : 'Unknown';
}

/** Confidence as a 0..1 float (mobile writes 0..1, tolerate 0..100). */
function tg_record_confidence(array $r): float
{
    $c = (float)($r['confidence'] ?? 0);
    if ($c > 1.0) {
        $c = $c / 100.0;
    }
    return max(0.0, min(1.0, $c));
}

/** @param list<array<string,mixed>> $records */
function tg_average_confidence(array $records): float
{
    $vals = [];
    foreach ($records as $r) {
        $c = tg_record_confidence($r);
        if ($c > 0) {
            $vals[] = $c;
        }
    }
    return $vals === [] ? 0.0 : array_sum($vals) / count($vals);
}

/**
 * Descending tally keyed by a caller-supplied field extractor.
 *
 * @param list<array<string,mixed>> $records
 * @param callable(array<string,mixed>):string $key
 * @return array<string,int>
 */
function tg_tally(array $records, callable $key): array
{
    $out = [];
    foreach ($records as $r) {
        $k = $key($r);
        if ($k === '') {
            $k = 'Unspecified';
        }
        $out[$k] = ($out[$k] ?? 0) + 1;
    }
    arsort($out);
    return $out;
}

/**
 * Detection trend buckets.
 *
 * @param list<array<string,mixed>> $records
 * @param 'daily'|'weekly'|'monthly' $granularity
 * @return array{labels:list<string>,values:list<int>}
 */
function tg_detection_trend(array $records, string $granularity = 'monthly', int $buckets = 6): array
{
    $now = new DateTimeImmutable('today');
    [$step, $fmtKey, $fmtLabel] = match ($granularity) {
        'daily' => ['-%d days', 'Y-m-d', 'M j'],
        'weekly' => ['-%d weeks', 'o-\WW', '\WW o'],
        default => ['-%d months', 'Y-m', 'M Y'],
    };

    $slots = [];
    for ($i = $buckets - 1; $i >= 0; $i--) {
        $d = $now->modify(sprintf($step, $i));
        $slots[$d->format($fmtKey)] = ['label' => $d->format($fmtLabel), 'count' => 0];
    }

    foreach ($records as $r) {
        $ms = (int)($r['createdAt'] ?? 0);
        if ($ms <= 0) {
            continue;
        }
        $key = (new DateTimeImmutable('@' . (int)floor($ms / 1000)))
            ->setTimezone($now->getTimezone())
            ->format($fmtKey);
        if (isset($slots[$key])) {
            $slots[$key]['count']++;
        }
    }

    return [
        'labels' => array_values(array_map(static fn (array $s): string => (string)$s['label'], $slots)),
        'values' => array_values(array_map(static fn (array $s): int => (int)$s['count'], $slots)),
    ];
}

/**
 * Average confidence per species.
 *
 * @param list<array<string,mixed>> $records
 * @return array<string,float>
 */
function tg_confidence_per_species(array $records): array
{
    $sum = [];
    $n = [];
    foreach ($records as $r) {
        $k = tg_record_species_label($r);
        $c = tg_record_confidence($r);
        if ($c <= 0) {
            continue;
        }
        $sum[$k] = ($sum[$k] ?? 0.0) + $c;
        $n[$k] = ($n[$k] ?? 0) + 1;
    }
    $out = [];
    foreach ($sum as $k => $total) {
        $out[$k] = $total / max(1, $n[$k]);
    }
    arsort($out);
    return $out;
}

/**
 * Contributor leaderboard ranked by accepted (approved) detections.
 *
 * @param list<array<string,mixed>> $records
 * @param array<string,array<string,mixed>> $usersByUid
 * @return list<array<string,mixed>>
 */
function tg_leaderboard(array $records, array $usersByUid): array
{
    $agg = [];
    $monthStart = (new DateTimeImmutable('first day of this month 00:00:00'))->getTimestamp() * 1000;

    foreach ($records as $r) {
        $uid = (string)($r['uid'] ?? '');
        if ($uid === '') {
            continue;
        }
        if (!isset($agg[$uid])) {
            $agg[$uid] = [
                'accepted' => 0,
                'total' => 0,
                'protected' => 0,
                'monthAccepted' => 0,
                'barangays' => [],
            ];
        }
        $agg[$uid]['total']++;

        $status = tg_scan_review_status($r);
        if (in_array($status, ['approved', 'reclassified'], true)) {
            $agg[$uid]['accepted']++;
            if ((int)($r['createdAt'] ?? 0) >= $monthStart) {
                $agg[$uid]['monthAccepted']++;
            }
            if (tg_status_bucket((string)($r['status'] ?? '')) !== 'vulnerable') {
                $agg[$uid]['protected']++;
            }
        }

        $b = tg_record_geo($r)['barangay'];
        $agg[$uid]['barangays'][$b] = ($agg[$uid]['barangays'][$b] ?? 0) + 1;
    }

    $rows = [];
    foreach ($agg as $uid => $a) {
        arsort($a['barangays']);
        $top = array_key_first($a['barangays']);
        $rows[] = [
            'uid' => $uid,
            'name' => tg_user_display_name($uid, $usersByUid),
            'role' => tg_role_label((string)($usersByUid[$uid]['role'] ?? 'user')),
            'accepted' => (int)$a['accepted'],
            'total' => (int)$a['total'],
            'rate' => $a['total'] > 0 ? $a['accepted'] / $a['total'] : 0.0,
            'protected' => (int)$a['protected'],
            'topBarangay' => $top !== null ? (string)$top : 'Unspecified',
            'monthAccepted' => (int)$a['monthAccepted'],
        ];
    }

    usort($rows, static fn (array $x, array $y): int
        => [$y['accepted'], $y['total']] <=> [$x['accepted'], $x['total']]);

    return $rows;
}

/**
 * Validator throughput. `validatedAt`/`validatedBy` are written by scans.php.
 *
 * @param list<array<string,mixed>> $records
 * @return array<string,mixed>
 */
function tg_validator_stats(array $records): array
{
    $accepted = 0;
    $rejected = 0;
    $reclassified = 0;
    $pending = 0;
    $durations = [];
    $timeline = [];

    foreach ($records as $r) {
        $status = tg_scan_review_status($r);
        if ($status === 'approved') {
            $accepted++;
        } elseif ($status === 'rejected') {
            $rejected++;
        } elseif ($status === 'reclassified') {
            $reclassified++;
        } else {
            $pending++;
        }

        $validatedAt = (int)($r['validatedAt'] ?? 0);
        $createdAt = (int)($r['createdAt'] ?? 0);
        if ($validatedAt > 0 && $createdAt > 0 && $validatedAt >= $createdAt) {
            $durations[] = ($validatedAt - $createdAt) / 1000.0;
            $timeline[] = [
                'when' => $validatedAt,
                'label' => (string)($r['validatedBy'] ?? 'Administrator'),
                'status' => $status,
                'species' => tg_record_species_label($r),
            ];
        }
    }

    usort($timeline, static fn (array $a, array $b): int => $b['when'] <=> $a['when']);

    return [
        'accepted' => $accepted,
        'rejected' => $rejected,
        'reclassified' => $reclassified,
        'pending' => $pending,
        'avgSeconds' => $durations === [] ? 0.0 : array_sum($durations) / count($durations),
        'timeline' => array_slice($timeline, 0, 12),
    ];
}

/** Human-friendly duration for validator averages. */
function tg_format_duration(float $seconds): string
{
    if ($seconds <= 0) {
        return '&mdash;';
    }
    if ($seconds < 90) {
        return round($seconds) . 's';
    }
    if ($seconds < 5400) {
        return round($seconds / 60) . ' min';
    }
    if ($seconds < 172800) {
        return round($seconds / 3600, 1) . ' hrs';
    }
    return round($seconds / 86400, 1) . ' days';
}

/**
 * Detection density per rounded coordinate cell, for heatmap statistics.
 *
 * @param list<array<string,mixed>> $records
 * @return array<string,mixed>
 */
function tg_detection_density(array $records, int $precision = 2): array
{
    $cells = [];
    $mapped = 0;
    foreach ($records as $r) {
        $ll = tg_record_lat_lng($r);
        if ($ll === null) {
            continue;
        }
        $mapped++;
        $key = round($ll[0], $precision) . ', ' . round($ll[1], $precision);
        $cells[$key] = ($cells[$key] ?? 0) + 1;
    }
    arsort($cells);
    $densest = array_key_first($cells);
    return [
        'cells' => count($cells),
        'mapped' => $mapped,
        'maxPerCell' => $cells === [] ? 0 : (int)reset($cells),
        'densest' => $densest !== null ? (string)$densest : 'No mapped records',
    ];
}

/**
 * Apply the shared Reports/Map filter set to a record list.
 *
 * @param list<array<string,mixed>> $records
 * @param array<string,string> $f
 * @param array<string,array<string,mixed>> $usersByUid
 * @return list<array<string,mixed>>
 */
function tg_filter_records(array $records, array $f, array $usersByUid = []): array
{
    $from = ($f['from'] ?? '') !== '' ? strtotime($f['from'] . ' 00:00:00') : false;
    $to = ($f['to'] ?? '') !== '' ? strtotime($f['to'] . ' 23:59:59') : false;

    $out = [];
    foreach ($records as $r) {
        $ts = (int)floor(((int)($r['createdAt'] ?? 0)) / 1000);
        if ($from !== false && $ts > 0 && $ts < $from) {
            continue;
        }
        if ($to !== false && $ts > 0 && $ts > $to) {
            continue;
        }
        if (($f['species'] ?? 'all') !== 'all' && tg_record_species_label($r) !== $f['species']) {
            continue;
        }
        if (($f['validation'] ?? 'all') !== 'all' && tg_scan_review_status($r) !== $f['validation']) {
            continue;
        }
        if (($f['conservation'] ?? 'all') !== 'all'
            && tg_status_bucket((string)($r['status'] ?? '')) !== $f['conservation']) {
            continue;
        }

        $geo = tg_record_geo($r);
        $geoMismatch = false;
        foreach (['province', 'municipality', 'barangay'] as $level) {
            if (($f[$level] ?? 'all') !== 'all' && $geo[$level] !== $f[$level]) {
                $geoMismatch = true;
                break;
            }
        }
        if ($geoMismatch) {
            continue;
        }

        if (($f['role'] ?? 'all') !== 'all') {
            $role = strtolower((string)($usersByUid[(string)($r['uid'] ?? '')]['role'] ?? 'user'));
            if ($f['role'] === 'admin') {
                $match = $role === 'admin';
            } elseif ($f['role'] === 'viewer') {
                $match = $role === 'viewer';
            } else {
                $match = !in_array($role, ['admin', 'viewer'], true);
            }
            if (!$match) {
                continue;
            }
        }

        $out[] = $r;
    }

    return $out;
}

/**
 * Distinct sorted values for a filter dropdown.
 *
 * @param list<array<string,mixed>> $records
 * @param callable(array<string,mixed>):string $key
 * @return list<string>
 */
function tg_filter_options(array $records, callable $key): array
{
    $set = [];
    foreach ($records as $r) {
        $v = $key($r);
        if ($v !== '') {
            $set[$v] = true;
        }
    }
    $out = array_keys($set);
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return array_map('strval', $out);
}

