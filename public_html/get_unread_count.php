<?php
// ===== FILE: get_unread_count.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$me = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($me <= 0) {
    echo json_encode([
        'ok' => false,
        'count' => 0,
        'by_user' => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT ById, COUNT(*) AS cnt
    FROM messages
    WHERE Id = :me
      AND `New` = 1
      AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
    GROUP BY ById
");
$stmt->execute([
    ':me' => $me
]);

$total = 0;
$byUser = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userId = (int)$row['ById'];
    $cnt = (int)$row['cnt'];

    $byUser[$userId] = $cnt;
    $total += $cnt;
}

echo json_encode([
    'ok' => true,
    'count' => $total,
    'by_user' => $byUser
]);