<?php

/**
 * inbox_get_messages.php
 * מחזיר את כל ההודעות בין המשתמש המחובר למשתמש אחר
 * כולל שם שולח, שעה וסטטוס "נקרא"
 */

session_start();
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $me = (int)$_SESSION['user_id'];
    $other = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($other <= 0) {
        throw new Exception('No user_id received');
    }

    $sql = "
    SELECT 
        m.*,
        up.Name AS sender_name
    FROM messages m
    LEFT JOIN users_profile up
        ON up.Id = m.ById
    WHERE
    (
        m.ById = :me
        AND m.Id = :other
        AND (m.Deleted_By_ById = 0 OR m.Deleted_By_ById IS NULL)
    )
    OR
    (
        m.ById = :other
        AND m.Id = :me
        AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL)
    )
    ORDER BY m.Date_Sent ASC, m.Msg_Num ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':me'    => $me,
        ':other' => $other
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo '<div class="inbox-empty">אין הודעות</div>';
        exit;
    }

    foreach ($rows as $msg) {
        $isMe = ((int)$msg['ById'] === $me);

        $text = nl2br(htmlspecialchars((string)($msg['Msg_Txt'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $time = !empty($msg['Date_Sent']) ? date('d/m/Y H:i', strtotime($msg['Date_Sent'])) : '';
        $senderName = htmlspecialchars((string)($msg['sender_name'] ?? 'משתמש'), ENT_QUOTES, 'UTF-8');

        $rowClass = $isMe ? 'inbox-message-row inbox-message-row-me' : 'inbox-message-row inbox-message-row-other';
        $bubbleClass = $isMe ? 'inbox-message inbox-message-me' : 'inbox-message inbox-message-other';

        echo "<div class='{$rowClass}'>";
        echo "  <div class='{$bubbleClass}'>";
        echo "      <div class='inbox-message-sender'>{$senderName}</div>";
        echo "      <div class='inbox-message-text'>{$text}</div>";
        echo "      <div class='inbox-message-meta'>";
        echo "          <span class='inbox-time'>{$time}</span>";

        if ($isMe && (int)$msg['New'] === 0) {
            echo "          <span class='inbox-read'>נקרא</span>";
        }

        echo "      </div>";
        echo "  </div>";
        echo "</div>";
    }
} catch (Throwable $e) {
    echo '<div class="inbox-empty">שגיאה בטעינת הודעות</div>';
}
