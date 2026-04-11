<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'count' => 0]);
    exit;
}

$me = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM messages 
        WHERE Id = :me 
          AND `New` = 1
    ");
    $stmt->execute([':me' => $me]);

    echo json_encode([
        'ok' => true,
        'count' => (int)$stmt->fetchColumn()
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'count' => 0]);
}