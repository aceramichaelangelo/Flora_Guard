<?php
declare(strict_types=1);

/**
 * Receives a scan JPEG from the mobile app and stores it under uploads/scan_images/{uid}/{recordId}.jpg.
 * POST multipart: uid, recordId, image (file). Responds with {"ok":true,"imageUrl":"uploads/..."} — a
 * path relative to the WEBphp root so the admin pages can use it directly as <img src>.
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function upload_fail(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_fail(405, 'POST required.');
}

$uid = (string)($_POST['uid'] ?? '');
$recordId = (string)($_POST['recordId'] ?? '');
$idPattern = '/^[A-Za-z0-9_-]{1,128}$/';
if (preg_match($idPattern, $uid) !== 1 || preg_match($idPattern, $recordId) !== 1) {
    upload_fail(400, 'Invalid uid or recordId.');
}

$file = $_FILES['image'] ?? null;
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    upload_fail(400, 'Image upload failed (error ' . (string)($file['error'] ?? 'none') . ').');
}
if (!is_uploaded_file((string)$file['tmp_name'])) {
    upload_fail(400, 'Not an uploaded file.');
}
if ((int)$file['size'] <= 0 || (int)$file['size'] > 8 * 1024 * 1024) {
    upload_fail(413, 'Image must be between 1 byte and 8 MB.');
}

$head = (string)file_get_contents((string)$file['tmp_name'], false, null, 0, 3);
$info = @getimagesize((string)$file['tmp_name']);
if ($head !== "\xFF\xD8\xFF" || $info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG) {
    upload_fail(415, 'Only JPEG images are accepted.');
}

// The app creates the record first, then uploads; refuse images for unknown records.
// Extra photos of one scan are sent as "{recordId}_1", "{recordId}_2", ... so fall back to the base record.
$baseId = preg_replace('/_\d{1,2}$/', '', $recordId);
$exists = firebase_db_get('tree_records/' . $uid . '/' . $recordId) !== []
    || ($baseId !== $recordId && firebase_db_get('tree_records/' . $uid . '/' . $baseId) !== []);
if (!$exists) {
    upload_fail(404, 'Record not found.');
}

$dir = dirname(__DIR__) . '/uploads/scan_images/' . $uid;
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    upload_fail(500, 'Could not create upload folder.');
}
if (!move_uploaded_file((string)$file['tmp_name'], $dir . '/' . $recordId . '.jpg')) {
    upload_fail(500, 'Could not save image.');
}

echo json_encode([
    'ok' => true,
    'imageUrl' => 'uploads/scan_images/' . $uid . '/' . $recordId . '.jpg',
]);
