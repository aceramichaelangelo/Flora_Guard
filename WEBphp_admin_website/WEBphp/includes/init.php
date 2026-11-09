<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/firebase_client.php';
require_once dirname(__DIR__) . '/lib/data_repository.php';

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Mapbox public token: env `MAPBOX_ACCESS_TOKEN` wins over `config.php` constant. */
function tg_mapbox_access_token(): string
{
    $e = getenv('MAPBOX_ACCESS_TOKEN');
    if (is_string($e) && $e !== '') {
        return $e;
    }
    return MAPBOX_ACCESS_TOKEN;
}

function tg_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_start();
}

function tg_require_login(): void
{
    tg_session_start();
    $u = $_SESSION['tg_user'] ?? null;
    if (!is_array($u) || ($u['role'] ?? '') !== 'admin') {
        header('Location: login.php', true, 302);
        exit;
    }
}

/** @return array{uid:string,email:string,fullName:string,role:string} */
function tg_current_user(): array
{
    tg_session_start();
    $u = $_SESSION['tg_user'] ?? null;
    return is_array($u) ? $u : ['uid' => '', 'email' => '', 'fullName' => '', 'role' => ''];
}

/**
 * @return array{ok:bool,message?:string}
 */
function tg_attempt_admin_session(string $email, string $password): array
{
    $auth = firebase_sign_in_with_password($email, $password);
    if (empty($auth['ok'])) {
        return ['ok' => false, 'message' => (string)($auth['message'] ?? 'Login failed.')];
    }

    $uid = (string)$auth['localId'];
    if ($uid === 'dev-admin') {
        $_SESSION['tg_user'] = [
            'uid' => $uid,
            'email' => (string)($auth['email'] ?? $email),
            'fullName' => 'Admin User',
            'role' => 'admin',
        ];
        return ['ok' => true];
    }

    $profile = firebase_db_get('users/' . $uid);
    $role = strtolower((string)($profile['role'] ?? 'user'));
    if ($role !== 'admin') {
        return ['ok' => false, 'message' => 'Access denied: admin account required.'];
    }

    $_SESSION['tg_user'] = [
        'uid' => $uid,
        'email' => (string)($auth['email'] ?? $email),
        'fullName' => (string)($profile['fullName'] ?? 'Admin User'),
        'role' => $role,
    ];
    return ['ok' => true];
}
