<?php
// ===== FILE: get_views_count.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$me = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($me <= 0) {
    echo json_encode([
        'ok' => false,
        'count' => 0
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM views
    WHERE Id = :me
      AND New = 1
      AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
");
$stmt->execute([
    ':me' => $me
]);

$count = (int)$stmt->fetchColumn();

echo json_encode([
    'ok' => true,
    'count' => $count
]);