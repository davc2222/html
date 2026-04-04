<?php
// ===== FILE: mark_messages_read.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me = (int)($_SESSION['user_id'] ?? 0);
$otherId = (int)($_POST['user_id'] ?? 0);

if ($me <= 0 || $otherId <= 0 || $me === $otherId) {
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE messages
        SET `New` = 0
        WHERE Id = :me
          AND ById = :other_id
          AND `New` = 1
    ");

    $stmt->execute([
        ':me' => $me,
        ':other_id' => $otherId
    ]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
}
