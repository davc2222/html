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
        </div>

    </div>

</div>

</style>

<script>
    let inboxCurrentUserId = <?= $selectedUserId ?>;
    let inboxCurrentName = '';
    let inboxTypingStopTimer = null;
    let inboxTypingPollTimer = null;
    let inboxTypingActive = false;

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

            item.addEventListener('click', function() {
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

    /* שיחות */
    function inboxLoadConversations() {
        fetch('/inbox_get_conversations.php')
            .then(r => r.text())
            .then(html => {
                document.getElementById('inboxConversationsList').innerHTML = html;
                inboxBindConversationClicks();
                inboxSetActiveConversation(inboxCurrentUserId);
            });
    }

    /* הודעות */
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

    /* סמן כנקרא */
    function inboxMarkRead() {
        if (!inboxCurrentUserId) return;
        fetch('/inbox_mark_read.php?user_id=' + inboxCurrentUserId);
    }

    /* שליחת סטטוס הקלדה */
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

    /* בדיקת סטטוס הקלדה */
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

    /* פתיחת שיחה */
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

        document.getElementById('inboxSendForm').addEventListener('submit', function(e) {
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
            });
        });

        document.getElementById('inboxMessageInput').addEventListener('focus', function() {
            if (!inboxCurrentUserId) return;
            inboxMarkRead();
            inboxLoadConversations();
        });

        document.getElementById('inboxMessageInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('inboxSendForm').dispatchEvent(new Event('submit'));
            }
        });

        document.getElementById('inboxMessageInput').addEventListener('input', function() {
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

    /* צד ימין - שיחות */
    .inbox-conversations {
        width: 260px;
        min-width: 260px;
        border-left: 1px solid #d9dde3;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .inbox-conversations-header {
        padding: 18px 16px;
        font-weight: 700;
        font-size: 22px;
        color: #1f2937;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
        flex-shrink: 0;
        text-align: right;
    }

    #inboxConversationsList {
        flex: 1;
        overflow-y: auto;
        background: #ffffff;
    }

    .inbox-conversation-item {
        padding: 14px 14px 12px;
        cursor: pointer;
        border-bottom: 1px solid #edf0f3;
        transition: background 0.2s ease;
        background: #ffffff;
    }

    .inbox-conversation-item:hover {
        background: #f5f6f8;
    }

    .inbox-conversation-item.active {
        background: #e8ebef;
    }

    .inbox-unread {
        background: #f3f6f9;
    }

    .inbox-conversation-item.active.inbox-unread {
        background: #dde3e9;
    }

    .inbox-conversation-date {
        font-size: 13px;
        color: #6b7280;
        text-align: right;
        margin-bottom: 10px;
    }

    .inbox-conversation-main {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inbox-conversation-avatar {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
        background: #fff;
        border: 1px solid #dfe3e8;
    }

    .inbox-conversation-content {
        flex: 1;
        min-width: 0;
        text-align: right;
    }

    .inbox-conversation-name {
        font-weight: 700;
        font-size: 15px;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .inbox-conversation-preview {
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* צד שמאל - צ'אט */
    .inbox-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #f7f7f8;
    }

    .inbox-chat-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e2e5e9;
        font-weight: 700;
        font-size: 20px;
        color: #1f2937;
        background: #ffffff;
        flex-shrink: 0;
        text-align: center;
    }

    .inbox-messages {
        flex: 1;
        overflow-y: auto;
        padding: 22px 24px;
        background: #f3f4f6;
        direction: rtl;
        min-height: 0;
    }

    .inbox-message-row {
        display: flex;
        margin-bottom: 14px;
    }

    .inbox-message-row-me {
        justify-content: flex-start;
    }

    .inbox-message-row-other {
        justify-content: flex-end;
    }

    .inbox-message {
        max-width: 64%;
        padding: 12px 14px;
        border-radius: 16px;
        text-align: right;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .inbox-message-me {
        background: #dbe7d8;
        color: #243224;
        border: 1px solid #cfdbc9;
    }

    .inbox-message-other {
        background: #ffffff;
        color: #1f2937;
        border: 1px solid #e1e5ea;
    }

    .inbox-message-sender {
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #475569;
    }

    .inbox-message-text {
        font-size: 15px;
        line-height: 1.45;
        word-break: break-word;
    }

    .inbox-message-meta {
        font-size: 11px;
        color: #6b7280;
        margin-top: 6px;
        display: flex;
        gap: 8px;
    }

    .inbox-typing-indicator {
        padding: 6px 18px 10px;
        font-size: 13px;
        color: #6b7280;
        background: #ffffff;
        border-top: 1px solid #eceff2;
        min-height: 22px;
    }

    .inbox-send-box {
        padding: 12px;
        border-top: 1px solid #dfe3e8;
        background: #ffffff;
        flex-shrink: 0;
    }

    .inbox-send-box form {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .inbox-send-box input {
        flex: 1;
        height: 44px;
        padding: 0 14px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #f9fafb;
        color: #111827;
        font-size: 14px;
        outline: none;
    }

    .inbox-send-box input:focus {
        border-color: #b7bec8;
        background: #ffffff;
    }

    .inbox-send-box button {
        height: 44px;
        min-width: 64px;
        padding: 0 18px;
        border: none;
        background: #e86a7a;
        color: white;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: 14px;
    }

    .inbox-send-box button:hover {
        background: #d85b6b;
    }

    .inbox-empty {
        padding: 28px 20px;
        text-align: center;
        color: #8a94a3;
        font-size: 15px;
    }

    /* יישור כותרות */
    .inbox-page {
        align-items: stretch;
    }

    .inbox-conversations-header,
    .inbox-chat-header {
        height: 72px;
        display: flex;
        align-items: center;
        box-sizing: border-box;
    }

    .inbox-conversations-header {
        justify-content: flex-end;
    }

    .inbox-chat-header {
        justify-content: center;
    }

    .inbox-conversations-header {
        justify-content: center;
        text-align: center;
    }


    /* רקע כללי עדין */
    .inbox-page {
        background: #f6f7f9;
    }

    /* רשימת שיחות */
    .inbox-conversations {
        background: #ffffff;
    }

    /* פריט שיחה */
    .inbox-conversation-item {
        background: #ffffff;
    }

    .inbox-conversation-item:hover {
        background: #f1f3f5;
    }

    .inbox-conversation-item.active {
        background: #e7eaee;
    }

    /* אזור צ'אט */
    .inbox-chat {
        background: #f7f8fa;
    }

    /* בועות */
    .inbox-message-me {
        background: #dfeadf;
        border: 1px solid #d3e0d3;
        color: #2f3e2f;
    }

    .inbox-message-other {
        background: #ffffff;
        border: 1px solid #e2e5e9;
        color: #2c2f33;
    }

    /* שדה כתיבה */
    .inbox-send-box input {
        background: #f9fafb;
        border: 1px solid #d5d9de;
    }

    /* כפתור שליחה סולידי */
    .inbox-send-box button {
        background: #9aa3ad;
    }

    .inbox-send-box button:hover {
        background: #858e98;
    }


    /* ===== FIX COLORS (override חזק) ===== */

    /* רקע כללי */
    .inbox-page {
        background: #f5f6f8 !important;
    }

    /* רשימת שיחות */
    .inbox-conversations {
        background: #ffffff !important;
    }

    /* פריט שיחה */
    .inbox-conversation-item {
        background: #ffffff !important;
    }

    .inbox-conversation-item:hover {
        background: #f2f3f5 !important;
    }

    .inbox-conversation-item.active {
        background: #e4e7eb !important;
    }

    /* אזור צ'אט */
    .inbox-chat {
        background: #f7f8fa !important;
    }

    /* הודעות */
    .inbox-message-me {
        background: #e3f0e3 !important;
        border: 1px solid #d2e2d2 !important;
        color: #2f3e2f !important;
    }

    .inbox-message-other {
        background: #ffffff !important;
        border: 1px solid #e2e5e9 !important;
        color: #2c2f33 !important;
    }

    /* שדה כתיבה */
    .inbox-send-box input {
        background: #fafafa !important;
        border: 1px solid #d5d9de !important;
    }

    /* ===== כפתור שלח אדום ===== */
    .inbox-send-box button {
        background: #e5485d !important;
        color: #fff !important;
        border: none !important;
        border-radius: 8px !important;
    }

    .inbox-send-box button:hover {
        background: #d63c50 !important;
    }


    /* ===== כותרות סולידיות ===== */

    .inbox-conversations-header {
        background: #f0f2f5 !important;
        color: #1f2937 !important;
        border-bottom: 1px solid #d9dde3 !important;
    }

    .inbox-chat-header {
        background: #f0f2f5 !important;
        color: #1f2937 !important;
        border-bottom: 1px solid #d9dde3 !important;
    }
</style>