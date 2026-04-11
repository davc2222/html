<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['restore_user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'לא נמצא משתמש לשחזור'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['restore_user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE users_profile
        SET Is_Frozen = 0
        WHERE Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);

    unset($_SESSION['restore_user_id'], $_SESSION['restore_user_name']);

    echo json_encode([
        'ok' => true,
        'redirect' => '/?page=login'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'שגיאה בשחזור הכרטיס'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}