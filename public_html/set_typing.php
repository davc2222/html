<?php
// ===== FILE: set_typing.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me = (int)($_SESSION['user_id'] ?? 0);
$targetId = (int)($_POST['target_id'] ?? 0);
$isTyping = (int)($_POST['is_typing'] ?? 0);

if ($me <= 0 || $targetId <= 0 || $targetId === $me) {
    echo json_encode([
        'ok' => false,
        'message' => 'נתונים לא תקינים'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($isTyping === 1) {
        $stmt = $pdo->prepare("
            INSERT INTO message_typing (user_id, target_id, updated_at)
            VALUES (:user_id, :target_id, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stmt->execute([
            ':user_id' => $me,
            ':target_id' => $targetId
        ]);
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM message_typing
            WHERE user_id = :user_id
              AND target_id = :target_id
        ");
        $stmt->execute([
            ':user_id' => $me,
            ':target_id' => $targetId
        ]);
    }

    echo json_encode([
        'ok' => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'שגיאת שרת'
    ], JSON_UNESCAPED_UNICODE);
}
