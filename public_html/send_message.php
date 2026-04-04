<?php
// ===== FILE: send_message.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me = (int)($_SESSION['user_id'] ?? 0);
$to = (int)($_POST['to'] ?? 0);
$text = trim((string)($_POST['text'] ?? ''));

if ($me <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'המשתמש לא מחובר'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($to <= 0 || $to === $me) {
    echo json_encode([
        'ok' => false,
        'message' => 'נמען לא תקין'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($text === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'אי אפשר לשלוח הודעה ריקה'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $checkStmt = $pdo->prepare("
        SELECT Id
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $checkStmt->execute([':id' => $to]);

    if (!$checkStmt->fetchColumn()) {
        echo json_encode([
            'ok' => false,
            'message' => 'המשתמש לא קיים'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`, Deleted_By_Id, Deleted_By_ById)
        VALUES (:to, :me, NOW(), :txt, 1, 0, 0)
    ");

    $stmt->execute([
        ':to' => $to,
        ':me' => $me,
        ':txt' => $text
    ]);

    $deleteTypingStmt = $pdo->prepare("
        DELETE FROM message_typing
        WHERE user_id = :user_id
          AND target_id = :target_id
    ");
    $deleteTypingStmt->execute([
        ':user_id' => $me,
        ':target_id' => $to
    ]);

    echo json_encode([
        'ok' => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'שגיאת שרת'
    ], JSON_UNESCAPED_UNICODE);
}
