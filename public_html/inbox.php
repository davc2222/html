<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /?page=login");
    exit;
}

$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
?>

<div class="inbox-page">

    <!-- צ'אט (שמאל) -->
    <div class="inbox-chat">

        <div class="inbox-chat-header" id="inboxChatHeader">
            <div class="inbox-empty">שיחות</div>
        </div>

        <div class="inbox-messages" id="inboxMessages">
            <div class="inbox-empty">אין הודעות</div>
        </div>

        <div class="inbox-typing-indicator" id="inboxTypingIndicator" style="display:none;">
            מקליד...
        </div>

        <div class="inbox-send-box">
            <form id="inboxSendForm">
                <input type="text" id="inboxMessageInput" placeholder="כתוב הודעה..." autocomplete="off">
                <button type="submit">➤</button>
            </form>

            <div class="inbox-enter-row">
                <label class="inbox-enter-label">
                    <input type="checkbox" id="inboxEnterToggle">
                    <span>שלח עם Enter</span>
                </label>
                <div class="inbox-enter-hint">Enter לא ישלח אם לא מסומן</div>
            </div>
        </div>

    </div>

    <!-- שיחות (ימין) -->
    <div class="inbox-conversations">

        <div class="inbox-conversations-header">תיבת דואר</div>

        <div id="inboxConversationsList">
            <div class="inbox-empty">טוען שיחות...</div>
        </div>

    </div>

</div>

<script>
    let inboxCurrentUserId = <?= $selectedUserId ?>;
    let inboxTypingStopTimer = null;
    let inboxTypingPollTimer = null;
    let inboxTypingActive = false;

    /* ===== Enter toggle ===== */
    function lmGetEnterSendEnabled() {
        return localStorage.getItem('lm_send_on_enter') === '1';
    }

    function lmSetEnterSendEnabled(enabled) {
        localStorage.setItem('lm_send_on_enter', enabled ? '1' : '0');
    }

    /* ===== Load conversations ===== */
    function inboxLoadConversations() {
        fetch('/inbox_get_conversations.php')
            .then(r => r.text())
            .then(html => {
                document.getElementById('inboxConversationsList').innerHTML = html;

                document.querySelectorAll('.inbox-conversation-item').forEach(item => {
                    item.onclick = function() {
                        const userId = this.getAttribute('data-user-id');
                        const name = this.getAttribute('data-name') || '';
                        inboxOpenConversation(userId, name);
                    };
                });
            });
    }

    /* ===== Load messages ===== */
    function inboxLoadMessages() {
        if (!inboxCurrentUserId) return;

        fetch('/inbox_get_messages.php?user_id=' + inboxCurrentUserId)
            .then(r => r.text())
            .then(html => {
                let box = document.getElementById('inboxMessages');
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight;
            });
    }

    /* ===== Mark read ===== */
    function inboxMarkRead() {
        if (!inboxCurrentUserId) return;
        fetch('/inbox_mark_read.php?user_id=' + inboxCurrentUserId).catch(() => {});
    }

    /* ===== Typing ===== */
    function inboxSetTyping(isTyping) {
        if (!inboxCurrentUserId) return;

        fetch('/inbox_set_typing.php', {
            method: 'POST',
            body: new URLSearchParams({
                to_user_id: inboxCurrentUserId,
                is_typing: isTyping ? 1 : 0
            })
        }).catch(() => {});
    }

    function inboxPollTyping() {
        if (!inboxCurrentUserId) return;

        fetch('/inbox_get_typing.php?user_id=' + inboxCurrentUserId)
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('inboxTypingIndicator');
                if (!el) return;
                el.style.display = data.typing ? 'block' : 'none';
            })
            .catch(() => {});
    }

    /* ===== Open conversation ===== */
    function inboxOpenConversation(userId, name = '') {
        inboxCurrentUserId = parseInt(userId || 0, 10);

        document.getElementById('inboxChatHeader').innerText =
            name ? ('שיחה עם ' + name) : 'בחר שיחה';

        if (inboxTypingPollTimer) clearInterval(inboxTypingPollTimer);
        inboxTypingPollTimer = setInterval(inboxPollTyping, 2000);

        inboxMarkRead();
        inboxLoadMessages();
        inboxLoadConversations();
    }

    /* ===== DOM Ready ===== */
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('inboxSendForm');
        const input = document.getElementById('inboxMessageInput');
        const toggle = document.getElementById('inboxEnterToggle');

        if (toggle) {
            toggle.checked = lmGetEnterSendEnabled();
            toggle.addEventListener('change', () => lmSetEnterSendEnabled(toggle.checked));
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            let text = input.value.trim();
            if (!text || !inboxCurrentUserId) return;

            fetch('/inbox_send_message.php', {
                method: 'POST',
                body: new URLSearchParams({
                    to_user_id: inboxCurrentUserId,
                    message: text
                })
            }).then(() => {
                input.value = '';
                inboxTypingActive = false;
                inboxSetTyping(false);
                inboxLoadMessages();
                inboxLoadConversations();
            });
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && lmGetEnterSendEnabled()) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });

        input.addEventListener('input', function() {
            if (!inboxCurrentUserId) return;

            if (!inboxTypingActive) {
                inboxTypingActive = true;
                inboxSetTyping(true);
            }

            clearTimeout(inboxTypingStopTimer);
            inboxTypingStopTimer = setTimeout(() => {
                inboxTypingActive = false;
                inboxSetTyping(false);
            }, 1500);
        });

        inboxLoadConversations();

        setInterval(inboxLoadConversations, 8000);

        setInterval(() => {
            if (inboxCurrentUserId) {
                inboxMarkRead();
                inboxLoadMessages();
            }
        }, 4000);
    });
</script>

<style>
    /* ===== Layout ===== */
    .inbox-page {
        display: flex;
        flex-direction: row;
        direction: ltr;
        max-width: 1200px;
        height: 80vh;
        margin: 20px auto;
        border-radius: 20px;
        overflow: hidden;
        background: #eef2f6;
        border: 1px solid #d9dee5;
    }

    .inbox-chat,
    .inbox-conversations {
        direction: rtl;
    }

    /* ===== Right side ===== */
    .inbox-conversations {
        width: 300px;
        background: #ffffff;
        border-left: 1px solid #e3e7ec;
        display: flex;
        flex-direction: column;
    }

    .inbox-conversations-header {
        padding: 18px;
        font-size: 20px;
        font-weight: 700;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    /* ===== Items ===== */
    .inbox-conversation-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-bottom: 1px solid #f1f3f6;
        cursor: pointer;
    }

    .inbox-conversation-item:hover {
        background: #f4f7fb;
    }

    .inbox-conversation-item.active {
        background: #e6f0ff;
    }

    .inbox-conversation-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
    }

    .inbox-conversation-content {
        flex: 1;
        text-align: right;
    }

    .inbox-conversation-name {
        font-weight: 700;
    }

    .inbox-conversation-preview {
        font-size: 13px;
        color: #6b7280;
    }

    /* ===== Chat ===== */
    .inbox-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
    }

    .inbox-chat-header {
        padding: 18px;
        text-align: center;
        font-weight: 700;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }

    .inbox-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f1f5f9;
    }

    /* ===== Messages ===== */
    .inbox-message-row {
        display: flex;
        margin-bottom: 10px;
    }

    .inbox-message-row-me {
        justify-content: flex-start;
    }

    .inbox-message-row-other {
        justify-content: flex-end;
    }

    .inbox-message {
        max-width: 60%;
        padding: 12px;
        border-radius: 14px;
    }

    .inbox-message-me {
        background: #dbeafe;
    }

    .inbox-message-other {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }

    /* ===== Send ===== */
    .inbox-send-box {
        padding: 10px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .inbox-send-box form {
        display: flex;
        gap: 8px;
    }

    .inbox-send-box input {
        flex: 1;
        height: 44px;
        border-radius: 22px;
        border: 1px solid #d1d5db;
        padding: 0 16px;
    }

    .inbox-send-box button {
        width: 44px;
        border-radius: 50%;
        border: none;
        background: #2563eb;
        color: white;
        cursor: pointer;
    }

    /* ===== Misc ===== */
    .inbox-empty {
        padding: 20px;
        text-align: center;
        color: #9ca3af;
    }

    .inbox-enter-row {
        font-size: 12px;
        margin-top: 6px;
        display: flex;
        justify-content: space-between;
    }

    .inbox-typing-indicator {
        font-size: 12px;
        padding: 6px 15px;
        color: #6b7280;
    }
</style>