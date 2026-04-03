<?php
// ===== FILE: mark_messages_read.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$me = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$other = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($me <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'לא מחובר'
    ]);
    exit;
}

if ($other <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'משתמש לא תקין'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE messages
    SET `New` = 0
    WHERE Id = :me
      AND ById = :other
      AND `New` = 1
      AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
");

$stmt->execute([
    ':me' => $me,
    ':other' => $other
]);

echo json_encode([
    'ok' => true
]);