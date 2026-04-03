<?php
// ===== FILE: get_messages.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$me = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$other = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

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

/* שמות משתמשים */
$names = [];

$nameStmt = $pdo->prepare("
    SELECT Id, Name
    FROM users_profile
    WHERE Id = :id1 OR Id = :id2
");
$nameStmt->execute([
    ':id1' => $me,
    ':id2' => $other
]);

foreach ($nameStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $names[(int)$row['Id']] = trim((string)($row['Name'] ?? ''));
}

/* טעינה ראשונה: כל ההיסטוריה | רענון: רק הודעות חדשות */
if ($lastId <= 0) {
    $msgStmt = $pdo->prepare("
        SELECT Msg_Num, Id, ById, Date_Sent, Msg_Txt
        FROM messages
        WHERE
            (
                Id = :me
                AND ById = :other
                AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
            )
            OR
            (
                Id = :other
                AND ById = :me
                AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
            )
        ORDER BY Date_Sent ASC, Msg_Num ASC
    ");

    $msgStmt->execute([
        ':me' => $me,
        ':other' => $other
    ]);
} else {
    $msgStmt = $pdo->prepare("
        SELECT Msg_Num, Id, ById, Date_Sent, Msg_Txt
        FROM messages
        WHERE
            Msg_Num > :last_id
            AND
            (
                (
                    Id = :me
                    AND ById = :other
                    AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
                )
                OR
                (
                    Id = :other
                    AND ById = :me
                    AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
                )
            )
        ORDER BY Date_Sent ASC, Msg_Num ASC
    ");

    $msgStmt->execute([
        ':last_id' => $lastId,
        ':me' => $me,
        ':other' => $other
    ]);
}

$messages = [];
$maxMessageId = $lastId;

while ($row = $msgStmt->fetch(PDO::FETCH_ASSOC)) {
    $messageId = (int)$row['Msg_Num'];
    $senderId = (int)$row['ById'];
    $isMe = $senderId === $me;

    if ($messageId > $maxMessageId) {
        $maxMessageId = $messageId;
    }

    $dateSent = '';
    if (!empty($row['Date_Sent'])) {
        $ts = strtotime((string)$row['Date_Sent']);
        if ($ts) {
            $dateSent = date('d/m/Y H:i', $ts);
        }
    }

    $messages[] = [
        'id' => $messageId,
        'is_me' => $isMe,
        'sender_id' => $senderId,
        'sender_name' => $names[$senderId] ?? ($isMe ? 'אני' : 'משתמש'),
        'text' => htmlspecialchars((string)($row['Msg_Txt'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'date_sent' => $dateSent
    ];
}

echo json_encode([
    'ok' => true,
    'messages' => $messages,
    'last_id' => $maxMessageId
]);