<?php
/* =========================
   profile.php
   ========================= */

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileFields = require __DIR__ . '/profile_fields.php';

/* -----------------------------
   עזר
----------------------------- */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function profile_value(array $user, string $field): string
{
    return isset($user[$field]) && $user[$field] !== null ? trim((string)$user[$field]) : '';
}

function get_options(PDO $pdo, array $cfg): array
{
    if (
        empty($cfg['table']) ||
        empty($cfg['column']) ||
        !in_array(($cfg['type'] ?? ''), ['select'], true)
    ) {
        return [];
    }

    $table = $cfg['table'];
    $column = $cfg['column'];

    $allowedMaps = [
        'gender'           => 'Gender_Str',
        'age'              => 'Age_Str',
        'occupation'       => 'Occupation_Str',
        'education'        => 'Education_Str',
        'place'            => 'Place_Str',
        'family_status'    => 'Family_Status_Str',
        'childs_num'       => 'Childs_Num_Str',
        'religion'         => 'Religion_Str',
        'religion_ref'     => 'Religion_Ref_Str',
        'smoking_habbit'   => 'Smoking_Habbit_Str',
        'drinking_habbit'  => 'Drinking_Habbit_Str',
        'vegitrain'        => 'Vegitrain_Str',
        'height'           => 'Height_Str',
        'hair_color'       => 'Hair_Color_Str',
        'hair_type'        => 'Hair_Type_Str',
        'body_type'        => 'Body_Type_Str',
        'look_type'        => 'Look_Type_Str',
        'zone'             => 'Zone_Str',
    ];

    if (!isset($allowedMaps[$table]) || $allowedMaps[$table] !== $column) {
        return [];
    }

    try {
        $stmt = $pdo->query("
            SELECT {$column}
            FROM {$table}
            WHERE {$column} IS NOT NULL
              AND {$column} <> ''
            ORDER BY {$column} ASC
        ");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (Throwable $e) {
        return [];
    }
}

function detect_birthdate_value(array $user): string
{
    $possibleFields = [
        'Birth_Date',
        'BirthDate',
        'Date_Of_Birth',
        'DOB',
        'Birthday',
        'BDate',
        'Birth_Dt'
    ];

    foreach ($possibleFields as $field) {
        if (!empty($user[$field])) {
            return trim((string)$user[$field]);
        }
    }

    return '';
}

function compute_age_from_birthdate(array $user): string
{
    $birthDate = detect_birthdate_value($user);

    if ($birthDate === '') {
        return '';
    }

    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime('today');
        return (string)$birth->diff($today)->y;
    } catch (Throwable $e) {
        return '';
    }
}

function format_profile_display_value(string $field, string $value, array $cfg = []): string
{
    $value = trim($value);

    if (!empty($cfg['zero_as_none'])) {
        if ($value === '0' || $value === '0 ילדים') {
            return 'ללא';
        }
    }

    return $value;
}

/* -----------------------------
   חלוקת שדות לימין/שמאל
----------------------------- */
$rightFields = [];
$leftFields = [];

foreach ($profileFields as $field => $cfg) {
    if (($cfg['side'] ?? '') === 'right') {
        $rightFields[$field] = $cfg;
    } elseif (($cfg['side'] ?? '') === 'left') {
        $leftFields[$field] = $cfg;
    }
}

/* -----------------------------
   קבלת מזהה משתמש
----------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$viewerName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'אני')));
$viewerImage = '/images/no_photo.jpg';

if (!empty($_SESSION['user_main_pic'])) {
    $viewerImage = (string)$_SESSION['user_main_pic'];
} elseif (!empty($_SESSION['user_image'])) {
    $viewerImage = '/images/' . $_SESSION['user_image'];
}

if ($id <= 0) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    exit;
}

/* -----------------------------
   שליפת משתמש
----------------------------- */
$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    exit;
}

$isOwner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$user['Id'];

/* -----------------------------
   שמירת צפייה
----------------------------- */
if ($viewerId > 0 && $viewerId !== (int)$user['Id']) {
    $deleteViewStmt = $pdo->prepare("
        DELETE FROM views
        WHERE Id = :viewed_id
          AND ById = :viewer_id
    ");
    $deleteViewStmt->execute([
        ':viewed_id' => (int)$user['Id'],
        ':viewer_id' => $viewerId
    ]);

    $insertViewStmt = $pdo->prepare("
        INSERT INTO views (Id, ById, Date, New)
        VALUES (:viewed_id, :viewer_id, NOW(), 1)
    ");
    $insertViewStmt->execute([
        ':viewed_id' => (int)$user['Id'],
        ':viewer_id' => $viewerId
    ]);
}

$editMode = $isOwner && isset($_GET['edit']) && (int)$_GET['edit'] === 1;

/* -----------------------------
   תמונת פרופיל
----------------------------- */
$profileImage = '/images/no_photo.jpg';

$picStmt = $pdo->prepare("
    SELECT Pic_Name
    FROM user_pics
    WHERE Id = :id
      AND Main_Pic = 1
      AND Pic_Status = 1
    LIMIT 1
");
$picStmt->execute([':id' => $id]);
$picRow = $picStmt->fetch(PDO::FETCH_ASSOC);

if ($picRow && !empty($picRow['Pic_Name'])) {
    $profileImage = '/upload/' . $picRow['Pic_Name'];
}

/* -----------------------------
   שם
----------------------------- */
$displayName = profile_value($user, 'Name');
if ($displayName === '') {
    $displayName = 'ללא שם';
}
?>

<div class="page-shell">
    <div class="profile-page">

        <div class="profile-left">
            <?php foreach ($leftFields as $field => $cfg): ?>
                <?php
                $title = $cfg['label'] ?? $field;
                $type = $cfg['type'] ?? 'textarea';
                $value = profile_value($user, $field);

                $displayValue = $value !== ''
                    ? nl2br(e($value))
                    : '<span class="empty-inline-note">אין מידע עדיין</span>';
                ?>
                <div class="profile-block" data-field="<?= e($field) ?>">
                    <div class="profile-block-head">
                        <h3><?= e($title) ?></h3>

                        <?php if ($isOwner): ?>
                            <button type="button" class="edit-btn left-edit-btn">✎</button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-block-body">
                        <div class="left-view-mode">
                            <?= $displayValue ?>
                        </div>

                        <?php if ($isOwner): ?>
                            <div class="left-edit-mode">
                                <?php if ($type === 'input'): ?>
                                    <input type="text" class="profile-left-edit-input" value="<?= e($value) ?>">
                                <?php else: ?>
                                    <textarea class="profile-left-edit-textarea"><?= e($value) ?></textarea>
                                <?php endif; ?>

                                <div class="profile-left-edit-actions">
                                    <button type="button" class="profile-left-save-btn">שמירה</button>
                                    <button type="button" class="profile-left-cancel-btn">ביטול</button>
                                </div>

                                <div class="profile-left-edit-status"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <aside class="profile-right">
            <div class="profile-card">
                <div class="profile-card-image-wrap">
                    <img src="<?= e($profileImage) ?>" alt="<?= e($displayName) ?>" class="profile-card-image">
                </div>

                <div class="profile-card-main">
                    <?php $age = compute_age_from_birthdate($user); ?>

                    <h1 class="profile-card-name">
                        <?= e($displayName) ?>
                        <?php if ($age !== ''): ?>
                            <span class="profile-age">, <?= e($age) ?></span>
                        <?php endif; ?>
                    </h1>
                </div>

                <div class="profile-card-actions">
                    <?php if (!$isOwner && $viewerId > 0): ?>
                        <button
                            type="button"
                            class="profile-message-btn"
                            onclick="openMessageModal(
                                <?= (int)$user['Id'] ?>,
                                '<?= e($displayName) ?>',
                                '<?= e($profileImage) ?>',
                                '<?= e($viewerName) ?>',
                                '<?= e($viewerImage) ?>'
                            )">
                            <span class="profile-message-btn-icon">✉️</span>
                            <span>שלח הודעה</span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($isOwner && !$editMode): ?>
                    <div class="profile-right-edit-link-wrap">
                        <a href="?page=profile&id=<?= (int)$user['Id'] ?>&edit=1" class="profile-right-edit-link">
                            ✏️ עריכת פרטים
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!$editMode): ?>
                    <div class="profile-info-list">
                        <?php foreach ($rightFields as $field => $cfg): ?>
                            <?php
                            $label = $cfg['label'] ?? $field;

                            if ($field === 'Age_Computed') {
                                $value = compute_age_from_birthdate($user);
                            } else {
                                $value = profile_value($user, $field);
                            }

                            $displayValue = format_profile_display_value($field, $value, $cfg);

                            if ($displayValue === '') {
                                continue;
                            }
                            ?>
                            <div class="profile-info-row">
                                <div class="profile-info-label"><?= e($label) ?>:</div>
                                <div class="profile-info-value-wrap">
                                    <div class="profile-info-value"><?= e($displayValue) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <form id="profileRightForm" class="profile-right-form">
                        <?php foreach ($rightFields as $field => $cfg): ?>
                            <?php
                            $label = $cfg['label'] ?? $field;
                            $type = $cfg['type'] ?? 'input';

                            if ($field === 'Age_Computed') {
                                $value = compute_age_from_birthdate($user);
                            } else {
                                $value = profile_value($user, $field);
                            }

                            $options = $type === 'select' ? get_options($pdo, $cfg) : [];
                            $readOnly = !empty($cfg['read_only']);
                            $displayValue = format_profile_display_value($field, $value, $cfg);
                            ?>
                            <div class="profile-right-edit-row">
                                <label class="profile-right-edit-label" for="field_<?= e($field) ?>"><?= e($label) ?></label>

                                <div class="profile-right-edit-control">
                                    <?php if ($readOnly): ?>
                                        <div class="profile-right-input profile-right-readonly">
                                            <?= e($displayValue) ?>
                                        </div>
                                    <?php elseif ($type === 'select'): ?>
                                        <div class="profile-right-select-wrap">
                                            <select
                                                id="field_<?= e($field) ?>"
                                                name="<?= e($field) ?>"
                                                class="profile-right-input profile-right-select">
                                                <option value="">בחר</option>
                                                <?php foreach ($options as $opt): ?>
                                                    <option value="<?= e($opt) ?>" <?= $opt === $value ? 'selected' : '' ?>>
                                                        <?= e($opt) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input
                                            id="field_<?= e($field) ?>"
                                            name="<?= e($field) ?>"
                                            type="text"
                                            class="profile-right-input"
                                            value="<?= e($value) ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="profile-right-actions">
                            <button type="button" id="saveRightProfileBtn" class="profile-right-save-btn">שמירה</button>
                            <a href="?page=profile&id=<?= (int)$user['Id'] ?>" class="profile-right-cancel-btn">ביטול</a>
                        </div>

                        <div id="profileRightEditStatus" class="profile-right-edit-status"></div>
                    </form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php if ($viewerId > 0): ?>
<div id="chatWindowsLayer" class="chat-windows-layer"></div>
<?php endif; ?>

<script>
const chatWindows = {};
let badgeRefreshTimer = null;
let unreadByUser = {};

function getChatWindowId(userId) {
    return 'chat-window-' + String(userId);
}

function getChatWindowElements(userId) {
    const root = document.getElementById(getChatWindowId(userId));
    if (!root) return null;

    return {
        root,
        head: root.querySelector('.message-window-head'),
        body: root.querySelector('.message-window-body'),
        history: root.querySelector('.message-history'),
        text: root.querySelector('.message-modal-textarea'),
        status: root.querySelector('.message-send-status'),
        title: root.querySelector('.message-modal-title'),
        image: root.querySelector('.message-modal-head-image')
    };
}

function buildMessageRow(msg, chat) {
    const row = document.createElement('div');
    row.className = 'message-row ' + (msg.is_me ? 'message-row-me' : 'message-row-other');
    row.setAttribute('data-message-id', msg.id);

    const avatar = document.createElement('img');
    avatar.className = 'message-avatar';
    avatar.src = msg.is_me ? chat.viewerImage : chat.userImage;
    avatar.alt = msg.sender_name || '';

    const bubbleWrap = document.createElement('div');
    bubbleWrap.className = 'message-bubble-wrap';

    const sender = document.createElement('div');
    sender.className = 'message-sender';
    sender.textContent = msg.sender_name || '';

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble ' + (msg.is_me ? 'message-bubble-me' : 'message-bubble-other');
    bubble.innerHTML = String(msg.text || '').replace(/\n/g, '<br>');

    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = msg.date_sent || '';

    bubbleWrap.appendChild(sender);
    bubbleWrap.appendChild(bubble);
    bubbleWrap.appendChild(time);

    row.appendChild(avatar);
    row.appendChild(bubbleWrap);

    return row;
}

function isHistoryNearBottom(history) {
    if (!history) return true;
    return (history.scrollHeight - history.scrollTop - history.clientHeight) < 80;
}

function scrollHistoryToBottom(history) {
    if (!history) return;
    history.scrollTop = history.scrollHeight;
}

function updateDefaultWindowPosition(userId) {
    const elements = getChatWindowElements(userId);
    if (!elements) return;

    const openIds = Object.keys(chatWindows)
        .filter(id => {
            const chat = chatWindows[id];
            const node = document.getElementById(getChatWindowId(id));
            return chat && node && node.style.display !== 'none';
        })
        .sort((a, b) => Number(a) - Number(b));

    const index = openIds.indexOf(String(userId));
    if (index === -1) return;

    if (!chatWindows[userId].dragged) {
        elements.root.style.left = 'auto';
        elements.root.style.top = 'auto';
        elements.root.style.right = (18 + (index * 400)) + 'px';
        elements.root.style.bottom = '18px';
    }
}

function refreshAllWindowPositions() {
    Object.keys(chatWindows).forEach(userId => {
        updateDefaultWindowPosition(userId);
    });
}

function createChatWindow(userId, userName, userImage, viewerName, viewerImage) {
    const layer = document.getElementById('chatWindowsLayer');
    if (!layer) return null;

    const existing = document.getElementById(getChatWindowId(userId));
    if (existing) return existing;

    const root = document.createElement('div');
    root.id = getChatWindowId(userId);
    root.className = 'message-modal-window';
    root.style.display = 'block';
    root.innerHTML = `
        <div class="message-window-card">
            <div class="message-window-head">
                <div class="message-window-head-user">
                    <img class="message-modal-head-image" src="${userImage}" alt="">
                    <div class="message-modal-head-text">
                        <h3 class="message-modal-title">${userName}</h3>
                        <div class="message-modal-head-subtitle">היסטוריית הודעות</div>
                    </div>
                </div>

                <div class="message-window-head-actions">
                    <button type="button" class="message-window-minimize">—</button>
                    <button type="button" class="message-modal-close">✕</button>
                </div>
            </div>

            <div class="message-window-body">
                <div class="message-history">
                    <div class="message-history-empty">טוען הודעות...</div>
                </div>

                <div class="message-modal-body">
                    <textarea class="message-modal-textarea" placeholder="כתוב הודעה..."></textarea>
                    <div class="message-send-status"></div>
                </div>

                <div class="message-modal-actions">
                    <button type="button" class="message-send-btn">שלח</button>
                    <button type="button" class="message-cancel-btn">סגור</button>
                </div>
            </div>
        </div>
    `;

    layer.appendChild(root);

    chatWindows[userId] = {
        userId: Number(userId),
        userName: userName,
        userImage: userImage || '/images/no_photo.jpg',
        viewerName: viewerName || 'אני',
        viewerImage: viewerImage || '/images/no_photo.jpg',
        lastMessageId: 0,
        minimized: false,
        dragged: false,
        timer: null,
        isDragging: false,
        dragOffsetX: 0,
        dragOffsetY: 0
    };

    bindChatWindowEvents(userId);
    refreshAllWindowPositions();

    return root;
}

function bindChatWindowEvents(userId) {
    const elements = getChatWindowElements(userId);
    if (!elements) return;

    const head = elements.head;
    const text = elements.text;
    const history = elements.history;
    const sendBtn = elements.root.querySelector('.message-send-btn');
    const closeBtn = elements.root.querySelector('.message-modal-close');
    const cancelBtn = elements.root.querySelector('.message-cancel-btn');
    const minimizeBtn = elements.root.querySelector('.message-window-minimize');

    minimizeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleMessageWindowMinimize(userId);
    });

    closeBtn.addEventListener('click', function () {
        closeMessageModal(userId);
    });

    cancelBtn.addEventListener('click', function () {
        closeMessageModal(userId);
    });

    sendBtn.addEventListener('click', function () {
        sendProfileMessage(userId);
    });

    text.addEventListener('focus', function () {
        markConversationAsRead(userId);
    });

    text.addEventListener('click', function () {
        markConversationAsRead(userId);
    });

    history.addEventListener('click', function () {
        markConversationAsRead(userId);
    });

    text.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendProfileMessage(userId);
        }
    });

    head.addEventListener('mousedown', function (e) {
        if (
            e.target.closest('.message-window-minimize') ||
            e.target.closest('.message-modal-close')
        ) {
            return;
        }

        const chat = chatWindows[userId];
        if (!chat) return;

        const rect = elements.root.getBoundingClientRect();

        chat.isDragging = true;
        chat.dragged = true;
        chat.dragOffsetX = e.clientX - rect.left;
        chat.dragOffsetY = e.clientY - rect.top;

        elements.root.style.left = rect.left + 'px';
        elements.root.style.top = rect.top + 'px';
        elements.root.style.right = 'auto';
        elements.root.style.bottom = 'auto';

        document.body.classList.add('message-window-dragging');
    });
}

document.addEventListener('mousemove', function (e) {
    Object.keys(chatWindows).forEach(userId => {
        const chat = chatWindows[userId];
        if (!chat || !chat.isDragging) return;

        const elements = getChatWindowElements(userId);
        if (!elements) return;

        const modalWidth = elements.root.offsetWidth;
        const modalHeight = elements.root.offsetHeight;
        const maxLeft = Math.max(0, window.innerWidth - modalWidth);
        const maxTop = Math.max(0, window.innerHeight - modalHeight);

        let newLeft = e.clientX - chat.dragOffsetX;
        let newTop = e.clientY - chat.dragOffsetY;

        if (newLeft < 0) newLeft = 0;
        if (newTop < 0) newTop = 0;
        if (newLeft > maxLeft) newLeft = maxLeft;
        if (newTop > maxTop) newTop = maxTop;

        elements.root.style.left = newLeft + 'px';
        elements.root.style.top = newTop + 'px';
    });
});

document.addEventListener('mouseup', function () {
    let hadDragging = false;

    Object.keys(chatWindows).forEach(userId => {
        const chat = chatWindows[userId];
        if (chat && chat.isDragging) {
            chat.isDragging = false;
            hadDragging = true;
        }
    });

    if (hadDragging) {
        document.body.classList.remove('message-window-dragging');
    }
});

async function refreshMessagesBadge() {
    const badge = document.getElementById('messagesBadge');
    if (!badge) return;

    try {
        const response = await fetch('get_unread_count.php', { cache: 'no-store' });
        const result = await response.json();

        if (!result.ok) {
            return;
        }

        unreadByUser = result.by_user || {};
        const count = parseInt(result.count || 0, 10);

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = 'flex';
            badge.classList.add('badge-pulse');

            setTimeout(() => {
                badge.classList.remove('badge-pulse');
            }, 400);
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    } catch (err) {
        console.error(err);
    }
}

async function markConversationAsRead(userId) {
    if (!userId) return;

    try {
        const response = await fetch('mark_messages_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                user_id: userId
            }).toString()
        });

        const result = await response.json();

        if (result.ok) {
            unreadByUser[userId] = 0;
            refreshMessagesBadge();
        }
    } catch (err) {
        console.error(err);
    }
}

function openMessageModal(userId, userName, userImage, viewerName, viewerImage) {
    userId = Number(userId);

    createChatWindow(userId, userName, userImage, viewerName, viewerImage);

    const chat = chatWindows[userId];
    const elements = getChatWindowElements(userId);

    if (!chat || !elements) return;

    elements.root.style.display = 'block';
    elements.root.classList.remove('is-minimized');
    elements.body.style.display = 'block';
    elements.title.textContent = userName;
    elements.image.src = userImage || '/images/no_photo.jpg';

    chat.userName = userName;
    chat.userImage = userImage || '/images/no_photo.jpg';
    chat.viewerName = viewerName || 'אני';
    chat.viewerImage = viewerImage || '/images/no_photo.jpg';
    chat.minimized = false;

    if (chat.lastMessageId === 0) {
        elements.history.innerHTML = '<div class="message-history-empty">טוען הודעות...</div>';
        loadMessageHistory(userId, true, true);
    } else {
        loadMessageHistory(userId, false, false);
    }

    elements.text.focus();

    if (chat.timer) {
        clearInterval(chat.timer);
    }

    chat.timer = setInterval(() => {
        if (!chat.minimized) {
            loadMessageHistory(userId, false, false);
        }
        refreshMessagesBadge();
    }, 4000);

    refreshAllWindowPositions();

    if (!badgeRefreshTimer) {
        badgeRefreshTimer = setInterval(refreshMessagesBadge, 5000);
    }
}

function closeMessageModal(userId) {
    const chat = chatWindows[userId];
    const elements = getChatWindowElements(userId);

    if (chat && chat.timer) {
        clearInterval(chat.timer);
        chat.timer = null;
    }

    if (elements) {
        elements.root.remove();
    }

    delete chatWindows[userId];
    refreshAllWindowPositions();
}

function toggleMessageWindowMinimize(userId) {
    const chat = chatWindows[userId];
    const elements = getChatWindowElements(userId);

    if (!chat || !elements) return;

    chat.minimized = !chat.minimized;

    if (chat.minimized) {
        elements.root.classList.add('is-minimized');
        elements.body.style.display = 'none';
    } else {
        elements.root.classList.remove('is-minimized');
        elements.body.style.display = 'block';
        loadMessageHistory(userId, false, false);
        scrollHistoryToBottom(elements.history);
    }
}

async function loadMessageHistory(userId, forceScrollToBottom = false, firstLoad = false) {
    const chat = chatWindows[userId];
    const elements = getChatWindowElements(userId);

    if (!chat || !elements) return;

    const history = elements.history;
    const nearBottomBefore = isHistoryNearBottom(history);

    try {
        const response = await fetch(
            'get_messages.php?user_id=' + encodeURIComponent(userId) + '&last_id=' + encodeURIComponent(chat.lastMessageId),
            { cache: 'no-store' }
        );

        const result = await response.json();

        if (!result.ok) {
            if (firstLoad) {
                history.innerHTML = '<div class="message-history-empty">לא ניתן לטעון הודעות</div>';
            }
            return;
        }

        const messages = Array.isArray(result.messages) ? result.messages : [];

        if (firstLoad) {
            history.innerHTML = '';

            if (messages.length === 0) {
                history.innerHTML = '<div class="message-history-empty">עדיין אין הודעות ביניכם</div>';
            } else {
                messages.forEach(msg => {
                    history.appendChild(buildMessageRow(msg, chat));
                });
            }
        } else if (messages.length > 0) {
            messages.forEach(msg => {
                const exists = history.querySelector('[data-message-id="' + msg.id + '"]');
                if (!exists) {
                    history.appendChild(buildMessageRow(msg, chat));
                }
            });
        }

        if (typeof result.last_id !== 'undefined') {
            chat.lastMessageId = parseInt(result.last_id, 10) || chat.lastMessageId;
        }

        if (firstLoad || forceScrollToBottom || nearBottomBefore) {
            scrollHistoryToBottom(history);
        }
    } catch (err) {
        if (firstLoad) {
            history.innerHTML = '<div class="message-history-empty">שגיאה בטעינת ההודעות</div>';
        }
        console.error(err);
    }
}

async function sendProfileMessage(userId) {
    const chat = chatWindows[userId];
    const elements = getChatWindowElements(userId);

    if (!chat || !elements) return;

    const msg = elements.text.value.trim();

    if (msg === '') {
        elements.status.textContent = 'יש לכתוב הודעה';
        return;
    }

    elements.status.textContent = 'שולח...';

    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                to_id: userId,
                message: msg
            }).toString()
        });

        const result = await response.json();

        if (!result.ok) {
            elements.status.textContent = result.message || 'שליחה נכשלה';
            return;
        }

        elements.text.value = '';
        elements.status.textContent = '';
        await loadMessageHistory(userId, true, false);
    } catch (err) {
        elements.status.textContent = 'אירעה שגיאה בשליחה';
        console.error(err);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    refreshMessagesBadge();

    if (!badgeRefreshTimer) {
        badgeRefreshTimer = setInterval(refreshMessagesBadge, 5000);
    }
});

document.addEventListener('click', async function (e) {
    const leftEditBtn = e.target.closest('.left-edit-btn');
    const leftCancelBtn = e.target.closest('.profile-left-cancel-btn');
    const leftSaveBtn = e.target.closest('.profile-left-save-btn');

    if (leftEditBtn) {
        const block = leftEditBtn.closest('.profile-block');
        if (!block) return;

        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');

        if (viewMode) viewMode.style.display = 'none';
        if (editMode) editMode.style.display = 'block';
        leftEditBtn.style.display = 'none';
        return;
    }

    if (leftCancelBtn) {
        const block = leftCancelBtn.closest('.profile-block');
        if (!block) return;

        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');
        const editBtn = block.querySelector('.left-edit-btn');
        const input = block.querySelector('.profile-left-edit-input');
        const textarea = block.querySelector('.profile-left-edit-textarea');
        const status = block.querySelector('.profile-left-edit-status');

        if (input) input.value = input.defaultValue;
        if (textarea) textarea.value = textarea.defaultValue;
        if (status) status.textContent = '';

        if (editMode) editMode.style.display = 'none';
        if (viewMode) viewMode.style.display = 'block';
        if (editBtn) editBtn.style.display = 'inline-block';
        return;
    }

    if (leftSaveBtn) {
        const block = leftSaveBtn.closest('.profile-block');
        if (!block) return;

        const field = block.dataset.field;
        const input = block.querySelector('.profile-left-edit-input');
        const textarea = block.querySelector('.profile-left-edit-textarea');
        const status = block.querySelector('.profile-left-edit-status');
        const editBtn = block.querySelector('.left-edit-btn');
        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');

        let value = '';
        if (input) value = input.value;
        if (textarea) value = textarea.value;

        if (status) status.textContent = 'שומר...';

        try {
            const response = await fetch('save_profile_field.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({
                    id: '<?= (int)$user['Id'] ?>',
                    field: field,
                    value: value
                }).toString()
            });

            const result = await response.json();

            if (!result.ok) {
                if (status) status.textContent = result.message || 'שמירה נכשלה';
                return;
            }

            if (viewMode) {
                viewMode.innerHTML = value.trim() !== ''
                    ? value
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;')
                        .replace(/\n/g, '<br>')
                    : '<span class="empty-inline-note">אין מידע עדיין</span>';
            }

            if (input) input.defaultValue = value;
            if (textarea) textarea.defaultValue = value;

            if (status) status.textContent = '';
            if (editMode) editMode.style.display = 'none';
            if (viewMode) viewMode.style.display = 'block';
            if (editBtn) editBtn.style.display = 'inline-block';
        } catch (err) {
            if (status) status.textContent = 'אירעה שגיאה בשמירה';
            console.error(err);
        }
    }
});

const saveRightProfileBtn = document.getElementById('saveRightProfileBtn');

if (saveRightProfileBtn) {
    saveRightProfileBtn.addEventListener('click', async function () {
        const status = document.getElementById('profileRightEditStatus');
        const form = document.getElementById('profileRightForm');
        const fields = form ? form.querySelectorAll('input[name], select[name]') : [];

        if (status) status.textContent = 'שומר...';

        try {
            for (const field of fields) {
                const response = await fetch('save_profile_field.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        id: '<?= (int)$user['Id'] ?>',
                        field: field.name,
                        value: field.value
                    }).toString()
                });

                const result = await response.json();

                if (!result.ok) {
                    if (status) status.textContent = result.message || ('שמירה נכשלה בשדה: ' + field.name);
                    return;
                }
            }

            window.location.href = '?page=profile&id=<?= (int)$user['Id'] ?>';
        } catch (err) {
            if (status) status.textContent = 'אירעה שגיאה בשמירה';
            console.error(err);
        }
    });
}
</script>