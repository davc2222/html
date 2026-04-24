<?php

/**
 * inbox.php
 * דף תיבת הדואר הראשי
 */

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

    <div class="inbox-conversations">
        <div class="inbox-conversations-header">שיחות</div>

        <div id="inboxConversationsList">
            <div class="inbox-empty">טוען שיחות...</div>
        </div>
    </div>

    <div class="inbox-chat">

        <div class="inbox-chat-header" id="inboxChatHeader">
            <div class="inbox-empty">בחר שיחה</div>
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
                <button type="submit">שלח</button>
            </form>

            <div class="inbox-enter-row">
                <label class="inbox-enter-label" for="inboxEnterToggle">
                    <input type="checkbox" id="inboxEnterToggle">
                    <span>שלח עם Enter</span>
                </label>
                <div class="inbox-enter-hint"שלח עם Enter</div>
            </div>
        </div>

    </div>

</div>

<script>
    let inboxCurrentUserId = <?= $selectedUserId ?>;
    let inboxCurrentName = '';
    let inboxTypingStopTimer = null;
    let inboxTypingPollTimer = null;
    let inboxTypingActive = false;

    function lmGetEnterSendEnabled() {
        return localStorage.getItem('lm_send_on_enter') === '1';
    }

    function lmSetEnterSendEnabled(enabled) {
        localStorage.setItem('lm_send_on_enter', enabled ? '1' : '0');
    }

    function inboxEscapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, function(m) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[m];
        });
    }

    function inboxSetActiveConversation(userId) {
        document.querySelectorAll('.inbox-conversation-item').forEach(el => {
            const itemUserId = parseInt(el.getAttribute('data-user-id') || '0', 10);
            el.classList.toggle('active', itemUserId === parseInt(userId || 0, 10));
        });
    }

    function inboxBindConversationClicks() {
        document.querySelectorAll('.inbox-conversation-item').forEach(item => {
            if (item.dataset.bound === '1') return;
            item.dataset.bound = '1';

            item.addEventListener('click', function(e) {

                // 🔥 אם לחצו על התמונה → אל תפתח שיחה
                if (e.target.closest('a')) {
                    return;
                }

                const userId = parseInt(this.getAttribute('data-user-id') || '0', 10);
                const name =
                    this.getAttribute('data-name') ||
                    (this.querySelector('.inbox-conversation-name') ?
                        this.querySelector('.inbox-conversation-name').textContent.trim() :
                        '');

                inboxOpenConversation(userId, name);
            });
        });
    }

    function inboxLoadConversations() {
        fetch('/mobile/inbox_get_conversations.php')
            .then(r => r.text())
            .then(html => {
                document.getElementById('inboxConversationsList').innerHTML = html;
                inboxBindConversationClicks();
                inboxSetActiveConversation(inboxCurrentUserId);
            });
    }

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

    function inboxMarkRead() {
        if (!inboxCurrentUserId) return;

        fetch('/inbox_mark_read.php?user_id=' + inboxCurrentUserId)
            .then(() => {
                if (typeof updateHeaderBadges === 'function') {
                    updateHeaderBadges();
                }
            })
            .catch(() => {});
    }

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

                if (data && data.typing) {
                    el.style.display = 'block';
                } else {
                    el.style.display = 'none';
                }
            })
            .catch(() => {});
    }

    function inboxOpenConversation(userId, name = '') {
        inboxCurrentUserId = parseInt(userId || 0, 10);
        inboxCurrentName = name || '';

        document.getElementById('inboxChatHeader').innerHTML = inboxCurrentName ?
            ('שיחה עם ' + inboxEscapeHtml(inboxCurrentName)) :
            'בחר שיחה';

        const typingEl = document.getElementById('inboxTypingIndicator');
        if (typingEl) {
            typingEl.style.display = 'none';
        }

        if (inboxTypingPollTimer) {
            clearInterval(inboxTypingPollTimer);
        }

        inboxTypingPollTimer = setInterval(inboxPollTyping, 2000);

        inboxSetActiveConversation(inboxCurrentUserId);

        inboxMarkRead();
        inboxLoadMessages();
        inboxLoadConversations();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inboxSendForm = document.getElementById('inboxSendForm');
        const inboxMessageInput = document.getElementById('inboxMessageInput');
        const inboxEnterToggle = document.getElementById('inboxEnterToggle');

        if (inboxEnterToggle) {
            inboxEnterToggle.checked = lmGetEnterSendEnabled();

            inboxEnterToggle.addEventListener('change', function() {
                lmSetEnterSendEnabled(this.checked);
            });
        }

        inboxSendForm.addEventListener('submit', function(e) {
            e.preventDefault();

            let input = document.getElementById('inboxMessageInput');
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

                if (typeof updateHeaderBadges === 'function') {
                    updateHeaderBadges();
                }
            });
        });

        inboxMessageInput.addEventListener('focus', function() {
            if (!inboxCurrentUserId) return;
            inboxMarkRead();
            inboxLoadConversations();
        });

        inboxMessageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && lmGetEnterSendEnabled()) {
                e.preventDefault();
                inboxSendForm.dispatchEvent(new Event('submit'));
            }
        });

        inboxMessageInput.addEventListener('input', function() {
            if (!inboxCurrentUserId) return;

            if (!inboxTypingActive) {
                inboxTypingActive = true;
                inboxSetTyping(true);
            }

            if (inboxTypingStopTimer) {
                clearTimeout(inboxTypingStopTimer);
            }

            inboxTypingStopTimer = setTimeout(() => {
                inboxTypingActive = false;
                inboxSetTyping(false);
            }, 1500);
        });

        inboxLoadConversations();

        if (inboxCurrentUserId) {
            inboxLoadMessages();
            inboxTypingPollTimer = setInterval(inboxPollTyping, 2000);
        }

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
    /* ===== CONTAINER ===== */
    .inbox-page {
        display: flex;
        width: 100%;
        max-width: 1200px;
        height: 78vh;
        margin: 20px auto;
        border: 1px solid #d8dadd;
        border-radius: 22px;
        overflow: hidden;
        background: #f4f5f7;
        box-sizing: border-box;
    }

    /* ===== LEFT - CONVERSATIONS ===== */
    .inbox-conversations {
        width: 150px;
        min-width: 150px;
        max-width: 180px;
        border-left: 1px solid #d9dde3;
        background: #fff;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .inbox-conversations-header {
        padding: 16px;
        font-weight: 700;
        font-size: 20px;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
    }

    #inboxConversationsList {
        flex: 1;
        overflow-y: auto;
    }

    /* ===== CONVERSATION ITEM ===== */
    .inbox-conversation-item {
        padding: 12px;
        border-bottom: 1px solid #edf0f3;
        cursor: pointer;
        transition: 0.2s;
    }

    .inbox-conversation-item:hover {
        background: #f1f3f5;
    }

    .inbox-conversation-item.active {
        background: #e7eaee;
    }

    .inbox-conversation-main {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .inbox-conversation-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
    }

    .inbox-conversation-content {
        flex: 1;
        min-width: 0;
        text-align: right;
    }

    .inbox-conversation-name {
        font-size: 14px;
        font-weight: 700;
    }

    .inbox-conversation-preview {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== RIGHT - CHAT ===== */
    .inbox-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f7f8fa;
    }

    .inbox-chat-header {
        padding: 16px;
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        border-bottom: 1px solid #e2e5e9;
    }

    /* ===== MESSAGES ===== */
    .inbox-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f3f4f6;
        direction: rtl;
    }

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

    /* 🔥 הרחבנו בועות */
    .inbox-message {
        max-width: 82%;
        padding: 10px 12px;
        border-radius: 14px;
    }

    .inbox-message-me {
        background: #dfeadf;
    }

    .inbox-message-other {
        background: #fff;
    }

    .inbox-message-text {
        font-size: 14px;
    }

    /* ===== TYPING ===== */
    .inbox-typing-indicator {
        padding: 6px 12px;
        font-size: 13px;
        color: #6b7280;
        background: #fff;
    }

    /* ===== SEND BOX ===== */
    .inbox-send-box {
        padding: 10px;
        background: #fff;
        border-top: 1px solid #dfe3e8;
    }

    .inbox-send-box form {
        display: flex;
        gap: 6px;
    }

    .inbox-send-box input {
        flex: 1;
        height: 40px;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 0 10px;
    }

    .inbox-send-box button {
        background: #e86a7a;
        color: #fff;
        border: none;
        padding: 0 14px;
        border-radius: 8px;
    }

    /* ===== MOBILE ===== */
    @media (max-width: 900px) {

        /* ❗ שומרים על שני טורים */
        .inbox-page {
            flex-direction: row;
            height: calc(100vh - 160px);
            margin: 10px;
        }

        .inbox-conversations {
            width: 38%;
            min-width: 140px;
            max-width: 180px;
        }

        .inbox-chat {
            flex: 1;
        }

        .inbox-message {
            max-width: 88%;
        }
    }

    .inbox-unread-count {
        color: #e11d48;
        font-weight: 700;
    }
</style>