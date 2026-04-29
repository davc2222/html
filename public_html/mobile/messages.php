<?php
// ===== FILE: /mobile/messages.php =====
// LoveMatch Mobile Chat - single file, AJAX + fallback

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function jsonOut(array $data, int $code = 200): void {
    if (ob_get_length()) {
        @ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getChatUserImage(PDO $pdo, int $userId): string {
    $default = '/images/default_male.svg';

    try {
        $stmt = $pdo->prepare("SELECT Gender_Str FROM users_profile WHERE Id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $gender = trim((string)$stmt->fetchColumn());

        if ($gender === 'אישה') {
            $default = '/images/default_female.svg';
        }

        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = ?
              AND Pic_Status = 1
            ORDER BY Main_Pic DESC, Pic_Num ASC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $pic = $stmt->fetchColumn();

        if ($pic) {
            return '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
        return $default;
    }

    return $default;
}

function markChatAsRead(PDO $pdo, int $me, int $otherId): void {
    try {
        $stmt = $pdo->prepare("
            UPDATE messages
            SET `New` = 0
            WHERE Id = :me
              AND ById = :other
              AND `New` = 1
              AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
        ");
        $stmt->execute([
            ':me' => $me,
            ':other' => $otherId
        ]);
    } catch (Throwable $e) {
        // Do not break the chat screen if read marking fails.
    }
}

function fetchChatMessages(PDO $pdo, int $me, int $otherId): array {
    $stmt = $pdo->prepare("
        SELECT Msg_Num, Id, ById, Date_Sent, Msg_Txt, `New`
        FROM messages
        WHERE
            (
                ById = :me
                AND Id = :other
                AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
            )
            OR
            (
                ById = :other
                AND Id = :me
                AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
            )
        ORDER BY Date_Sent ASC, Msg_Num ASC
    ");

    $stmt->execute([
        ':me' => $me,
        ':other' => $otherId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderMessagesHtml(array $messages, int $me, string $meImg, string $otherImg): string {
    ob_start();

    if (!$messages) {
        echo '<div class="chat-empty">אין הודעות עדיין</div>';
    }

    foreach ($messages as $msg) {
        $isMe = ((int)$msg['ById'] === $me);
        $img = $isMe ? $meImg : $otherImg;

        $time = '';
        $fullDate = '';
        if (!empty($msg['Date_Sent'])) {
            $ts = strtotime((string)$msg['Date_Sent']);
            if ($ts) {
                if (date('Y-m-d') === date('Y-m-d', $ts)) {
                    $time = date('H:i', $ts);
                } elseif (date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', $ts)) {
                    $time = 'אתמול ' . date('H:i', $ts);
                } else {
                    $time = date('d/m H:i', $ts);
                }
                $fullDate = date('d/m/Y H:i', $ts);
            }
        }

        $readClass = ((int)($msg['New'] ?? 1) === 0) ? 'read' : '';
?>
        <div class="chat-row <?= $isMe ? 'me' : 'other' ?>" data-msg-id="<?= (int)($msg['Msg_Num'] ?? 0) ?>">
            <img
                src="<?= h($img) ?>"
                class="chat-avatar-small"
                onerror="this.onerror=null;this.src='/images/default_male.svg';"
                alt="">

            <div class="chat-bubble-wrap">
                <div class="chat-bubble <?= $isMe ? 'me' : 'other' ?>" data-time="<?= h($fullDate) ?>">
                    <?= nl2br(h($msg['Msg_Txt'] ?? '')) ?>
                </div>

                <?php if ($time !== ''): ?>
                    <div class="chat-meta">
                        <span class="chat-time"><?= h($time) ?></span>

                        <?php if ($isMe): ?>
                            <span class="chat-read <?= h($readClass) ?>">✓✓</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    return (string)ob_get_clean();
}

if (empty($_SESSION['user_id'])) {
    if (isset($_GET['ajax_action']) || isset($_POST['ajax_action'])) {
        jsonOut(['ok' => false, 'message' => 'המשתמש לא מחובר'], 401);
    }

    header('Location: /mobile/?page=login');
    exit;
}

$me = (int)$_SESSION['user_id'];
$otherId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)($_POST['user_id'] ?? 0);
$ajaxAction = (string)($_POST['ajax_action'] ?? $_GET['ajax_action'] ?? '');

if ($otherId <= 0 || $otherId === $me) {
    if ($ajaxAction !== '') {
        jsonOut(['ok' => false, 'message' => 'משתמש לא תקין'], 400);
    }

    /* =======================================================
       מצב רשימת משתמשים:
       בלי user_id מציגים משתמשים ששלחו לי / ששלחתי להם הודעות.
       עם user_id ממשיכים לצ'אט הרגיל שבהמשך הקובץ.
    ======================================================= */
    try {
        $stmt = $pdo->prepare("
            SELECT
                up.*,
                MAX(m.Date_Sent) AS last_msg_date,
                COUNT(*) AS total_count,
                SUM(
                    CASE
                        WHEN m.ById = up.Id
                         AND m.Id = :me
                         AND m.`New` = 1
                         AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL)
                        THEN 1
                        ELSE 0
                    END
                ) AS unread_count
            FROM messages m
            JOIN users_profile up
                ON up.Id = CASE
                    WHEN m.ById = :me THEN m.Id
                    ELSE m.ById
                END
            WHERE
                (m.ById = :me OR m.Id = :me)
                AND up.Id <> :me
                AND (up.Is_Frozen = 0 OR up.Is_Frozen IS NULL)
                AND (
                    (m.ById = :me AND (m.Deleted_By_ById = 0 OR m.Deleted_By_ById IS NULL))
                    OR
                    (m.Id = :me AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL))
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM blocked_users bu
                    WHERE (bu.Id = up.Id AND bu.Blocked_ById = :me)
                       OR (bu.Id = :me AND bu.Blocked_ById = up.Id)
                )
            GROUP BY up.Id
            ORDER BY last_msg_date DESC
        ");
        $stmt->execute([':me' => $me]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('MOBILE MESSAGE USERS LIST ERROR: ' . $e->getMessage());
        $results = [];
    }
    ?>

    <style>
        .mobile-views-page {
            padding: 14px;
            padding-bottom: 90px;
        }

        .mobile-views-title {
            margin: 0 0 14px;
            font-size: 24px;
            font-weight: 800;
            color: #222;
            text-align: right;
        }

        .mobile-views-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .view-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        }

        .view-card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .view-card-img-wrap {
            position: relative;
            flex: 0 0 auto;
        }

        .view-card-img {
            width: 74px;
            height: 74px;
            object-fit: cover;
            border-radius: 14px;
            display: block;
            background: #f5f5f5;
        }

        .view-card-info {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            flex: 1 1 auto;
        }

        .view-card-name {
            font-size: 17px;
            font-weight: 700;
            color: #222;
            line-height: 1.3;
            word-break: break-word;
        }

        .view-card-date {
            font-size: 13px;
            color: #777;
        }

        .msg-card-side {
            position: relative;
            flex: 0 0 auto;
            min-width: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: auto;
        }

        .msg-total-count {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: #f3f3f3;
            color: #333;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            border: 1px solid #e8e8e8;
        }

        .msg-new-top-badge {
            position: absolute;
            top: -10px;
            right: -26px;
            background: #e11d48;
            color: #fff;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25);
        }

        .no-results {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            color: #666;
        }
    </style>

    <main class="mobile-views-page">
        <h2 class="mobile-views-title">הודעות</h2>

        <?php if (!$results): ?>
            <div class="no-results">אין הודעות עדיין</div>
        <?php else: ?>
            <div class="mobile-views-list">
                <?php foreach ($results as $user): ?>
                    <?php
                    $userId = (int)($user['Id'] ?? 0);
                    $age = '';
                    if (!empty($user['DOB'])) {
                        try {
                            $age = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                        } catch (Throwable $e) {
                            $age = '';
                        }
                    }

                    $img = getChatUserImage($pdo, $userId);

                    $lastMsgText = '';
                    if (!empty($user['last_msg_date'])) {
                        try {
                            $dt = new DateTime((string)$user['last_msg_date']);
                            $lastMsgText = $dt->format('d/m/Y H:i');
                        } catch (Throwable $e) {
                            $lastMsgText = '';
                        }
                    }

                    $totalCount = (int)($user['total_count'] ?? 0);
                    $unreadCount = (int)($user['unread_count'] ?? 0);
                    ?>

                    <div class="view-card">
                        <a href="/mobile/?page=messages&user_id=<?= $userId ?>" class="view-card-link">
                            <div class="view-card-img-wrap">
                                <img
                                    src="<?= h($img) ?>"
                                    class="view-card-img"
                                    onerror="this.onerror=null;this.src='/images/default_male.svg';"
                                    alt="<?= h($user['Name'] ?? '') ?>">
                            </div>

                            <div class="view-card-info">
                                <div class="view-card-name">
                                    <?= h($user['Name'] ?? 'משתמש') ?><?= $age !== '' ? ', ' . (int)$age : '' ?>
                                </div>

                                <?php if ($lastMsgText !== ''): ?>
                                    <div class="view-card-date">הודעה אחרונה: <?= h($lastMsgText) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="msg-card-side">
                                <div class="msg-total-count"><?= $totalCount ?></div>

                                <?php if ($unreadCount > 0): ?>
                                    <div style="
    position:absolute;
    top:-22px;
    right:-10px;
    background:#e11d48;
    color:#fff;
    font-size:10px;
    font-weight:800;
    padding:3px 7px;
    border-radius:999px;
    white-space:nowrap;
    z-index:999;
">
                                        הודעה חדשה
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

<?php
    return;
}

/* ===== AJAX / direct POST actions before HTML ===== */
if ($ajaxAction === 'send') {
    $text = trim((string)($_POST['msg'] ?? $_POST['text'] ?? ''));

    if ($text === '') {
        jsonOut(['ok' => false, 'message' => 'אי אפשר לשלוח הודעה ריקה'], 400);
    }

    $text = mb_substr($text, 0, 2000);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`, Deleted_By_Id, Deleted_By_ById)
            VALUES (:to_id, :from_id, NOW(), :msg, 1, 0, 0)
        ");

        $stmt->execute([
            ':to_id'   => $otherId,
            ':from_id' => $me,
            ':msg'     => $text
        ]);

        try {
            $pdo->prepare("
                DELETE FROM message_typing
                WHERE user_id = :user_id
                  AND target_id = :target_id
            ")->execute([
                ':user_id' => $me,
                ':target_id' => $otherId
            ]);
        } catch (Throwable $e) {
            // If message_typing table does not exist yet, ignore.
        }

        jsonOut(['ok' => true]);
    } catch (Throwable $e) {
        error_log('CHAT SEND ERROR: ' . $e->getMessage());
        jsonOut(['ok' => false, 'message' => 'שגיאה בשליחת הודעה'], 500);
    }
}

if ($ajaxAction === 'fetch') {
    try {
        markChatAsRead($pdo, $me, $otherId);

        $otherImg = getChatUserImage($pdo, $otherId);
        $meImg = getChatUserImage($pdo, $me);
        $messages = fetchChatMessages($pdo, $me, $otherId);

        jsonOut([
            'ok' => true,
            'html' => renderMessagesHtml($messages, $me, $meImg, $otherImg),
            'last_id' => $messages ? (int)end($messages)['Msg_Num'] : 0
        ]);
    } catch (Throwable $e) {
        error_log('CHAT FETCH ERROR: ' . $e->getMessage());
        jsonOut(['ok' => false, 'message' => 'שגיאה בטעינת הודעות'], 500);
    }
}

if ($ajaxAction === 'mark_read') {
    markChatAsRead($pdo, $me, $otherId);
    jsonOut(['ok' => true]);
}

if ($ajaxAction === 'typing') {
    try {
        $isTyping = (int)($_POST['typing'] ?? 0);

        if ($isTyping) {
            $pdo->prepare("
                INSERT INTO message_typing (user_id, target_id, updated_at)
                VALUES (:user_id, :target_id, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ")->execute([
                ':user_id' => $me,
                ':target_id' => $otherId
            ]);
        } else {
            $pdo->prepare("
                DELETE FROM message_typing
                WHERE user_id = :user_id
                  AND target_id = :target_id
            ")->execute([
                ':user_id' => $me,
                ':target_id' => $otherId
            ]);
        }

        jsonOut(['ok' => true]);
    } catch (Throwable $e) {
        // Probably the typing table is missing. Do not show an error to the user.
        jsonOut(['ok' => true]);
    }
}

if ($ajaxAction === 'typing_status') {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM message_typing
            WHERE user_id = :other
              AND target_id = :me
              AND updated_at >= (NOW() - INTERVAL 6 SECOND)
        ");
        $stmt->execute([
            ':other' => $otherId,
            ':me' => $me
        ]);

        jsonOut(['ok' => true, 'typing' => ((int)$stmt->fetchColumn() > 0)]);
    } catch (Throwable $e) {
        jsonOut(['ok' => true, 'typing' => false]);
    }
}

/* ===== fallback standard POST, if JS is disabled ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim((string)($_POST['msg'] ?? ''));

    if ($text !== '') {
        $text = mb_substr($text, 0, 2000);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`, Deleted_By_Id, Deleted_By_ById)
                VALUES (:to_id, :from_id, NOW(), :msg, 1, 0, 0)
            ");

            $stmt->execute([
                ':to_id'   => $otherId,
                ':from_id' => $me,
                ':msg'     => $text
            ]);
        } catch (Throwable $e) {
            error_log('CHAT FALLBACK SEND ERROR: ' . $e->getMessage());
        }
    }

    header('Location: /mobile/?page=messages&user_id=' . $otherId);
    exit;
}

/* ===== פרטי המשתמש השני ===== */
$stmt = $pdo->prepare("
    SELECT Id, Name
    FROM users_profile
    WHERE Id = ?
    LIMIT 1
");
$stmt->execute([$otherId]);
$otherUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otherUser) {
    echo '<div class="chat-empty">המשתמש לא נמצא</div>';
    return;
}

$otherName = trim((string)($otherUser['Name'] ?? 'משתמש'));
$otherImg  = getChatUserImage($pdo, $otherId);
$meImg     = getChatUserImage($pdo, $me);

markChatAsRead($pdo, $me, $otherId);
$messages = fetchChatMessages($pdo, $me, $otherId);
?>

<div class="chat-page" data-other-id="<?= (int)$otherId ?>">

    <div class="chat-title-bar">
        <a href="/mobile/?page=profile&id=<?= (int)$otherId ?>" class="chat-profile-link">
            <img
                src="<?= h($otherImg) ?>"
                class="chat-avatar"
                onerror="this.onerror=null;this.src='/images/default_male.svg';"
                alt="">
            <div class="chat-profile-text">
                <div class="chat-profile-name"><?= h($otherName) ?></div>
                <div class="chat-profile-sub">צפה בפרופיל</div>
            </div>
        </a>
    </div>

    <div class="chat-messages" id="chatBox">
        <?= renderMessagesHtml($messages, $me, $meImg, $otherImg) ?>
    </div>

    <div class="chat-typing" id="typingBox" style="display:none;">מקליד...</div>

    <form class="chat-send" id="chatForm" method="post" action="/mobile/messages.php?user_id=<?= (int)$otherId ?>" autocomplete="off">
        <input type="hidden" name="user_id" value="<?= (int)$otherId ?>">
        <input type="hidden" name="ajax_action" value="send">

        <label class="chat-enter-label" title="שליחה עם Enter">
            <input type="checkbox" id="sendWithEnter" checked>
            Enter
        </label>

        <input id="chatInput" type="text" name="msg" placeholder="כתוב הודעה..." autocomplete="off">

        <button type="submit">שלח</button>
    </form>

</div>

<script>
    (function() {
        const otherId = <?= (int)$otherId ?>;
        const ajaxUrl = '/mobile/messages.php?user_id=' + encodeURIComponent(otherId);

        const box = document.getElementById('chatBox');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('chatInput');
        const enterCheckbox = document.getElementById('sendWithEnter');
        const typingBox = document.getElementById('typingBox');

        let isSending = false;
        let typingTimer = null;
        let lastHtml = '';

        function nearBottom() {
            if (!box) return true;
            return (box.scrollHeight - box.scrollTop - box.clientHeight) < 140;
        }

        function scrollToBottom(force) {
            if (!box) return;
            if (force || nearBottom()) {
                box.scrollTop = box.scrollHeight;
            }
        }

        async function postAction(params) {
            const body = new URLSearchParams(params);

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body
            });

            const text = await response.text();

            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Non JSON response:', text);
                return {
                    ok: false,
                    message: 'השרת החזיר תשובה לא תקינה. בדוק ש־/mobile/messages.php קיים ואין שגיאות PHP.'
                };
            }
        }

        async function loadMessages(forceScroll) {
            if (!box) return;

            const wasNearBottom = nearBottom();

            try {
                const response = await fetch(ajaxUrl + '&ajax_action=fetch', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                });

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Fetch non JSON:', text);
                    return;
                }

                if (!data.ok) return;

                if (data.html !== lastHtml) {
                    box.innerHTML = data.html;
                    lastHtml = data.html;

                    if (forceScroll || wasNearBottom) {
                        scrollToBottom(true);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function sendMessage() {
            if (!input || isSending) return;

            const msg = input.value.trim();
            if (!msg) return;

            isSending = true;

            try {
                const data = await postAction({
                    ajax_action: 'send',
                    user_id: otherId,
                    msg: msg
                });

                if (!data.ok) {
                    alert(data.message || 'שגיאה בשליחת הודעה');
                    return;
                }

                input.value = '';
                await postAction({
                    ajax_action: 'typing',
                    user_id: otherId,
                    typing: '0'
                });

                await loadMessages(true);
                input.focus();
            } catch (e) {
                console.error(e);
                alert('שגיאה בשליחת הודעה');
            } finally {
                isSending = false;
            }
        }

        async function setTyping(isTyping) {
            try {
                await postAction({
                    ajax_action: 'typing',
                    user_id: otherId,
                    typing: isTyping ? '1' : '0'
                });
            } catch (e) {
                // ignore
            }
        }

        async function checkTyping() {
            if (!typingBox) return;

            try {
                const response = await fetch(ajaxUrl + '&ajax_action=typing_status', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                });

                const text = await response.text();
                const data = JSON.parse(text);

                typingBox.style.display = data.typing ? 'block' : 'none';
            } catch (e) {
                typingBox.style.display = 'none';
            }
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }

        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey && enterCheckbox && enterCheckbox.checked) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            input.addEventListener('input', function() {
                setTyping(true);

                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    setTyping(false);
                }, 1800);
            });
        }

        scrollToBottom(true);
        loadMessages(true);

        setInterval(function() {
            loadMessages(false);
        }, 2500);

        setInterval(checkTyping, 1500);
    })();
</script>

<style>
    .chat-page {
        display: flex;
        flex-direction: column;
        height: calc(100dvh - 220px);
        min-height: 360px;
        max-height: calc(100dvh - 220px);
        overflow: hidden;
        background: #f3f4f6;
        border-top: 1px solid #eeeeee;
    }

    .chat-title-bar {
        background: #ffffff;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        direction: rtl;
    }

    .chat-profile-link {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        text-decoration: none;
        color: inherit;
    }

    .chat-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        background: #f1f1f1;
        border: 1px solid #dddddd;
        flex: 0 0 auto;
    }

    .chat-profile-text {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .chat-profile-name {
        font-size: 18px;
        font-weight: 800;
        color: #111827;
        line-height: 1.2;
    }

    .chat-profile-sub {
        margin-top: 3px;
        font-size: 13px;
        color: #e83e6f;
        text-decoration: underline;
    }

    .chat-messages {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 10px 10px 12px;
        background: #f3f4f6;
        direction: ltr !important;
    }

    .chat-row {
        width: 100%;
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 14px;
        box-sizing: border-box;
    }

    /* אני / השולח - צד ימין */
    .chat-row.me {
        flex-direction: row !important;
        justify-content: flex-end !important;
    }

    /* המשתמש השני - צד שמאל */
    .chat-row.other {
        flex-direction: row !important;
        justify-content: flex-start !important;
    }

    .chat-avatar-small {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        background: #f1f1f1;
        border: 1px solid #dddddd;
        flex: 0 0 auto;
    }

    .chat-bubble-wrap {
        display: flex;
        flex-direction: column;
        max-width: 76%;
    }

    .chat-row.me .chat-bubble-wrap {
        align-items: flex-end;
    }

    .chat-row.other .chat-bubble-wrap {
        align-items: flex-start;
    }

    .chat-bubble {
        padding: 10px 13px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.45;
        word-break: break-word;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        direction: rtl;
        text-align: right;
        position: relative;
    }

    .chat-bubble.me {
        background: #d7f0d2;
        color: #1f2937;
        border-bottom-right-radius: 6px;
    }

    .chat-bubble.other {
        background: #ffffff;
        color: #1f2937;
        border-bottom-left-radius: 6px;
    }

    .chat-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 4px;
        font-size: 11px;
        color: #6b7280;
        direction: ltr;
    }

    .chat-row.me .chat-meta {
        justify-content: flex-end;
    }

    .chat-row.other .chat-meta {
        justify-content: flex-start;
    }

    .chat-read {
        font-size: 12px;
        color: #9ca3af;
        letter-spacing: -2px;
    }

    .chat-read.read {
        color: #0ea5e9;
        font-weight: 700;
    }

    .chat-empty {
        padding: 28px 10px;
        text-align: center;
        color: #8a94a3;
        font-size: 14px;
        direction: rtl;
    }

    .chat-typing {
        padding: 4px 16px 8px;
        color: #6b7280;
        font-size: 12px;
        background: #f3f4f6;
        direction: rtl;
        text-align: right;
    }

    .chat-send {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        background: #ffffff;
        border-top: 1px solid #dddddd;
        direction: rtl;
        flex: 0 0 auto;
        position: relative;
        z-index: 20;
    }

    .chat-send input[type="text"] {
        flex: 1;
        height: 42px;
        padding: 0 14px;
        border: 1px solid #d1d5db;
        border-radius: 14px;
        background: #f9fafb;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        direction: rtl;
    }

    .chat-send input[type="text"]:focus {
        background: #ffffff;
        border-color: #e86a7a;
        box-shadow: 0 0 0 3px rgba(232, 106, 122, 0.15);
    }

    .chat-send button {
        height: 42px;
        min-width: 64px;
        padding: 0 14px;
        border: none;
        border-radius: 14px;
        background: #e86a7a;
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        flex: 0 0 auto;
    }

    .chat-enter-label {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: #4b5563;
        white-space: nowrap;
        user-select: none;
    }

    .chat-enter-label input {
        margin: 0;
    }

    @media (max-width: 420px) {
        .chat-page {
            height: calc(100dvh - 230px);
            max-height: calc(100dvh - 230px);
        }

        .chat-bubble-wrap {
            max-width: 74%;
        }

        .chat-avatar-small {
            width: 30px;
            height: 30px;
        }

        .chat-profile-name {
            font-size: 17px;
        }

        .chat-enter-label {
            font-size: 11px;
        }

        .chat-send button {
            min-width: 58px;
        }
    }

    .msg-new-top {
        position: absolute;
        top: -35px;
        /* 👈 יותר גבוה */
        right: -6px;
        /* 👈 קצת פחות ימינה */
        background: #e11d48;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .msg-side {
        margin-right: auto;
        position: relative;
        min-width: 50px;
        height: 42px;
        /* 👈 נותן מקום לבאדג׳ */
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .msg-side {
        position: relative !important;
        min-width: 70px !important;
        height: 54px !important;
        display: flex !important;
        align-items: flex-end !important;
        justify-content: center !important;
    }

    .msg-new-top {
        position: absolute !important;
        top: -4px !important;
        right: 0 !important;
        background: #e11d48 !important;
        color: #fff !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        padding: 2px 7px !important;
        border-radius: 999px !important;
        white-space: nowrap !important;
        z-index: 5 !important;
    }

    .msg-total {
        margin-top: 16px !important;
    }
</style>

<script>
    document.addEventListener('click', function(e) {

        const bubble = e.target.closest('.chat-bubble');
        if (!bubble) return;

        const time = bubble.getAttribute('data-time');
        if (!time) return;

        // אם כבר פתוח → סגור
        let existing = bubble.querySelector('.msg-time-popup');
        if (existing) {
            existing.remove();
            return;
        }

        // סגור כל האחרים
        document.querySelectorAll('.msg-time-popup').forEach(el => el.remove());

        // יצירת פופאפ
        const el = document.createElement('div');
        el.className = 'msg-time-popup';
        el.innerText = time;

        bubble.appendChild(el);

        // נעלם אחרי 2 שניות
        setTimeout(() => el.remove(), 2000);
    });
</script>