<?php
/* =========================
   get_header_counts.php
   ========================= */

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo json_encode([
        'session_user_id' => null,
        'views' => 0,
        'messages' => 0
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ById)
        FROM views
        WHERE Id = :id
          AND `New` = 1
          AND (Deleted_By_Id IS NULL OR Deleted_By_Id = 0)
    ");
    $stmt->execute([':id' => $userId]);
    $views = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $views = 0;
}

echo json_encode([
    'session_user_id' => $userId,
    'views' => $views,
    'messages' => 0
]);