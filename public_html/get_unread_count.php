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
    SELECT 
        m.ById,
        COUNT(*) AS cnt,
        up.Name,
        (
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = m.ById
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ) AS Pic_Name
    FROM messages m
    LEFT JOIN users_profile up ON up.Id = m.ById
    WHERE m.Id = :me
      AND m.`New` = 1
      AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL)
    GROUP BY m.ById, up.Name
");
$stmt->execute([
    ':me' => $me
]);

$total = 0;
$byUser = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userId = (int)$row['ById'];
    $cnt = (int)$row['cnt'];

    $img = '/images/no_photo.jpg';
    if (!empty($row['Pic_Name'])) {
        $img = '/upload/' . $row['Pic_Name'];
    }

    $byUser[$userId] = [
        'count' => $cnt,
        'name' => trim((string)($row['Name'] ?? 'משתמש')),
        'image' => $img
    ];

    $total += $cnt;
}

echo json_encode([
    'ok' => true,
    'count' => $total,
    'by_user' => $byUser
]);