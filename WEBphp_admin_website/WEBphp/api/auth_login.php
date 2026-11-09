<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$body = read_json_body();
$email = strtolower(trim((string)($body['email'] ?? '')));
$password = (string)($body['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['ok' => false, 'message' => 'Email and password are required.'], 400);
}

$auth = firebase_sign_in_with_password($email, $password);
if (empty($auth['ok'])) {
    json_response(['ok' => false, 'message' => (string)($auth['message'] ?? 'Login failed.')], 401);
}

$uid = (string)$auth['localId'];
if ($uid === 'dev-admin') {
    json_response([
        'ok' => true,
        'token' => $auth['idToken'] ?? null,
        'user' => [
            'uid' => $uid,
            'email' => $auth['email'] ?? $email,
            'fullName' => 'Admin User',
            'role' => 'admin',
        ],
    ]);
}

$profile = firebase_db_get("users/$uid");
$role = strtolower((string)($profile['role'] ?? 'user'));
if ($role !== 'admin') {
    json_response(['ok' => false, 'message' => 'Access denied: admin account required.'], 403);
}

json_response([
    'ok' => true,
    'token' => $auth['idToken'] ?? null,
    'user' => [
        'uid' => $uid,
        'email' => $auth['email'] ?? $email,
        'fullName' => (string)($profile['fullName'] ?? 'Admin User'),
        'role' => $role,
    ],
]);
