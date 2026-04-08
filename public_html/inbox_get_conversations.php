<?php

/**
 * inbox_get_conversations.php
 * מחזיר רשימת שיחות למשתמש כולל:
 * שם, תמונה, הודעה אחרונה, זמן, וכמות הודעות שלא נקראו
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $me = (int)$_SESSION['user_id'];

    $sql = "
    SELECT
        t.other_user_id AS user_id,
        up.Name,

        (
            SELECT m2.Msg_Txt
            FROM messages m2
            WHERE
                (
                    m2.ById = :me
                    AND m2.Id = t.other_user_id
                    AND (m2.Deleted_By_ById = 0 OR m2.Deleted_By_ById IS NULL)
                )
                OR
                (
                    m2.ById = t.other_user_id
                    AND m2.Id = :me
                    AND (m2.Deleted_By_Id = 0 OR m2.Deleted_By_Id IS NULL)
                )
            ORDER BY m2.Date_Sent DESC, m2.Msg_Num DESC
            LIMIT 1
        ) AS last_msg,

        (
            SELECT m3.Date_Sent
            FROM messages m3
            WHERE
                (
                    m3.ById = :me
                    AND m3.Id = t.other_user_id
                    AND (m3.Deleted_By_ById = 0 OR m3.Deleted_By_ById IS NULL)
                )
                OR
                (
                    m3.ById = t.other_user_id
                    AND m3.Id = :me
                    AND (m3.Deleted_By_Id = 0 OR m3.Deleted_By_Id IS NULL)
                )
            ORDER BY m3.Date_Sent DESC, m3.Msg_Num DESC
            LIMIT 1
        ) AS last_date,

        (
            SELECT COUNT(*)
            FROM messages m4
            WHERE
                m4.ById = t.other_user_id
                AND m4.Id = :me
                AND m4.New = 1
                AND (m4.Deleted_By_Id = 0 OR m4.Deleted_By_Id IS NULL)
        ) AS unread_count

    FROM (
        SELECT DISTINCT
            CASE
                WHEN ById = :me THEN Id
                ELSE ById
            END AS other_user_id
        FROM messages
        WHERE ById = :me OR Id = :me
    ) t
    LEFT JOIN users_profile up
        ON up.Id = t.other_user_id
    WHERE NOT EXISTS (
        SELECT 1
        FROM blocked_users bu
        WHERE (bu.Id = t.other_user_id AND bu.Blocked_ById = :me)
           OR (bu.Id = :me AND bu.Blocked_ById = t.other_user_id)
    )
    ORDER BY last_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':me' => $me]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo '<div class="inbox-empty">אין שיחות עדיין</div>';
        exit;
    }

    foreach ($rows as $row) {
        $userId = (int)$row['user_id'];

        $displayNameRaw = trim((string)($row['Name'] ?: 'משתמש'));
        $displayName = htmlspecialchars($displayNameRaw, ENT_QUOTES, 'UTF-8');

        $lastMsgRaw = trim((string)($row['last_msg'] ?? ''));
        $lastMsgShort = $lastMsgRaw !== '' ? mb_strimwidth($lastMsgRaw, 0, 28, '...') : '';
        $lastMsg = htmlspecialchars($lastMsgShort, ENT_QUOTES, 'UTF-8');

        $dateText = '';
        if (!empty($row['last_date'])) {
            $dateText = date('d/m/Y', strtotime($row['last_date']));
        }

        $unread = (int)$row['unread_count'];
        $unreadClass = $unread > 0 ? ' inbox-unread' : '';

        $avatar = '/images/no_photo.jpg';

        $picStmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Pic_Status = 1
            ORDER BY Main_Pic DESC, Pic_Num ASC
            LIMIT 1
        ");
        $picStmt->execute([':id' => $userId]);
        $pic = $picStmt->fetchColumn();

        if ($pic) {
            $avatar = '/uploads/' . ltrim((string)$pic, '/');
        }

        $avatarHtml = htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8');
        $dateHtml = htmlspecialchars($dateText, ENT_QUOTES, 'UTF-8');

        echo "
        <div
            class='inbox-conversation-item{$unreadClass}'
            data-user-id='{$userId}'
            data-name='{$displayName}'
        >
            <div class='inbox-conversation-date'>{$dateHtml}</div>

            <div class='inbox-conversation-main'>
                <a href='/?page=profile&id={$userId}' onclick='event.stopPropagation()'>
                    <img src='{$avatarHtml}' alt='{$displayName}' class='inbox-conversation-avatar'>
                </a>

                <div class='inbox-conversation-content'>
                    <div class='inbox-conversation-name'>{$displayName}</div>
                    <div class='inbox-conversation-preview'>{$lastMsg}</div>
                </div>
            </div>
        </div>
        ";
    }
} catch (Throwable $e) {
    echo '<pre style="direction:ltr;text-align:left;background:#fff0f0;color:#900;padding:12px;border:1px solid #f5b5b5;">';
    echo "ERROR in inbox_get_conversations.php\n\n";
    echo $e->getMessage() . "\n\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}
