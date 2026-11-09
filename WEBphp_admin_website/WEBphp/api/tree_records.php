<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/data_repository.php';
require_once dirname(__DIR__) . '/lib/barangay.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$result = tg_attach_barangays(tg_all_tree_records());

json_response([
    'ok' => true,
    'count' => count($result),
    'records' => $result,
]);
