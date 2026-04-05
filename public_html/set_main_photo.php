<?php
// ===== FILE: set_main_photo.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$picNum = (int)($_POST['pic_num'] ?? 0);

if ($userId <= 0 || $picNum <= 0) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

try {
    /* =========================
       verify ownership
    ========================= */
    $checkStmt = $pdo->prepare("
        SELECT Pic_Num
        FROM user_pics
        WHERE Pic_Num = :pic_num
          AND Id = :id
        LIMIT 1
    ");
    $checkStmt->execute([
        ':pic_num' => $picNum,
        ':id' => $userId
    ]);

    $exists = $checkStmt->fetchColumn();

    if (!$exists) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    /* =========================
       reset all main flags
    ========================= */
    $resetStmt = $pdo->prepare("
        UPDATE user_pics
        SET Main_Pic = 0,
            Main_Pic_Str = 'לא'
        WHERE Id = :id
    ");
    $resetStmt->execute([':id' => $userId]);

    /* =========================
       set selected image as main
    ========================= */
    $mainStmt = $pdo->prepare("
        UPDATE user_pics
        SET Main_Pic = 1,
            Main_Pic_Str = 'כן'
        WHERE Pic_Num = :pic_num
          AND Id = :id
    ");
    $mainStmt->execute([
        ':pic_num' => $picNum,
        ':id' => $userId
    ]);

    http_response_code(200);
    echo 'OK';
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Server Error';
    exit;
}
