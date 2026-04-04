<?php
// ===== FILE: get_typing.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me = (int)($_SESSION['user_id'] ?? 0);
$otherId = (int)($_GET['user_id'] ?? 0);

if ($me <= 0 || $otherId <= 0 || $otherId === $me) {
    echo json_encode([
        'ok' => false,
        'typing' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM message_typing
        WHERE user_id = :other_id
          AND target_id = :me
          AND updated_at >= (NOW() - INTERVAL 4 SECOND)
        LIMIT 1
    ");
    $stmt->execute([
        ':other_id' => $otherId,
        ':me' => $me
    ]);

    echo json_encode([
        'ok' => true,
        'typing' => (bool)$stmt->fetchColumn()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'typing' => false
    ], JSON_UNESCAPED_UNICODE);
}
