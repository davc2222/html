<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/mobile/');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$picNum = (int)($_POST['pic_num'] ?? 0);

if ($userId <= 0 || $picNum <= 0) {
    header('Location: ' . APP_URL . '/mobile/?page=login');
    exit;
}

$stmt = $pdo->prepare("
    SELECT Pic_Num
    FROM user_pics
    WHERE Pic_Num = :pic_num
      AND Id = :id
    LIMIT 1
");
$stmt->execute([
    ':pic_num' => $picNum,
    ':id'      => $userId
]);

if (!$stmt->fetch()) {
    header('Location: ' . APP_URL . '/mobile/?page=profile&id=' . $userId);
    exit;
}

/* אפס תמונה ראשית קודמת */
$stmt = $pdo->prepare("
    UPDATE user_pics
    SET Main_Pic = 0,
        Main_Pic_Str = 'לא'
    WHERE Id = :id
");
$stmt->execute([':id' => $userId]);

/* קבע חדשה */
$stmt = $pdo->prepare("
    UPDATE user_pics
    SET Main_Pic = 1,
        Main_Pic_Str = 'כן'
    WHERE Pic_Num = :pic_num
      AND Id = :id
    LIMIT 1
");
$stmt->execute([
    ':pic_num' => $picNum,
    ':id'      => $userId
]);

header('Location: ' . APP_URL . '/mobile/?page=profile&id=' . $userId);
exit;
