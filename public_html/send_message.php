<?php
// ===== FILE: send_message.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$fromId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$toId   = isset($_POST['to_id']) ? (int)$_POST['to_id'] : 0;
$msgTxt = isset($_POST['message']) ? trim((string)$_POST['message']) : '';

if ($fromId <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'יש להתחבר כדי לשלוח הודעה'
    ]);
    exit;
}

if ($toId <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'נמען לא תקין'
    ]);
    exit;
}

if ($fromId === $toId) {
    echo json_encode([
        'ok' => false,
        'message' => 'לא ניתן לשלוח הודעה לעצמך'
    ]);
    exit;
}

if ($msgTxt === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'יש לכתוב הודעה'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            Id,
            ById,
            Date_Sent,
            Msg_Txt,
            `New`,
            Deleted_By_Id,
            Deleted_By_ById
        )
        VALUES (
            :to_id,
            :from_id,
            NOW(),
            :msg_txt,
            1,
            0,
            0
        )
    ");

    $stmt->execute([
        ':to_id'   => $toId,
        ':from_id' => $fromId,
        ':msg_txt' => $msgTxt
    ]);

    echo json_encode([
        'ok' => true
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'שגיאה בשמירת ההודעה'
    ]);
}