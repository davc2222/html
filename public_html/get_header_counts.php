<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        'messages' => 0,
        'views' => 0
    ]);
    exit;
}

// ==========================
// הודעות שלא נקראו
// ==========================
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM messages
    WHERE Id = :me
      AND `New` = 1
      AND Deleted_By_Id = 0
");
$stmt->execute([':me' => $userId]);
$messages = (int)$stmt->fetchColumn();

// ==========================
// צפיות שלא נקראו
// ==========================
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM views
    WHERE Id = :me
      AND `New` = 1
      AND Deleted_By_Id = 0
");
$stmt->execute([':me' => $userId]);
$views = (int)$stmt->fetchColumn();

echo json_encode([
    'messages' => $messages,
    'views' => $views
]);
