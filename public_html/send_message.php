<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false]);
    exit;
}

$from = (int)$_SESSION['user_id'];
$to   = (int)($_POST['to'] ?? 0);
$msg  = trim($_POST['msg'] ?? '');

if (!$to || $msg === '') {
    echo json_encode(['ok'=>false]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, New)
    VALUES (:to, :from, NOW(), :msg, 1)
");

$stmt->execute([
    ':to' => $to,
    ':from' => $from,
    ':msg' => $msg
]);

echo json_encode(['ok'=>true]);