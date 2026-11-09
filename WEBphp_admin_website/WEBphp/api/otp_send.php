<?php
declare(strict_types=1);

/**
 * POST JSON {"email": "..."} — emails a 6-digit registration code.
 * Responds {"ok":true,"expiresIn":600,"resendIn":60} or {"ok":false,"message":"..."}.
 */
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'POST required.'], 405);
}

$body = read_json_body();
$email = otp_normalize_email((string)($body['email'] ?? ''));
if ($email === '' || strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'Enter a valid email address.'], 422);
}

if (!otp_rate_ok('ip:' . otp_client_ip(), OTP_MAX_PER_IP_HOUR, 3600)) {
    json_response(['ok' => false, 'message' => 'Too many requests. Try again later.'], 429);
}

$existing = otp_load($email);
if ($existing !== null) {
    $wait = OTP_RESEND_GAP - (time() - (int)($existing['sentAt'] ?? 0));
    if ($wait > 0) {
        json_response(['ok' => false, 'message' => "Please wait {$wait}s before requesting a new code.", 'retryAfter' => $wait], 429);
    }
}
if (!otp_rate_ok('email:' . $email, OTP_MAX_PER_EMAIL_HOUR, 3600)) {
    json_response(['ok' => false, 'message' => 'Too many codes requested for this email. Try again in an hour.'], 429);
}

$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
otp_save($email, [
    'hash' => otp_hash($email, $code),
    'sentAt' => time(),
    'expiresAt' => time() + OTP_TTL,
    'attempts' => 0,
]);

if (!otp_send_mail($email, $code)) {
    otp_delete($email);
    json_response(['ok' => false, 'message' => 'Could not send the email right now. Please try again.'], 502);
}

$out = ['ok' => true, 'expiresIn' => OTP_TTL, 'resendIn' => OTP_RESEND_GAP];
if (defined('OTP_DEBUG_ECHO') && OTP_DEBUG_ECHO === true) {
    $out['debugCode'] = $code; // local testing only; never enable on the live server
}
json_response($out);
