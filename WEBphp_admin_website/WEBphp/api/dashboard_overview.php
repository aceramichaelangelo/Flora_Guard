<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/data_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$allRecords = tg_all_tree_records();
$users = tg_all_users();

$counts = tg_conservation_counts($allRecords);
$recent = [];
foreach (array_slice($allRecords, 0, 8) as $r) {
    $recent[] = [
        'id' => $r['id'] ?? '',
        'title' => $r['title'] ?? 'Unknown',
        'scientific' => $r['scientific'] ?? '-',
        'status' => $r['status'] ?? 'unknown',
        'locationName' => $r['locationName'] ?? '-',
        'uid' => $r['uid'] ?? '',
        'createdAt' => $r['createdAt'] ?? null,
    ];
}

$totalUsers = count($users);
$admins = $fieldOfficers = 0;
foreach ($users as $u) {
    if (!is_array($u)) {
        continue;
    }
    $role = strtolower((string)($u['role'] ?? 'user'));
    if ($role === 'admin') {
        $admins++;
    } else {
        $fieldOfficers++;
    }
}

json_response([
    'ok' => true,
    'stats' => [
        'totalTrees' => $counts['total'],
        'critical' => $counts['critical'],
        'endangered' => $counts['endangered'],
        'vulnerable' => $counts['vulnerable'],
        'totalUsers' => $totalUsers,
        'admins' => $admins,
        'fieldOfficers' => $fieldOfficers,
    ],
    'recentActivity' => $recent,
]);
