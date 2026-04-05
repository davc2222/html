<?php
// ===== FILE: upload_photo.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

/* ===== בדיקות ===== */
$maxSize = 5 * 1024 * 1024;
if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed, true)) {
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

if (!getimagesize($file['tmp_name'])) {
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

/* ===== שמירה ===== */
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$newName = 'user_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$target = $uploadDir . '/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    header('Location: /?page=profile&id=' . $userId);
    exit;
}

/* ===== DB ===== */
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

header('Location: /?page=profile&id=' . $userId);
exit;
