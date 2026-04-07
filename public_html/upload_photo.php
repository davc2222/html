<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function fail_upload(string $msg, int $userId = 0): void {
    echo '<!doctype html><html lang="he"><head><meta charset="UTF-8"><title>שגיאת העלאה</title></head><body dir="rtl" style="font-family:Arial;padding:30px;">';
    echo '<h2>שגיאה בהעלאת תמונה</h2>';
    echo '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($userId > 0) {
        echo '<p><a href="/?page=profile&id=' . (int)$userId . '">חזרה לפרופיל</a></p>';
    } else {
        echo '<p><a href="/">חזרה</a></p>';
    }
    echo '</body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: /?page=login');
    exit;
}

if (!isset($_FILES['photo'])) {
    fail_upload('לא נבחר קובץ.', $userId);
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    fail_upload('שגיאת העלאה. קוד שגיאה: ' . (int)$file['error'], $userId);
}

$maxSize = 5 * 1024 * 1024;
if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    fail_upload('הקובץ גדול מדי או ריק. מותר עד 5MB.', $userId);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed, true)) {
    fail_upload('סוג קובץ לא נתמך. מותר: jpg, jpeg, png, webp.', $userId);
}

if (!getimagesize($file['tmp_name'])) {
    fail_upload('הקובץ שנבחר אינו תמונה תקינה.', $userId);
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    fail_upload('לא ניתן ליצור את תיקיית uploads.', $userId);
}

if (!is_writable($uploadDir)) {
    fail_upload('אין הרשאת כתיבה לתיקיית uploads.', $userId);
}

$newName = 'user_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$target = $uploadDir . '/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    fail_upload('שמירת הקובץ נכשלה.', $userId);
}

$count = $pdo->prepare("SELECT COUNT(*) FROM user_pics WHERE Id = :id");
$count->execute([':id' => $userId]);
$isMain = ($count->fetchColumn() == 0) ? 1 : 0;

$stmt = $pdo->prepare("
    INSERT INTO user_pics
    (Id, Pic_Name, Pic_Title, Pic_Status, Pic_Status_Str, Main_Pic, Main_Pic_Str)
    VALUES
    (:id, :name, '', 1, 'פעיל', :main, :main_str)
");

$stmt->execute([
    ':id' => $userId,
    ':name' => $newName,
    ':main' => $isMain,
    ':main_str' => $isMain ? 'כן' : 'לא'
]);

header('Location: /?page=profile&id=' . $userId . '&uploaded=1');
exit;
