<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    /* ========= בדיקת התחברות ========= */
    if (empty($_SESSION['user_id'])) {
        throw new RuntimeException('לא מחובר');
    }

    $blockerId = (int)$_SESSION['user_id'];

    /* ========= קבלת המשתמש לחסימה ========= */
    $blockedId = (int)($_POST['blocked_id'] ?? $_POST['user_id'] ?? 0);

    if ($blockedId <= 0) {
        throw new RuntimeException('משתמש לא תקין');
    }

    if ($blockedId === $blockerId) {
        throw new RuntimeException('לא ניתן לחסום את עצמך');
    }

    /* ========= בדיקה אם כבר חסום ========= */
    $stmt = $pdo->prepare("
        SELECT 1
        FROM blocked_users
        WHERE Id = :blocked
          AND Blocked_ById = :blocker
        LIMIT 1
    ");
    $stmt->execute([
        ':blocked' => $blockedId,
        ':blocker' => $blockerId
    ]);

    if ($stmt->fetch()) {
        echo json_encode([
            'ok' => true,
            'msg' => 'כבר חסום'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ========= הכנסת חסימה ========= */
    $stmt = $pdo->prepare("
        INSERT INTO blocked_users (Id, Blocked_ById, Created_At)
        VALUES (:blocked, :blocker, NOW())
    ");

    $stmt->execute([
        ':blocked' => $blockedId,
        ':blocker' => $blockerId
    ]);

    echo json_encode([
        'ok' => true,
        'msg' => 'נחסם בהצלחה'
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
