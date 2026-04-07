<?php

/**
 * inbox_send_message.php
 * שולח הודעה חדשה למשתמש:
 * מקבל to_user_id וטקסט, ושומר בטבלת messages עם New=1
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    if (!isset($pdo)) {
        throw new Exception('PDO connection is missing');
    }

    $me  = (int)$_SESSION['user_id'];
    $to  = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
    $msg = trim($_POST['message'] ?? '');

    if ($to <= 0) {
        throw new Exception('Invalid to_user_id');
    }

    if ($msg === '') {
        throw new Exception('Empty message');
    }

    $msg = mb_substr($msg, 0, 2000);

    $sql = "
    INSERT INTO messages
    (Id, ById, Date_Sent, Msg_Txt, New, Deleted_By_Id, Deleted_By_ById)
    VALUES
    (:to_id, :from_id, NOW(), :msg, 1, 0, 0)
    ";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':to_id'   => $to,
        ':from_id' => $me,
        ':msg'     => $msg
    ]);

    if (!$ok) {
        throw new Exception('Insert failed');
    }

    echo "OK";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR in inbox_send_message.php\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
