<?php
// ================================
// delete_photo.php - DESKTOP
// ================================

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=home');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$picNum = (int)($_POST['pic_num'] ?? 0);

if ($userId <= 0) {
    header('Location: /?page=login');
    exit;
}

if ($picNum <= 0) {
    header('Location: /?page=profile&id=' . $userId . '&edit=1');
    exit;
}

$stmt = $pdo->prepare("
    SELECT Pic_Name, Main_Pic
    FROM user_pics
    WHERE Pic_Num = :pic_num
      AND Id = :id
    LIMIT 1
");

$stmt->execute([
    ':pic_num' => $picNum,
    ':id'      => $userId
]);

$pic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pic) {
    header('Location: /?page=profile&id=' . $userId . '&edit=1');
    exit;
}

$fileName = trim((string)($pic['Pic_Name'] ?? ''));
$wasMain  = (int)($pic['Main_Pic'] ?? 0) === 1;

/*
|------------------------------------------------------------
| מחיקה מהמסד
|------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    DELETE FROM user_pics
    WHERE Pic_Num = :pic_num
      AND Id = :id
    LIMIT 1
");

$stmt->execute([
    ':pic_num' => $picNum,
    ':id'      => $userId
]);

/*
|------------------------------------------------------------
| מחיקת הקובץ הפיזי
|------------------------------------------------------------
*/
if ($fileName !== '') {
    $fullPath = __DIR__ . '/uploads/' . ltrim($fileName, '/');

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

/*
|------------------------------------------------------------
| אם נמחקה התמונה הראשית – קבע תמונה ראשית חדשה
|------------------------------------------------------------
*/
if ($wasMain) {
    $stmt = $pdo->prepare("
        SELECT Pic_Num
        FROM user_pics
        WHERE Id = :id
        ORDER BY Pic_Num ASC
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $userId
    ]);

    $newMain = $stmt->fetchColumn();

    if ($newMain) {
        $stmt = $pdo->prepare("
            UPDATE user_pics
            SET Main_Pic = 1,
                Main_Pic_Str = 'כן'
            WHERE Pic_Num = :pic_num
              AND Id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':pic_num' => $newMain,
            ':id'      => $userId
        ]);
    }
}

/*
|------------------------------------------------------------
| חזרה לפרופיל הרגיל, לא למובייל
|------------------------------------------------------------
*/
header('Location: /?page=profile&id=' . $userId . '&edit=1');
exit;
