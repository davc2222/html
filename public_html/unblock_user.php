<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        throw new RuntimeException('לא מחובר');
    }

    $blockerId = (int)$_SESSION['user_id'];
    $blockedId = (int)($_POST['blocked_id'] ?? 0);

    if ($blockedId <= 0) {
        throw new RuntimeException('משתמש לא תקין');
    }

    $stmt = $pdo->prepare("
        DELETE FROM blocked_users
        WHERE Id = :blocked
          AND Blocked_ById = :blocker
        LIMIT 1
    ");
    $stmt->execute([
        ':blocked' => $blockedId,
        ':blocker' => $blockerId
    ]);

    echo json_encode([
        'ok'  => true,
        'msg' => 'בוטלה חסימה'
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
