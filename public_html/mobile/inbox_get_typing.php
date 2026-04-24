<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $me = (int)$_SESSION['user_id'];
    $otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($otherUserId <= 0) {
        throw new Exception('Missing user_id');
    }

    $sql = "
        SELECT is_typing, updated_at
        FROM chat_typing
        WHERE from_user_id = :other_user_id
          AND to_user_id = :me
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':other_user_id' => $otherUserId,
        ':me' => $me
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $typing = false;

    if ($row) {
        $isTyping = (int)$row['is_typing'] === 1;
        $updatedAt = strtotime((string)$row['updated_at']);
        $fresh = $updatedAt && (time() - $updatedAt <=12);

        $typing = $isTyping && $fresh;
    }

    echo json_encode([
        'ok' => true,
        'typing' => $typing
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'typing' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

