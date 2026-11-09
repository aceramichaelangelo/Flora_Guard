<?php
declare(strict_types=1);

require_once __DIR__ . '/firebase_client.php';

/**
 * Email one-time codes for app registration.
 *
 * Codes are stored only as HMAC hashes in WEBphp/storage/otp (web access denied by .htaccess),
 * expire after OTP_TTL seconds and lock after OTP_MAX_ATTEMPTS wrong tries.
 *
 * Optional constants (define in config.php): OTP_MAIL_FROM, OTP_DEBUG_ECHO (local testing only).
 */
const OTP_TTL = 600;
const OTP_MAX_ATTEMPTS = 5;
const OTP_RESEND_GAP = 60;
const OTP_MAX_PER_EMAIL_HOUR = 5;
const OTP_MAX_PER_IP_HOUR = 20;

function otp_dir(): string
{
    $root = dirname(__DIR__) . '/storage';
    $dir = $root . '/otp';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    foreach ([$root, $dir] as $d) {
        $ht = $d . '/.htaccess';
        if (is_dir($d) && !is_file($ht)) {
            @file_put_contents($ht, "Require all denied\n");
        }
    }
    return $dir;
}

function otp_secret(): string
{
    $file = otp_dir() . '/.secret';
    if (!is_file($file)) {
        @file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
    }
    $secret = (string)@file_get_contents($file);
    return $secret !== '' ? $secret : 'fallback-secret-change-me';
}

function otp_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function otp_path(string $email): string
{
    return otp_dir() . '/' . hash('sha256', otp_normalize_email($email)) . '.json';
}

function otp_load(string $email): ?array
{
    $raw = @file_get_contents(otp_path($email));
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function otp_save(string $email, array $data): void
{
    @file_put_contents(otp_path($email), json_encode($data), LOCK_EX);
}

function otp_delete(string $email): void
{
    @unlink(otp_path($email));
}

function otp_hash(string $email, string $code): string
{
    return hash_hmac('sha256', otp_normalize_email($email) . '|' . $code, otp_secret());
}

/** Sliding-window limiter. Returns false when $max hits already happened within $windowSec. */
function otp_rate_ok(string $key, int $max, int $windowSec): bool
{
    $file = otp_dir() . '/rl_' . hash('sha256', $key) . '.json';
    $now = time();
    $hits = [];
    $raw = @file_get_contents($file);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter($decoded, static fn($t) => is_int($t) && $t > $now - $windowSec));
        }
    }
    if (count($hits) >= $max) {
        return false;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

function otp_client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function otp_mail_from(): string
{
    if (defined('OTP_MAIL_FROM') && OTP_MAIL_FROM !== '') {
        return (string)OTP_MAIL_FROM;
    }
    $host = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', (string)$host);
    return 'no-reply@' . $host;
}

function otp_send_mail(string $email, string $code): bool
{
    $from = otp_mail_from();
    $minutes = (int)(OTP_TTL / 60);
    $subject = 'Your Flora Guard verification code';
    $text = "Your Flora Guard verification code is: {$code}\n\n"
        . "It expires in {$minutes} minutes. If you did not try to register, you can ignore this email.\n";
    $html = '<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;padding:24px;border:1px solid #c8e6c9;border-radius:16px">'
        . '<h2 style="color:#0d3b1e;margin:0 0 8px">Flora Guard</h2>'
        . '<p style="color:#37474f;margin:0 0 16px">Use this code to finish creating your account:</p>'
        . '<p style="font-size:34px;letter-spacing:8px;font-weight:700;color:#1b5e20;margin:0 0 16px">' . htmlspecialchars($code) . '</p>'
        . '<p style="color:#78909c;font-size:13px;margin:0">It expires in ' . $minutes . ' minutes. If you did not try to register, ignore this email.</p>'
        . '</div>';

    $boundary = 'fg_' . bin2hex(random_bytes(8));
    $headers = [
        'From: Flora Guard <' . $from . '>',
        'Reply-To: ' . $from,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$text}\r\n"
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
        . "--{$boundary}--";

    return @mail($email, $subject, $body, implode("\r\n", $headers), '-f' . $from);
}
