<?php
// messages.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| טעינת config - עם fallback לכמה מבנים אפשריים
|--------------------------------------------------------------------------
*/
$configLoaded = false;

$possibleConfigs = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/includes/config/config.php',
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../includes/config/config.php',
];

foreach ($possibleConfigs as $configFile) {
    if (file_exists($configFile)) {
        require_once $configFile;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded || !isset($pdo)) {
    echo '<div style="padding:20px;color:red;font-family:Arial">שגיאה: לא נמצא config.php או שאין \$pdo פעיל.</div>';
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$otherUserId   = (int)($_GET['user_id'] ?? 0);

if ($currentUserId <= 0) {
    echo '<div style="padding:20px;font-family:Arial">יש להתחבר כדי לצפות בהודעות.</div>';
    exit;
}

/*
|--------------------------------------------------------------------------
| סימון הודעות כנקראו
|--------------------------------------------------------------------------
*/
if ($otherUserId > 0) {
    $stmt = $pdo->prepare("
        UPDATE messages
        SET `New` = 0
        WHERE Id = :me
          AND ById = :other
          AND `New` = 1
    ");
    $stmt->execute([
        ':me'    => $currentUserId,
        ':other' => $otherUserId
    ]);
}

/*
|--------------------------------------------------------------------------
| שליחת הודעה
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $otherUserId > 0) {
    $text = trim($_POST['message']);

    if ($text !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`)
            VALUES (:to_id, :from_id, NOW(), :msg_txt, 1)
        ");
        $stmt->execute([
            ':to_id'   => $otherUserId,
            ':from_id' => $currentUserId,
            ':msg_txt' => $text
        ]);

        header('Location: /?page=messages&user_id=' . $otherUserId);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| רשימת שיחות - רק משתמשים שהיה איתם קשר בהודעות
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        u.Id,
        u.User_Name,
        u.Image_Main,
        (
            SELECT COUNT(*)
            FROM messages m3
            WHERE m3.Id = :me_count
              AND m3.ById = u.Id
              AND m3.`New` = 1
              AND m3.Deleted_By_Id = 0
        ) AS unread_count
    FROM users_profile u
    WHERE u.Id != :me_user
      AND u.Id IN (
          SELECT Id FROM messages WHERE ById = :me_sent
          UNION
          SELECT ById FROM messages WHERE Id = :me_received
      )
    ORDER BY unread_count DESC, u.User_Name ASC
");
$stmt->execute([
    ':me_count'    => $currentUserId,
    ':me_user'     => $currentUserId,
    ':me_sent'     => $currentUserId,
    ':me_received' => $currentUserId
]);
$chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| הודעה אחרונה לכל משתמש
|--------------------------------------------------------------------------
*/
$lastMessages = [];

foreach ($chatUsers as $chatUser) {
    $stmt = $pdo->prepare("
        SELECT Msg_Txt
        FROM messages
        WHERE 
            (Id = :me1 AND ById = :other1)
            OR
            (Id = :other2 AND ById = :me2)
        ORDER BY Msg_Num DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':me1'    => $currentUserId,
        ':other1' => $chatUser['Id'],
        ':other2' => $chatUser['Id'],
        ':me2'    => $currentUserId
    ]);
    $lastMessages[$chatUser['Id']] = (string)($stmt->fetchColumn() ?: '');
}

/*
|--------------------------------------------------------------------------
| טעינת ההודעות בצ'אט הפתוח
|--------------------------------------------------------------------------
*/
$messages = [];

if ($otherUserId > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM messages
        WHERE 
            (Id = :me1 AND ById = :other1)
            OR
            (Id = :other2 AND ById = :me2)
        ORDER BY Msg_Num ASC
    ");
    $stmt->execute([
        ':me1'    => $currentUserId,
        ':other1' => $otherUserId,
        ':other2' => $otherUserId,
        ':me2'    => $currentUserId
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| פרטי המשתמש של הצ'אט הפתוח
|--------------------------------------------------------------------------
*/
$otherUser = null;

if ($otherUserId > 0) {
    $stmt = $pdo->prepare("
        SELECT Id, User_Name, Image_Main
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $otherUserId]);
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    .messages-page {
        max-width: 1200px;
        margin: 24px auto;
        display: flex;
        gap: 20px;
        direction: rtl;
    }

    .messages-sidebar {
        width: 340px;
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #ececec;
    }

    .messages-sidebar-title {
        padding: 16px 18px;
        font-size: 20px;
        font-weight: 700;
        border-bottom: 1px solid #f0f0f0;
    }

    .chat-user-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #f4f4f4;
        text-decoration: none;
        color: inherit;
        background: #fff;
    }

    .chat-user-row:hover {
        background: #fafafa;
    }

    .chat-user-row.active {
        background: #fff4f6;
    }

    .chat-user-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        background: #eee;
        flex-shrink: 0;
    }

    .chat-user-main {
        flex: 1;
        min-width: 0;
    }

    .chat-user-name {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .chat-user-last {
        font-size: 12px;
        color: #777;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-user-badge {
        min-width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #d9234f;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        padding: 0 7px;
    }

    .messages-chat {
        flex: 1;
        background: #fff;
        border-radius: 18px;
        border: 1px solid #ececec;
        display: flex;
        flex-direction: column;
        min-height: 600px;
    }

    .messages-chat-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid #f0f0f0;
    }

    .messages-chat-header img {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        object-fit: cover;
        background: #eee;
    }

    .messages-chat-title {
        font-size: 18px;
        font-weight: 700;
    }

    .messages-chat-body {
        flex: 1;
        padding: 18px;
        background: #fcfcfc;
        overflow-y: auto;
    }

    .chat-empty {
        color: #777;
        font-size: 15px;
    }

    .msg-row {
        display: flex;
        margin-bottom: 12px;
    }

    .msg-row.me {
        justify-content: flex-start;
    }

    .msg-row.other {
        justify-content: flex-end;
    }

    .msg-bubble {
        max-width: 70%;
        padding: 10px 14px;
        border-radius: 16px;
        line-height: 1.5;
        font-size: 14px;
        word-break: break-word;
    }

    .msg-row.me .msg-bubble {
        background: #d9234f;
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .msg-row.other .msg-bubble {
        background: #ededed;
        color: #222;
        border-bottom-left-radius: 4px;
    }

    .messages-chat-form {
        border-top: 1px solid #f0f0f0;
        padding: 14px;
        display: flex;
        gap: 10px;
    }

    .messages-chat-form textarea {
        flex: 1;
        min-height: 56px;
        max-height: 120px;
        resize: vertical;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 12px;
        font-family: inherit;
        font-size: 14px;
        outline: none;
    }

    .messages-chat-form button {
        width: 110px;
        border: none;
        border-radius: 12px;
        background: #d9234f;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
    }

    .messages-chat-form button:hover {
        opacity: 0.95;
    }

    @media (max-width: 900px) {
        .messages-page {
            flex-direction: column;
        }

        .messages-sidebar {
            width: 100%;
        }

        .messages-chat {
            min-height: 500px;
        }

        .msg-bubble {
            max-width: 88%;
        }
    }
</style>

<div class="messages-page">

    <div class="messages-sidebar">
        <div class="messages-sidebar-title">הודעות</div>

        <?php if (!empty($chatUsers)): ?>
            <?php foreach ($chatUsers as $chatUser): ?>
                <?php
                $img = trim((string)($chatUser['Image_Main'] ?? ''));
                if ($img === '') {
                    $img = 'images/no-photo.jpg';
                }
                ?>
                <a
                    class="chat-user-row <?= ($otherUserId === (int)$chatUser['Id']) ? 'active' : '' ?>"
                    href="/?page=messages&user_id=<?= (int)$chatUser['Id'] ?>">
                    <img class="chat-user-avatar" src="/<?= htmlspecialchars($img) ?>" alt="">
                    <div class="chat-user-main">
                        <div class="chat-user-name"><?= htmlspecialchars($chatUser['User_Name'] ?? '') ?></div>
                        <div class="chat-user-last"><?= htmlspecialchars($lastMessages[$chatUser['Id']] ?? '') ?></div>
                    </div>

                    <?php if ((int)$chatUser['unread_count'] > 0): ?>
                        <span class="chat-user-badge"><?= (int)$chatUser['unread_count'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding:16px;color:#777;">אין עדיין שיחות.</div>
        <?php endif; ?>
    </div>

    <div class="messages-chat">
        <?php if ($otherUser): ?>
            <?php
            $headerImg = trim((string)($otherUser['Image_Main'] ?? ''));
            if ($headerImg === '') {
                $headerImg = 'images/no-photo.jpg';
            }
            ?>
            <div class="messages-chat-header">
                <img src="/<?= htmlspecialchars($headerImg) ?>" alt="">
                <div class="messages-chat-title"><?= htmlspecialchars($otherUser['User_Name'] ?? '') ?></div>
            </div>

            <div class="messages-chat-body" id="chatBody">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="msg-row <?= ((int)$msg['ById'] === $currentUserId) ? 'me' : 'other' ?>">
                            <div class="msg-bubble">
                                <?= nl2br(htmlspecialchars($msg['Msg_Txt'] ?? '')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="chat-empty">אין עדיין הודעות בשיחה הזאת.</div>
                <?php endif; ?>
            </div>

            <form class="messages-chat-form" method="POST" action="/?page=messages&user_id=<?= (int)$otherUserId ?>">
                <textarea name="message" placeholder="כתוב הודעה..."></textarea>
                <button type="submit">שלח</button>
            </form>
        <?php else: ?>
            <div class="messages-chat-body">
                <div class="chat-empty">בחר שיחה מהרשימה.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        var chatBody = document.getElementById('chatBody');
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    })();
</script>