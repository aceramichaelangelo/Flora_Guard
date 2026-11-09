<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function firebase_db_get(string $path): array
{
    $url = rtrim(FIREBASE_DB_URL, '/') . '/' . ltrim($path, '/') . '.json';
    $ctx = stream_context_create(['http' => ['timeout' => 12]]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        return [];
    }
    $data = json_decode($result, true);
    return is_array($data) ? $data : [];
}

function firebase_db_patch(string $path, array $payload): bool
{
    $url = rtrim(FIREBASE_DB_URL, '/') . '/' . ltrim($path, '/') . '.json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $ok = $response !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400;
    curl_close($ch);
    return $ok;
}

function firebase_db_delete(string $path): bool
{
    $url = rtrim(FIREBASE_DB_URL, '/') . '/' . ltrim($path, '/') . '.json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $ok = $response !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400;
    curl_close($ch);
    return $ok;
}

function firebase_sign_in_with_password(string $email, string $password): array
{
    if (FIREBASE_WEB_API_KEY === 'REPLACE_WITH_FIREBASE_WEB_API_KEY' && TG_DEV_LOGIN) {
        $e = strtolower(trim($email));
        if ($e === 'admin@davao.gov.ph' && $password === 'admin123') {
            return [
                'ok' => true,
                'localId' => 'dev-admin',
                'email' => $e,
                'idToken' => 'dev-token',
            ];
        }
        return ['ok' => false, 'message' => 'Invalid credentials (enable Firebase key or use dev login).'];
    }

    if (FIREBASE_WEB_API_KEY === 'REPLACE_WITH_FIREBASE_WEB_API_KEY') {
        return ['ok' => false, 'message' => 'Missing FIREBASE_WEB_API_KEY in config.php'];
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . FIREBASE_WEB_API_KEY;
    $payload = json_encode([
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status >= 400) {
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }

    $auth = json_decode($response, true);
    if (!is_array($auth) || empty($auth['localId'])) {
        return ['ok' => false, 'message' => 'Authentication failed.'];
    }

    return [
        'ok' => true,
        'localId' => (string)$auth['localId'],
        'email' => (string)($auth['email'] ?? $email),
        'idToken' => (string)($auth['idToken'] ?? ''),
    ];
}

/**
 * Create a new Firebase Auth user (Email/Password). Requires Web API key with sign-up allowed.
 *
 * @return array{ok:true,localId:string,email:string}|array{ok:false,message:string}
 */
function firebase_sign_up(string $email, string $password): array
{
    if (FIREBASE_WEB_API_KEY === 'REPLACE_WITH_FIREBASE_WEB_API_KEY') {
        return ['ok' => false, 'message' => 'Set FIREBASE_WEB_API_KEY in config.php to add users from the admin panel.'];
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . FIREBASE_WEB_API_KEY;
    $payload = json_encode([
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true,
    ], JSON_THROW_ON_ERROR);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 25,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'message' => 'Network error while creating account.'];
    }

    $body = json_decode($response, true);
    if ($status >= 400 && is_array($body) && isset($body['error']['message'])) {
        $code = (string)$body['error']['message'];
        $msg = match ($code) {
            'EMAIL_EXISTS' => 'That email is already registered.',
            'INVALID_EMAIL' => 'Invalid email address.',
            'WEAK_PASSWORD' => 'Password is too weak (use at least 6 characters).',
            'OPERATION_NOT_ALLOWED' => 'Email/Password sign-up is disabled in Firebase Console.',
            default => 'Could not create account: ' . $code,
        };
        return ['ok' => false, 'message' => $msg];
    }

    if (!is_array($body) || empty($body['localId'])) {
        return ['ok' => false, 'message' => 'Could not create account.'];
    }

    return [
        'ok' => true,
        'localId' => (string)$body['localId'],
        'email' => (string)($body['email'] ?? $email),
    ];
}
