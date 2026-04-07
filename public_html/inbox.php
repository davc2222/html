<?php

/**
 * inbox.php
 * דף תיבת הדואר הראשי
 */

session_start();

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

        <div class="inbox-send-box">
            <form id="inboxSendForm">
                <input type="text" id="inboxMessageInput" placeholder="כתוב הודעה..." autocomplete="off">
                <button type="submit">שלח</button>
            </form>
        </div>

    </div>

</div>

<style>
    .inbox-page {
        display: flex;
        width: 100%;
        max-width: 1200px;
        height: 75vh;
        margin: 20px auto;
        border: 1px solid #ddd;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
    }

    /* צד ימין - רשימת שיחות */
    .inbox-conversations {
        width: 260px;
        min-width: 260px;
        border-left: 1px solid #eee;
        background: #fafafa;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .inbox-conversations-header {
        padding: 14px;
        font-weight: bold;
        border-bottom: 1px solid #eee;
        background: #fff;
        flex-shrink: 0;
    }

    #inboxConversationsList {
        flex: 1;
        overflow-y: auto;
    }

    /* פריט שיחה */
    .inbox-conversation-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background 0.2s ease;
        background: #fff;
    }

    .inbox-conversation-item:hover {
        background: #f3f4f6;
    }

    .inbox-conversation-item.active {
        background: #e5e7eb;
    }

    .inbox-unread {
        background: #eef6ff;
    }

    .inbox-conversation-item.active.inbox-unread {
        background: #dfe3e8;
    }

    .inbox-conversation-date {
        font-size: 13px;
        color: #666;
        text-align: right;
        margin-bottom: 8px;
    }

    .inbox-conversation-main {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inbox-conversation-avatar {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
    }

    .inbox-conversation-content {
        flex: 1;
        min-width: 0;
    }

    .inbox-conversation-name {
        font-weight: 700;
        font-size: 15px;
        color: #333;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .inbox-conversation-preview {
        font-size: 13px;
        color: #666;
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
    }

    .inbox-chat-header {
        padding: 14px;
        border-bottom: 1px solid #eee;
        font-weight: bold;
        background: #fff;
        flex-shrink: 0;
    }

    /* אזור הודעות */
    .inbox-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f7f7f7;
        direction: rtl;
        min-height: 0;
    }

    /* שורות */
    .inbox-message-row {
        display: flex;
        margin-bottom: 12px;
    }

    .inbox-message-row-me {
        justify-content: flex-start;
    }

    .inbox-message-row-other {
        justify-content: flex-end;
    }

    /* בועות */
    .inbox-message {
        max-width: 65%;
        padding: 10px 12px;
        border-radius: 14px;
        text-align: right;
    }

    .inbox-message-me {
        background: #d9f5d9;
    }

    .inbox-message-other {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }

    .inbox-message-sender {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #444;
    }

    .inbox-message-text {
        font-size: 15px;
    }

    .inbox-message-meta {
        font-size: 11px;
        color: #777;
        margin-top: 6px;
        display: flex;
        gap: 8px;
    }

    /* שליחה */
    .inbox-send-box {
        padding: 10px;
        border-top: 1px solid #eee;
        background: #fff;
        flex-shrink: 0;
    }

    .inbox-send-box form {
        display: flex;
        gap: 8px;
    }

    .inbox-send-box input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
    }

    .inbox-send-box button {
        padding: 10px 16px;
        border: none;
        background: #ff4d6d;
        color: white;
        border-radius: 6px;
        cursor: pointer;
    }

    .inbox-empty {
        padding: 20px;
        text-align: center;
        color: #999;
    }
</style>

<script>
    let inboxCurrentUserId = <?= $selectedUserId ?>;
    let inboxCurrentName = '';

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

    /* פתיחת שיחה */
    function inboxOpenConversation(userId, name = '') {
        inboxCurrentUserId = parseInt(userId || 0, 10);
        inboxCurrentName = name || '';

        document.getElementById('inboxChatHeader').innerHTML = inboxCurrentName ?
            ('שיחה עם ' + inboxEscapeHtml(inboxCurrentName)) :
            'בחר שיחה';

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

        inboxLoadConversations();

        if (inboxCurrentUserId) {
            inboxLoadMessages();
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