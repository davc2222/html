<?php

/**
 * FILE: /mobile/inbox_get_conversations.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $me = (int)$_SESSION['user_id'];

    $sql = "
        SELECT
            t.other_user_id AS user_id,
            up.Name,
            up.Gender_Id,

            (
                SELECT m2.Msg_Txt
                FROM messages m2
                WHERE
                    (
                        m2.ById = :me_a
                        AND m2.Id = t.other_user_id
                        AND (m2.Deleted_By_ById = 0 OR m2.Deleted_By_ById IS NULL)
                    )
                    OR
                    (
                        m2.ById = t.other_user_id
                        AND m2.Id = :me_b
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
                        m3.ById = :me_c
                        AND m3.Id = t.other_user_id
                        AND (m3.Deleted_By_ById = 0 OR m3.Deleted_By_ById IS NULL)
                    )
                    OR
                    (
                        m3.ById = t.other_user_id
                        AND m3.Id = :me_d
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
                    AND m4.Id = :me_e
                    AND m4.New = 1
                    AND (m4.Deleted_By_Id = 0 OR m4.Deleted_By_Id IS NULL)
            ) AS unread_count

        FROM (
            SELECT DISTINCT
                CASE
                    WHEN ById = :me_f THEN Id
                    ELSE ById
                END AS other_user_id
            FROM messages
            WHERE ById = :me_g OR Id = :me_h
        ) t

        LEFT JOIN users_profile up
            ON up.Id = t.other_user_id

        WHERE NOT EXISTS (
            SELECT 1
            FROM blocked_users bu
            WHERE (bu.Id = t.other_user_id AND bu.Blocked_ById = :me_i)
               OR (bu.Id = :me_j AND bu.Blocked_ById = t.other_user_id)
        )

        ORDER BY last_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':me_a' => $me,
        ':me_b' => $me,
        ':me_c' => $me,
        ':me_d' => $me,
        ':me_e' => $me,
        ':me_f' => $me,
        ':me_g' => $me,
        ':me_h' => $me,
        ':me_i' => $me,
        ':me_j' => $me,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo '<div class="inbox-empty">אין שיחות עדיין</div>';
        exit;
    }

    foreach ($rows as $row) {
        $userId = (int)($row['user_id'] ?? 0);

        $displayNameRaw = trim((string)($row['Name'] ?? ''));
        if ($displayNameRaw === '') {
            $displayNameRaw = 'משתמש';
        }
        $displayName = h($displayNameRaw);

        $lastMsgRaw = trim((string)($row['last_msg'] ?? ''));
        $lastMsgShort = $lastMsgRaw !== '' ? mb_strimwidth($lastMsgRaw, 0, 28, '...') : '';
        $lastMsg = h($lastMsgShort);

        $dateText = '';
        if (!empty($row['last_date'])) {
            $ts = strtotime((string)$row['last_date']);
            if ($ts) {
                $dateText = date('d/m', $ts);
            }
        }
        $dateHtml = h($dateText);

        $unread = (int)($row['unread_count'] ?? 0);
        $unreadClass = $unread > 0 ? ' inbox-unread' : '';

        $gender = (int)($row['Gender_Id'] ?? 0);

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
        } else {
            $avatar = ($gender === 1)
                ? '/images/default_male.svg'
                : '/images/default_female.svg';
        }

        $fallback = ($gender === 1)
            ? '/images/default_male.svg'
            : '/images/default_female.svg';

        $avatarHtml = h($avatar);
        $fallbackHtml = h($fallback);

        $profileUrl = "/mobile/index.php?page=profile&id={$userId}";

        echo "
        <div
            class='inbox-conversation-item{$unreadClass}'
            data-user-id='{$userId}'
            data-name='{$displayName}'
        >
            <div class='inbox-conversation-date'>{$dateHtml}</div>

            <div class='inbox-conversation-main'>
                <a href='" . h($profileUrl) . "' onclick='event.stopPropagation()'>
                    <img
                        src='{$avatarHtml}'
                        alt='{$displayName}'
                        class='inbox-conversation-avatar'
                        onerror=\"this.onerror=null;this.src='{$fallbackHtml}';\"
                    >
                </a>

                <div class='inbox-conversation-content'>
                    <div class='inbox-conversation-name'>{$displayName}</div>
                    <div class='inbox-conversation-preview'>"
            . ($unread > 0 ? "<span class='inbox-unread-count'>({$unread})</span> " : "") .
            "{$lastMsg}
                    </div>
                </div>
            </div>
        </div>
        ";
    }
} catch (Throwable $e) {
    echo '<div class="inbox-error">אירעה שגיאה בטעינת השיחות</div>';
}
