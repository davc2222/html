<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function upload_response(bool $ok, string $message = ''): void
{
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'ok'      => $ok,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_response(false, 'בקשה לא תקינה.');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    upload_response(false, 'המשתמש לא מחובר.');
}

if (!isset($_FILES['photo'])) {
    upload_response(false, 'לא נבחר קובץ.');
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    upload_response(false, 'שגיאת העלאה. קוד שגיאה: ' . (int) $file['error']);
}

$maxSize = 5 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    upload_response(false, 'הקובץ גדול מדי או ריק. מותר עד 5MB.');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed, true)) {
    upload_response(false, 'סוג קובץ לא נתמך. מותר: jpg, jpeg, png, webp.');
}

if (!getimagesize($file['tmp_name'])) {
    upload_response(false, 'הקובץ שנבחר אינו תמונה תקינה.');
}

$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    upload_response(false, 'לא ניתן ליצור את תיקיית uploads.');
}

if (!is_writable($uploadDir)) {
    upload_response(false, 'אין הרשאת כתיבה לתיקיית uploads.');
}

$newName =
    'user_' .
    $userId .
    '_' .
    time() .
    '_' .
    bin2hex(random_bytes(3)) .
    '.' .
    $ext;

$target = $uploadDir . '/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    upload_response(false, 'שמירת הקובץ נכשלה.');
}

$count = $pdo->prepare("
    SELECT COUNT(*)
    FROM user_pics
    WHERE Id = :id
");

$count->execute([
    ':id' => $userId
]);

$isMain = ($count->fetchColumn() == 0) ? 1 : 0;

$stmt = $pdo->prepare("
    INSERT INTO user_pics
    (
        Id,
        Pic_Name,
        Pic_Title,
        Pic_Status,
        Pic_Status_Str,
        Main_Pic,
        Main_Pic_Str
    )
    VALUES
    (
        :id,
        :name,
        '',
        1,
        'פעיל',
        :main,
        :main_str
    )
");

$stmt->execute([
    ':id'       => $userId,
    ':name'     => $newName,
    ':main'     => $isMain,
    ':main_str' => $isMain ? 'כן' : 'לא'
]);

upload_response(true, 'התמונה הועלתה בהצלחה');
?>