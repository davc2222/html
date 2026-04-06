<?php
// ===== FILE: includes/chat_windows.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$chatViewerId = (int)($_SESSION['user_id'] ?? 0);
$chatViewerName = 'אני';
$chatViewerImage = '/images/no_photo.jpg';

if ($chatViewerId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT Name
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $chatViewerId]);
        $chatViewerName = trim((string)$stmt->fetchColumn()) ?: 'אני';
    } catch (Throwable $e) {
        // ignore
    }

    try {
        $picStmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $picStmt->execute([':id' => $chatViewerId]);
        $picName = $picStmt->fetchColumn();

        if ($picName) {
            $chatViewerImage = '/uploads/' . ltrim((string)$picName, '/');
        }
    } catch (Throwable $e) {
        // ignore
    }
}
?>

<div class="chat-window" id="chatWindow" hidden>
    <div class="chat-window-header">
        <div class="chat-window-user">
            <img id="chatTargetImage" class="chat-window-avatar" src="/images/no_photo.jpg" alt="">

            <div class="chat-window-user-text">
                <a id="chatTargetNameLink" class="chat-window-name-link" href="#">
                    <div id="chatTargetName" class="chat-window-name">צ'אט</div>
                </a>
                <div id="chatHeaderTitle" class="chat-window-subtitle">היסטוריית הודעות</div>
            </div>
        </div>

        <button type="button" class="chat-window-close" id="chatCloseBtn">✕</button>
    </div>

    <div class="chat-window-body" id="chatMessages"></div>

    <div class="chat-window-footer">
        <textarea
            id="chatText"
            class="chat-window-textarea"
            rows="2"
            placeholder="כתוב הודעה..."></textarea>

        <button
            type="button"
            class="chat-window-send"
            onclick="sendMessage()">שלח</button>
    </div>

    <div class="chat-window-status" id="chatStatus"></div>
</div>

<style>
    /* =========================
   בסיס
========================= */

    .chat-window[hidden] {
        display: none !important;
    }

    /* =========================
   חלון ראשי
========================= */

    .chat-window {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 380px;
        max-width: calc(100vw - 24px);
        height: 520px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.20);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        direction: rtl;
    }

    /* =========================
   HEADER
========================= */

    .chat-window-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 14px 10px;
        background: linear-gradient(135deg, #d91f4f, #b9153f);
        border-top-right-radius: 18px;
        border-top-left-radius: 18px;
        overflow: hidden;
    }

    .chat-window-user {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .chat-window-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid rgba(255, 255, 255, 0.45);
    }

    .chat-window-user-text {
        min-width: 0;
    }

    /* לינק לשם */
    .chat-window-name-link {
        color: inherit;
        text-decoration: none;
        display: inline-block;
    }

    .chat-window-name-link:hover .chat-window-name {
        text-decoration: underline;
        cursor: pointer;
    }

    /* שם */
    .chat-window-name {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* "מקליד..." */
    .chat-window-subtitle {
        min-height: 18px;
        font-size: 12px;
        color: #fff;
        margin-top: 2px;
        opacity: 0.95;
    }

    /* כפתור סגירה */
    .chat-window-close {
        border: none;
        background: transparent;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        width: 34px;
        height: 34px;
        border-radius: 50%;
    }

    /* =========================
   BODY (הודעות)
========================= */

    .chat-window-body {
        flex: 1;
        overflow-y: auto;
        background: #f7f7f8;
        padding: 14px;
    }

    /* אין הודעות */
    .cw-empty {
        text-align: center;
        color: #777;
        padding: 24px 10px;
        font-size: 14px;
    }

    /* שורה */
    .cw-row {
        display: flex;
        width: 100%;
        margin-bottom: 10px;
    }

    /* שלי */
    .cw-row-me {
        justify-content: flex-start;
    }

    /* של השני */
    .cw-row-other {
        justify-content: flex-end;
    }

    /* עטיפה */
    .cw-bubble-wrap {
        max-width: 78%;
    }

    /* בועה */
    .cw-bubble {
        padding: 10px 12px;
        border-radius: 16px;
        line-height: 1.55;
        word-break: break-word;
        white-space: pre-wrap;
        font-size: 14px;
        color: #222;
    }

    /* שלי */
    .cw-row-me .cw-bubble {
        background: #e5e7eb;
        border-bottom-left-radius: 6px;
    }

    /* של השני */
    .cw-row-other .cw-bubble {
        background: #ffd7e2;
        border-bottom-right-radius: 6px;
    }

    /* זמן */
    .cw-time {
        margin-top: 4px;
        font-size: 11px;
        color: #888;
    }

    /* יישור זמן */
    .cw-row-me .cw-time {
        text-align: right;
    }

    .cw-row-other .cw-time {
        text-align: left;
    }

    /* =========================
   FOOTER (שליחה)
========================= */

    .chat-window-footer {
        border-top: 1px solid #ececec;
        background: #fff;
        padding: 12px;
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .chat-window-textarea {
        flex: 1;
        resize: none;
        min-height: 46px;
        max-height: 140px;
        border: 1px solid #dcdcdc;
        border-radius: 14px;
        padding: 10px 12px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
    }

    .chat-window-send {
        min-width: 78px;
        height: 46px;
        border: none;
        border-radius: 14px;
        background: #d91f4f;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }

    /* =========================
   STATUS
========================= */

    .chat-window-status {
        min-height: 18px;
        padding: 0 12px 10px;
        color: #d91f4f;
        font-size: 13px;
        background: #fff;
    }

    /* =========================
   מובייל
========================= */

    @media (max-width: 768px) {
        .chat-window {
            right: 10px;
            left: 10px;
            bottom: 10px;
            width: auto;
            height: 70vh;
        }
    }
</style>

<script>
    window.chatViewer = {
        id: <?= (int)$chatViewerId ?>,
        name: <?= json_encode($chatViewerName, JSON_UNESCAPED_UNICODE) ?>,
        image: <?= json_encode($chatViewerImage, JSON_UNESCAPED_UNICODE) ?>
    };

    if (typeof window.currentChatUserId === 'undefined') {
        window.currentChatUserId = 0;
    }

    let typingStopTimer = null;
    let typingHeartbeatTimer = null;
    let typingPollTimer = null;
    let messagePollTimer = null;
    let messagesMarked = false;

    function chatScrollToBottom() {
        const box = document.getElementById('chatMessages');
        if (!box) return;
        box.scrollTop = box.scrollHeight;
    }

    function setChatHeaderTyping(isTyping) {
        const title = document.getElementById('chatHeaderTitle');
        if (!title) return;
        title.textContent = isTyping ? 'מקליד...' : 'היסטוריית הודעות';
    }

    function openMessageModal(userId, userName, userImage) {
        currentChatUserId = Number(userId || 0);
        if (!currentChatUserId) return;

        messagesMarked = false;

        const targetName = document.getElementById('chatTargetName');
        const targetNameLink = document.getElementById('chatTargetNameLink');
        const targetImage = document.getElementById('chatTargetImage');
        const statusBox = document.getElementById('chatStatus');
        const textBox = document.getElementById('chatText');
        const box = document.getElementById('chatMessages');
        const win = document.getElementById('chatWindow');

        if (targetName) {
            targetName.textContent = userName || 'משתמש';
        }

        if (targetNameLink) {
            targetNameLink.href = '/?page=profile&id=' + encodeURIComponent(currentChatUserId);
        }

        if (targetImage) {
            targetImage.src = userImage || '/images/no_photo.jpg';
        }

        if (statusBox) statusBox.textContent = '';
        if (textBox) textBox.value = '';
        setChatHeaderTyping(false);

        if (box) {
            box.dataset.loadedOnce = '';
        }

        if (win) {
            win.hidden = false;
            win.style.display = 'flex';
        }

        loadChatMessages();
        startChatPolling();
    }

    function closeChat() {
        stopChatPolling();
        clearTypingState();

        const win = document.getElementById('chatWindow');
        const statusBox = document.getElementById('chatStatus');
        const textBox = document.getElementById('chatText');
        const messagesBox = document.getElementById('chatMessages');

        if (win) {
            win.hidden = true;
            win.style.display = 'none';
        }

        if (statusBox) statusBox.textContent = '';
        if (textBox) textBox.value = '';
        if (messagesBox) messagesBox.innerHTML = '';

        setChatHeaderTyping(false);
        currentChatUserId = 0;
        messagesMarked = false;
    }

    function loadChatMessages() {
        if (!currentChatUserId) return;

        fetch('/get_chat_messages.php?user_id=' + encodeURIComponent(currentChatUserId))
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (!data.ok) return;

                const box = document.getElementById('chatMessages');
                if (!box) return;

                const nearBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 80;

                box.innerHTML = data.html || '';

                if (nearBottom || !box.dataset.loadedOnce) {
                    chatScrollToBottom();
                }

                box.dataset.loadedOnce = '1';
            })
            .catch(function() {});
    }

    function sendMessage() {
        if (!currentChatUserId) return;

        const textBox = document.getElementById('chatText');
        const statusBox = document.getElementById('chatStatus');

        if (!textBox || !statusBox) return;

        const text = textBox.value.trim();
        if (!text) return;

        statusBox.textContent = 'שולח...';

        const formData = new FormData();
        formData.append('to', currentChatUserId);
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
                    return;
                }

                textBox.value = '';
                statusBox.textContent = '';
                clearTypingState();
                setChatHeaderTyping(false);
                loadChatMessages();
            })
            .catch(function() {
                statusBox.textContent = 'שגיאת תקשורת';
            });
    }

    function notifyTyping(isTyping) {
        if (!currentChatUserId) return;

        const formData = new FormData();
        formData.append('target_id', currentChatUserId);
        formData.append('is_typing', isTyping ? '1' : '0');

        fetch('/set_typing.php', {
            method: 'POST',
            body: formData
        }).catch(function() {});
    }

    function startTypingHeartbeat() {
        if (typingHeartbeatTimer) return;

        notifyTyping(true);

        typingHeartbeatTimer = setInterval(function() {
            notifyTyping(true);
        }, 2000);
    }

    function stopTypingHeartbeat() {
        if (typingHeartbeatTimer) {
            clearInterval(typingHeartbeatTimer);
            typingHeartbeatTimer = null;
        }
    }

    function scheduleTypingStop() {
        if (typingStopTimer) {
            clearTimeout(typingStopTimer);
        }

        typingStopTimer = setTimeout(function() {
            clearTypingState();
        }, 2500);
    }

    function clearTypingState() {
        if (typingStopTimer) {
            clearTimeout(typingStopTimer);
            typingStopTimer = null;
        }

        stopTypingHeartbeat();
        notifyTyping(false);
    }

    function pollTypingStatus() {
        if (!currentChatUserId) return;

        fetch('/get_typing.php?user_id=' + encodeURIComponent(currentChatUserId))
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data.ok && data.typing) {
                    setChatHeaderTyping(true);
                } else {
                    setChatHeaderTyping(false);
                }
            })
            .catch(function() {});
    }

    function markMessagesAsRead() {
        if (!currentChatUserId || messagesMarked) return;

        messagesMarked = true;

        const formData = new FormData();
        formData.append('user_id', currentChatUserId);

        fetch('/mark_messages_read.php', {
            method: 'POST',
            body: formData
        }).catch(function() {});
    }

    function startChatPolling() {
        stopChatPolling();

        messagePollTimer = setInterval(function() {
            loadChatMessages();
        }, 2500);

        typingPollTimer = setInterval(function() {
            pollTypingStatus();
        }, 1200);

        pollTypingStatus();
    }

    function stopChatPolling() {
        if (messagePollTimer) {
            clearInterval(messagePollTimer);
            messagePollTimer = null;
        }

        if (typingPollTimer) {
            clearInterval(typingPollTimer);
            typingPollTimer = null;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const textBox = document.getElementById('chatText');
        const win = document.getElementById('chatWindow');
        const closeBtn = document.getElementById('chatCloseBtn');

        win.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        if (win) {
            win.hidden = true;
            win.style.display = 'none';
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeChat();
            });
        }

        if (textBox) {
            textBox.addEventListener('focus', function() {
                markMessagesAsRead();
            });

            textBox.addEventListener('input', function() {
                if (!currentChatUserId) return;

                if (textBox.value.trim() === '') {
                    clearTypingState();
                    return;
                }

                startTypingHeartbeat();
                scheduleTypingStop();
            });

            textBox.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    });
</script>