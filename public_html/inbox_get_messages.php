<?php
// ===== inbox_get_messages.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

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
    $other = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($other <= 0) {
        throw new Exception('No user_id received');
    }

    $sql = "
        SELECT
            m.*,
            up.Name AS sender_name,
            pic.Pic_Name AS profile_image
        FROM messages m

        LEFT JOIN users_profile up
            ON up.Id = m.ById

        LEFT JOIN user_pics pic
            ON pic.Id = m.ById
            AND pic.Main_Pic = 1

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

    $lastDay = '';

    foreach ($rows as $msg) {

        $isMe = ((int)$msg['ById'] === $me);

        $rawText = (string)($msg['Msg_Txt'] ?? '');
        $text = nl2br(h($rawText));

        $image = !empty($msg['profile_image'])
            ? '/uploads/' . $msg['profile_image']
            : '/images/default.png';

        $dateSent = (string)($msg['Date_Sent'] ?? '');
        $ts = $dateSent !== '' ? strtotime($dateSent) : false;

        $time = '';
        $fullDate = '';
        $dayKey = '';
        $dayLabel = '';

        if ($ts) {
            $fullDate = date('d/m/Y H:i', $ts);
            $time = date('H:i', $ts);
            $dayKey = date('Y-m-d', $ts);

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            if ($dayKey === $today) {
                $dayLabel = 'היום';
            } elseif ($dayKey === $yesterday) {
                $dayLabel = 'אתמול';
            } else {
                $dayLabel = date('d/m/Y', $ts);
            }
        }

        if ($dayKey !== '' && $dayKey !== $lastDay) {
            echo "<div class='inbox-day-separator'>{$dayLabel}</div>";
            $lastDay = $dayKey;
        }

        $rowClass = $isMe
            ? 'inbox-message-row inbox-message-row-me'
            : 'inbox-message-row inbox-message-row-other';

        $bubbleClass = $isMe
            ? 'inbox-message inbox-message-me'
            : 'inbox-message inbox-message-other';

        $readHtml = '';
        if ($isMe) {
            if ((int)($msg['New'] ?? 1) === 0) {
                $readHtml = "<span class='inbox-read inbox-read-seen'>✓✓</span>";
            } else {
                $readHtml = "<span class='inbox-read inbox-read-sent'>✓</span>";
            }
        }

        $fullDateHtml = h($fullDate);

        if ($isMe) {
            // אתה - לא נוגע (כמו שהיה)
            echo "
            <div class='{$rowClass}'>

                <img src='" . h($image) . "' class='inbox-message-avatar'>

                <div class='{$bubbleClass}'>
                    <div class='inbox-message-text'>{$text}</div>
                    <div class='inbox-message-meta'>
                        <span class='inbox-time' title='{$fullDateHtml}'>{$time}</span>
                        {$readHtml}
                    </div>
                </div>

            </div>
            ";
        } else {
            // הצד השני - מתוקן
            echo "
            <div class='{$rowClass}'>

                <div class='{$bubbleClass}'>
                    <div class='inbox-message-text'>{$text}</div>
                    <div class='inbox-message-meta'>
                        <span class='inbox-time' title='{$fullDateHtml}'>{$time}</span>
                    </div>
                </div>

                <img src='" . h($image) . "' class='inbox-message-avatar'>

            </div>
            ";
        }
    }
} catch (Throwable $e) {
    echo '<div class="inbox-empty">שגיאה בטעינת הודעות</div>';
}
