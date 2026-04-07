<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("
        UPDATE users_profile
        SET last_seen = NOW()
        WHERE Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);

    echo json_encode(['ok' => true]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
