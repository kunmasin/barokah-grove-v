<?php
require_once 'config.php';

$file = basename($_GET['file'] ?? '');

if ($file === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $file)) {
    http_response_code(404);
    exit('Image not found');
}

$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;

if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('Image not found');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path);
finfo_close($finfo);

$allowed = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif'
];

if (!in_array($mime, $allowed, true)) {
    http_response_code(403);
    exit('Invalid image');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');

readfile($path);
exit;
