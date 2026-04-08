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

    $userId  = (int)$_SESSION['user_id'];
    $viewNum = (int)($_POST['view_num'] ?? 0);

    if ($viewNum <= 0) {
        throw new RuntimeException('רשומה לא תקינה');
    }

    $stmt = $pdo->prepare("
        UPDATE views
        SET Deleted_By_ById = 1
        WHERE Num = :num
          AND ById = :me
        LIMIT 1
    ");
    $stmt->execute([
        ':num' => $viewNum,
        ':me'  => $userId
    ]);

    echo json_encode([
        'ok' => true
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
