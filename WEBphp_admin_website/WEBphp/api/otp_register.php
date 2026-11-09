<?php
declare(strict_types=1);

/**
 * POST JSON {"email","code","password","fullName"} — checks the emailed code, then creates the
 * Firebase Auth account (and its profile) on the server. The app signs in afterwards.
 */
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'POST required.'], 405);
}

$body = read_json_body();
$email = otp_normalize_email((string)($body['email'] ?? ''));
$code = preg_replace('/\D/', '', (string)($body['code'] ?? ''));
$password = (string)($body['password'] ?? '');
$fullName = trim(strip_tags((string)($body['fullName'] ?? '')));
$fullName = mb_substr($fullName, 0, 80);

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'Enter a valid email address.'], 422);
}
if (strlen((string)$code) !== 6) {
    json_response(['ok' => false, 'message' => 'Enter the 6-digit code.'], 422);
}
if (strlen($password) < 6 || strlen($password) > 128) {
    json_response(['ok' => false, 'message' => 'Password must be 6 to 128 characters.'], 422);
}
if ($fullName === '') {
    json_response(['ok' => false, 'message' => 'Full name is required.'], 422);
}

$rec = otp_load($email);
if ($rec === null || time() > (int)($rec['expiresAt'] ?? 0)) {
    otp_delete($email);
    json_response(['ok' => false, 'message' => 'That code expired. Request a new one.', 'expired' => true], 400);
}
$attempts = (int)($rec['attempts'] ?? 0);
if ($attempts >= OTP_MAX_ATTEMPTS) {
    otp_delete($email);
    json_response(['ok' => false, 'message' => 'Too many wrong tries. Request a new code.', 'expired' => true], 429);
}

if (!hash_equals((string)($rec['hash'] ?? ''), otp_hash($email, (string)$code))) {
    $rec['attempts'] = $attempts + 1;
    otp_save($email, $rec);
    $left = OTP_MAX_ATTEMPTS - $rec['attempts'];
    json_response(['ok' => false, 'message' => $left > 0 ? "Wrong code. {$left} tries left." : 'Too many wrong tries. Request a new code.', 'attemptsLeft' => max(0, $left)], 400);
}

$auth = firebase_sign_up($email, $password);
if (empty($auth['ok'])) {
    // Keep the code so a fixable problem (e.g. weak password) does not cost a new email.
    json_response(['ok' => false, 'message' => (string)($auth['message'] ?? 'Could not create the account.')], 400);
}

otp_delete($email);
$uid = (string)$auth['localId'];
$profileSaved = firebase_db_patch('users/' . $uid, [
    'uid' => $uid,
    'fullName' => $fullName,
    'email' => $email,
    'role' => 'user',
    'isGuest' => false,
    'emailVerified' => true,
    'createdAt' => (int) round(microtime(true) * 1000),
]);

json_response(['ok' => true, 'uid' => $uid, 'profileSaved' => $profileSaved]);
