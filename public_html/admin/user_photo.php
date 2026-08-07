<?php
declare(strict_types=1);

/*
 * Public and safe image endpoint.
 * It receives only a file name and never accepts an arbitrary path.
 * Both the regular site and the admin can use:
 * /user_photo.php?name=example.jpg
 */

$name = trim((string) ($_GET['name'] ?? ''));

if ($name === '') {
    http_response_code(404);
    exit;
}

/* Prevent directory traversal. */
$name = basename(str_replace('\\', '/', $name));

if ($name === '' || $name === '.' || $name === '..') {
    http_response_code(400);
    exit;
}

$publicRoot = realpath(dirname(__DIR__));

if ($publicRoot === false) {
    http_response_code(500);
    exit;
}

/*
 * Add the real image directory here if it is different.
 * The first existing matching file is returned.
 */
$directories = [
    $publicRoot . '/uploads',
    $publicRoot . '/uploads/users',
    $publicRoot . '/uploads/profiles',
    $publicRoot . '/uploads/profile',
    $publicRoot . '/user_pics',
    $publicRoot . '/pics',
    $publicRoot . '/photos',
    $publicRoot . '/images/users',
    $publicRoot . '/images/profiles',
    $publicRoot . '/assets/uploads',
];

$filePath = null;

foreach ($directories as $directory) {
    $candidate = $directory . DIRECTORY_SEPARATOR . $name;

    if (is_file($candidate) && is_readable($candidate)) {
        $filePath = $candidate;
        break;
    }
}

if ($filePath === null) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filePath);

$allowed = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
];

if (!in_array($mime, $allowed, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($filePath));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($filePath);