<?php
session_start();
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $fromUserId = (int)$_SESSION['user_id'];
    $toUserId   = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
    $isTyping   = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : 0;

    if ($toUserId <= 0) {
        throw new Exception('Missing to_user_id');
    }

    $sql = "
        INSERT INTO chat_typing (from_user_id, to_user_id, is_typing, updated_at)
        VALUES (:from_user_id, :to_user_id, :is_typing, NOW())
        ON DUPLICATE KEY UPDATE
            is_typing = VALUES(is_typing),
            updated_at = NOW()
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':from_user_id' => $fromUserId,
        ':to_user_id'   => $toUserId,
        ':is_typing'    => $isTyping,
    ]);

    echo json_encode([
        'ok' => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
