<?php
// ===== FILE: chat.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$me = (int)$_SESSION['user_id'];
$otherId = (int)($_GET['user_id'] ?? 0);

if ($otherId <= 0 || $otherId === $me) {
    echo "<div class='page-shell'>שיחה לא תקינה</div>";
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function get_profile_image(PDO $pdo, int $userId): string {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $picName = $stmt->fetchColumn();

        if ($picName) {
            return '/uploads/' . ltrim((string)$picName, '/');
        }
    } catch (Throwable $e) {
        // ignore
    }

    return '/images/no_photo.jpg';
}

/* ===== שליפת המשתמש השני ===== */
$stmt = $pdo->prepare("
    SELECT Id, Name
    FROM users_profile
    WHERE Id = :id
    LIMIT 1
");
$stmt->execute([':id' => $otherId]);
$otherUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otherUser) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    exit;
}

/* ===== סימון הודעות שהתקבלו כנקראו ===== */
try {
    $markReadStmt = $pdo->prepare("
        UPDATE messages
        SET `New` = 0
        WHERE Id = :me
          AND ById = :otherId
          AND `New` = 1
    ");
    $markReadStmt->execute([
        ':me' => $me,
        ':otherId' => $otherId
    ]);
} catch (Throwable $e) {
    // ignore
}

/* ===== שליפת הודעות ===== */
$stmt = $pdo->prepare("
    SELECT Msg_Num, Id, ById, Date_Sent, Msg_Txt, `New`
    FROM messages
    WHERE (
            Id = :me
        AND ById = :otherId
        AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
    )
       OR (
            Id = :otherId
        AND ById = :me
        AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
    )
    ORDER BY Date_Sent ASC, Msg_Num ASC
");
$stmt->execute([
    ':me' => $me,
    ':otherId' => $otherId
]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$otherName = trim((string)($otherUser['Name'] ?? 'משתמש'));
$otherImage = get_profile_image($pdo, $otherId);
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>צ'אט עם <?= h($otherName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            color: #222;
        }

        .page-shell.chat-page-shell {
            max-width: 1100px;
            margin: 26px auto;
            padding: 20px;
            background: #fff;
            border-radius: 22px;
            box-sizing: border-box;
        }

        .chat-page-wrap {
            display: flex;
            flex-direction: column;
            min-height: 78vh;
        }

        .chat-topbar {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid #ececec;
            margin-bottom: 18px;
        }

        .chat-topbar-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f2f2f2;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }

        .chat-topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .chat-topbar-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .chat-topbar-name {
            font-size: 24px;
            font-weight: 700;
            color: #222;
        }

        .chat-messages-box {
            flex: 1;
            background: #f7f7f8;
            border: 1px solid #ececec;
            border-radius: 18px;
            padding: 16px;
            overflow-y: auto;
            min-height: 420px;
            max-height: 62vh;
        }

        .chat-empty {
            text-align: center;
            color: #777;
            padding: 40px 10px;
            font-size: 16px;
        }

        .chat-row {
            display: flex;
            margin-bottom: 12px;
        }

        .chat-row.me {
            justify-content: flex-start;
        }

        .chat-row.other {
            justify-content: flex-end;
        }

        .chat-bubble-wrap {
            max-width: 72%;
        }

        .chat-bubble {
            padding: 12px 14px;
            border-radius: 18px;
            line-height: 1.6;
            word-break: break-word;
            white-space: pre-wrap;
            font-size: 15px;
        }

        .chat-row.me .chat-bubble {
            background: #e5e7eb;
            color: #222;
            border-bottom-left-radius: 6px;
        }

        .chat-row.other .chat-bubble {
            background: #ffd7e2;
            color: #222;
            border-bottom-right-radius: 6px;
        }

        .chat-time {
            margin-top: 4px;
            font-size: 12px;
            color: #888;
        }

        .chat-row.me .chat-time {
            text-align: right;
        }

        .chat-row.other .chat-time {
            text-align: left;
        }

        .chat-compose {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            margin-top: 16px;
        }

        .chat-textarea {
            flex: 1;
            min-height: 58px;
            max-height: 180px;
            resize: vertical;
            border: 1px solid #dcdcdc;
            border-radius: 16px;
            padding: 14px;
            font-size: 15px;
            font-family: Arial, sans-serif;
            outline: none;
            box-sizing: border-box;
        }

        .chat-send-btn {
            min-width: 120px;
            height: 58px;
            border: none;
            border-radius: 16px;
            background: #d91f4f;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .chat-send-btn:disabled {
            opacity: 0.65;
            cursor: default;
        }

        .chat-status {
            margin-top: 10px;
            min-height: 20px;
            color: #d91f4f;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .page-shell.chat-page-shell {
                margin: 12px;
                padding: 14px;
                border-radius: 16px;
            }

            .chat-bubble-wrap {
                max-width: 88%;
            }

            .chat-compose {
                flex-direction: column;
                align-items: stretch;
            }

            .chat-send-btn {
                width: 100%;
            }

            .chat-topbar-name {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="page-shell chat-page-shell">
        <div class="chat-page-wrap">

            <div class="chat-topbar">
                <a href="/?page=messages" class="chat-topbar-back">חזרה להודעות</a>

                <div class="chat-topbar-user">
                    <img src="<?= h($otherImage) ?>" alt="<?= h($otherName) ?>" class="chat-topbar-avatar">
                    <div class="chat-topbar-name"><?= h($otherName) ?></div>
                </div>
            </div>

            <div class="chat-messages-box" id="chatMessagesBox">
                <?php if (!$messages): ?>
                    <div class="chat-empty">אין עדיין הודעות. אפשר להתחיל 🙂</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isMe = ((int)$msg['ById'] === $me);
                        $time = '';
                        if (!empty($msg['Date_Sent'])) {
                            try {
                                $time = date('d/m/Y H:i', strtotime((string)$msg['Date_Sent']));
                            } catch (Throwable $e) {
                                $time = '';
                            }
                        }
                        ?>
                        <div class="chat-row <?= $isMe ? 'me' : 'other' ?>">
                            <div class="chat-bubble-wrap">
                                <div class="chat-bubble"><?= nl2br(h($msg['Msg_Txt'] ?? '')) ?></div>
                                <?php if ($time !== ''): ?>
                                    <div class="chat-time"><?= h($time) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-compose">
                <textarea id="chatText" class="chat-textarea" placeholder="כתוב הודעה..."></textarea>
                <button id="chatSendBtn" class="chat-send-btn" type="button">שלח</button>
            </div>

            <div class="chat-status" id="chatStatus"></div>

        </div>
    </div>

    <script>
        (function() {
            const messagesBox = document.getElementById('chatMessagesBox');
            const textBox = document.getElementById('chatText');
            const sendBtn = document.getElementById('chatSendBtn');
            const statusBox = document.getElementById('chatStatus');
            const otherUserId = <?= (int)$otherId ?>;

            function scrollToBottom() {
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            scrollToBottom();

            function sendMessage() {
                const text = textBox.value.trim();
                if (!text) {
                    return;
                }

                sendBtn.disabled = true;
                statusBox.textContent = 'שולח...';

                const formData = new FormData();
                formData.append('to', otherUserId);
                formData.append('text', text);

                fetch('/send_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (!data.ok) {
                            statusBox.textContent = data.message || 'שליחת ההודעה נכשלה';
                            sendBtn.disabled = false;
                            return;
                        }

                        window.location.reload();
                    })
                    .catch(function() {
                        statusBox.textContent = 'שגיאת תקשורת';
                        sendBtn.disabled = false;
                    });
            }

            sendBtn.addEventListener('click', sendMessage);

            textBox.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        })();
    </script>

</body>

</html>