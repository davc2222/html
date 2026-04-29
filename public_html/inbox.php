<?php

/**
 * inbox.php
 * דף תיבת הדואר הראשי - עיצוב אפליקציה כחול
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
        <div class="inbox-conversations-header">
            <span class="inbox-header-title">תיבת דואר</span>
            <span class="inbox-header-icon">💬</span>
        </div>

        <div class="inbox-search-wrap">
            <input type="text" id="inboxConversationSearch" placeholder="חיפוש שיחות..." autocomplete="off">
            <span class="inbox-search-icon">⌕</span>
        </div>

        <div id="inboxConversationsList">
            <div class="inbox-empty">טוען שיחות...</div>
        </div>
    </div>

    <div class="inbox-chat">

        <div class="inbox-chat-header" id="inboxChatHeader">
            <div class="inbox-chat-title-empty">שיחות</div>
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
                <button type="submit" aria-label="שלח" title="שלח">
                    <span class="inbox-send-icon">➤</span>
                </button>
            </form>

            <div class="inbox-enter-row">
                <label class="inbox-enter-label" for="inboxEnterToggle">
                    <input type="checkbox" id="inboxEnterToggle">
                    <span>שלח עם Enter</span>
                </label>
                <div class="inbox-enter-hint">כשלא מסומן, Enter לא ישלח</div>
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
    let inboxLastHtml = '';

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

    function inboxIsNearBottom(box) {
        if (!box) return true;
        return (box.scrollHeight - box.scrollTop - box.clientHeight) < 70;
    }

    function inboxScrollToBottom() {
        const box = document.getElementById('inboxMessages');
        if (!box) return;
        box.scrollTop = box.scrollHeight;
    }

    function inboxSetActiveConversation(userId) {
        document.querySelectorAll('.inbox-conversation-item').forEach(el => {
            const itemUserId = parseInt(el.getAttribute('data-user-id') || '0', 10);
            el.classList.toggle('active', itemUserId === parseInt(userId || 0, 10));
        });
    }

    function inboxFilterConversations() {
        const search = document.getElementById('inboxConversationSearch');
        const q = (search ? search.value : '').trim().toLowerCase();

        document.querySelectorAll('.inbox-conversation-item').forEach(item => {
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            const previewEl = item.querySelector('.inbox-conversation-preview');
            const preview = (previewEl ? previewEl.textContent : '').toLowerCase();
            item.style.display = (!q || name.includes(q) || preview.includes(q)) ? 'flex' : 'none';
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

    function inboxLoadConversations() {
        fetch('/inbox_get_conversations.php')
            .then(r => r.text())
            .then(html => {
                document.getElementById('inboxConversationsList').innerHTML = html;
                inboxBindConversationClicks();
                inboxSetActiveConversation(inboxCurrentUserId);
                inboxFilterConversations();
            })
            .catch(() => {});
    }

    function inboxLoadMessages(forceScroll = false) {
        if (!inboxCurrentUserId) return;

        const box = document.getElementById('inboxMessages');
        const wasNearBottom = inboxIsNearBottom(box);

        fetch('/inbox_get_messages.php?user_id=' + inboxCurrentUserId)
            .then(r => r.text())
            .then(html => {
                const box = document.getElementById('inboxMessages');
                if (!box) return;

                const changed = html !== inboxLastHtml;
                inboxLastHtml = html;
                box.innerHTML = html;

                if (forceScroll || wasNearBottom || changed && wasNearBottom) {
                    requestAnimationFrame(inboxScrollToBottom);
                }
            })
            .catch(() => {});
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

                el.style.display = (data && data.typing) ? 'block' : 'none';
            })
            .catch(() => {});
    }

    function inboxOpenConversation(userId, name = '') {
        inboxCurrentUserId = parseInt(userId || 0, 10);
        inboxCurrentName = name || '';
        inboxLastHtml = '';

        document.getElementById('inboxChatHeader').innerHTML = inboxCurrentName ?
            ('<div class="inbox-chat-title">שיחה עם ' + inboxEscapeHtml(inboxCurrentName) + '</div>') :
            '<div class="inbox-chat-title-empty">בחר שיחה</div>';

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
        inboxLoadMessages(true);
        inboxLoadConversations();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inboxSendForm = document.getElementById('inboxSendForm');
        const inboxMessageInput = document.getElementById('inboxMessageInput');
        const inboxEnterToggle = document.getElementById('inboxEnterToggle');
        const inboxConversationSearch = document.getElementById('inboxConversationSearch');

        if (inboxEnterToggle) {
            inboxEnterToggle.checked = lmGetEnterSendEnabled();

            inboxEnterToggle.addEventListener('change', function() {
                lmSetEnterSendEnabled(this.checked);
            });
        }

        if (inboxConversationSearch) {
            inboxConversationSearch.addEventListener('input', inboxFilterConversations);
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

                inboxLoadMessages(true);
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
            inboxLoadMessages(true);
            inboxTypingPollTimer = setInterval(inboxPollTyping, 2000);
        }

        setInterval(inboxLoadConversations, 8000);

        setInterval(() => {
            if (inboxCurrentUserId) {
                inboxMarkRead();
                inboxLoadMessages(false);
            }
        }, 4000);
    });
</script>

<style>
    .inbox-page {
        display: flex;
        flex-direction: row;
        width: calc(100% - 40px);
        max-width: 1200px;
        height: 78vh;
        margin: 20px auto;
        border: 1px solid #d8dadd;
        border-radius: 22px;
        overflow: hidden;
        background: #ffffff;
        box-sizing: border-box;
        align-items: stretch;
        direction: rtl;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
    }

    .inbox-conversations {
        width: 300px;
        min-width: 300px;
        border-left: 1px solid #d9dde3;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        order: 1;
    }

    .inbox-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #f7f8fa;
        order: 2;
    }

    .inbox-conversations-header,
    .inbox-chat-header {
        height: 72px;
        padding: 16px 18px;
        flex-shrink: 0;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-weight: 800;
        font-size: 20px;
        color: #0f172a;
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb;
    }

    .inbox-conversations-header {
        gap: 10px;
    }

    .inbox-header-icon {
        color: #2563eb;
        font-size: 22px;
        line-height: 1;
    }

    .inbox-chat-title,
    .inbox-chat-title-empty {
        font-size: 19px;
        font-weight: 800;
        color: #111827;
    }

    .inbox-search-wrap {
        position: relative;
        padding: 12px 12px 8px;
        background: #ffffff;
        border-bottom: 1px solid #eef2f7;
        flex-shrink: 0;
    }

    #inboxConversationSearch {
        width: 100%;
        height: 44px;
        padding: 0 42px 0 14px;
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        background: #f8fafc;
        outline: none;
        font-size: 14px;
        color: #111827;
        box-sizing: border-box;
    }

    #inboxConversationSearch:focus {
        background: #ffffff;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .inbox-search-icon {
        position: absolute;
        right: 25px;
        top: 23px;
        color: #64748b;
        font-size: 20px;
        pointer-events: none;
    }

    #inboxConversationsList {
        flex: 1;
        overflow-y: auto;
        background: #f8fafc;
        padding: 8px 10px 12px;
    }

    .inbox-conversation-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 10px;
        margin: 0 0 10px;
        cursor: pointer;
        border: 1px solid #eef2f7;
        border-radius: 14px;
        transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
        background: #ffffff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
    }

    .inbox-conversation-item:hover {
        background: #eef6ff;
        border-color: #bfdbfe;
        transform: translateY(-1px);
    }

    .inbox-conversation-item.active {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-color: #bfdbfe;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.14);
    }

    .inbox-unread {
        background: #ffffff;
    }

    .inbox-conversation-avatar-link {
        flex-shrink: 0;
        display: block;
        line-height: 0;
    }

    .inbox-conversation-avatar {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 50%;
        flex-shrink: 0;
        background: #fff;
        border: 2px solid #ffffff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.12);
    }

    .inbox-conversation-content {
        flex: 1;
        min-width: 0;
        text-align: right;
    }

    .inbox-conversation-name {
        font-weight: 800;
        font-size: 15px;
        color: #0f172a;
        margin-bottom: 4px;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .inbox-conversation-preview {
        font-size: 13px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.35;
    }

    .inbox-conversation-meta {
        width: 48px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 8px;
        text-align: left;
    }

    .inbox-conversation-time {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }

    .inbox-badge {
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        border-radius: 999px;
        background: #2563eb;
        color: white;
        font-size: 12px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.25);
    }

    .inbox-messages {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px 28px;
        direction: rtl;
        min-height: 0;
        background-color: #f3f7fc;
        background-image:
            radial-gradient(circle at 20px 20px, rgba(37, 99, 235, 0.055) 1px, transparent 1px),
            radial-gradient(circle at 62px 58px, rgba(15, 23, 42, 0.035) 1px, transparent 1px),
            linear-gradient(135deg, rgba(255,255,255,0.55), rgba(255,255,255,0));
        background-size: 82px 82px, 82px 82px, 100% 100%;
    }

    .inbox-day-separator {
        width: fit-content;
        margin: 6px auto 20px;
        padding: 5px 13px;
        border-radius: 999px;
        background: rgba(226, 232, 240, 0.92);
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 2px 7px rgba(15, 23, 42, 0.05);
    }

    .inbox-message-row {
        display: flex;
        margin-bottom: 16px;
        width: 100%;
    }

    .inbox-message-row-me {
        justify-content: flex-start;
    }

    .inbox-message-row-other {
        justify-content: flex-end;
    }

    .inbox-message {
        max-width: 66%;
        min-width: 70px;
        padding: 11px 14px 22px;
        border-radius: 17px;
        text-align: right;
        position: relative;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        word-break: break-word;
    }

    .inbox-message-me {
        background: #dbeafe;
        color: #1e3a5f;
        border: 1px solid #bfdbfe;
        border-bottom-left-radius: 6px;
    }

    .inbox-message-other {
        background: #ffffff;
        color: #1f2937;
        border: 1px solid #e5e7eb;
        border-bottom-right-radius: 6px;
    }

    .inbox-message-text {
        font-size: 15px;
        line-height: 1.55;
        word-break: break-word;
        padding-bottom: 2px;
    }

    .inbox-message-meta {
        position: absolute;
        bottom: 5px;
        left: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
        direction: ltr;
        line-height: 1;
    }

    .inbox-message-row-me .inbox-message-meta {
        left: 10px;
        right: auto;
    }

    .inbox-message-row-other .inbox-message-meta {
        left: 10px;
        right: auto;
    }

    .inbox-time {
        font-size: 11px;
        color: #7c8796;
        white-space: nowrap;
    }

    .inbox-read {
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -2px;
        user-select: none;
    }

    .inbox-read-sent {
        color: #94a3b8;
    }

    .inbox-read-seen {
        color: #2563eb;
    }

    .inbox-typing-indicator {
        padding: 7px 22px 9px;
        font-size: 13px;
        color: #64748b;
        background: #ffffff;
        border-top: 1px solid #eceff2;
        min-height: 24px;
    }

    .inbox-send-box {
        padding: 12px 14px 14px;
        border-top: 1px solid #dfe3e8;
        background: #ffffff;
        flex-shrink: 0;
    }

    .inbox-send-box form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-direction: row;
    }

    .inbox-send-box input {
        flex: 1;
        height: 46px;
        padding: 0 18px;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        background: #f8fafc;
        color: #111827;
        font-size: 14px;
        outline: none;
        min-width: 0;
    }

    .inbox-send-box input:focus {
        border-color: #93c5fd;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .inbox-send-box button {
        width: 46px;
        height: 46px;
        min-width: 46px;
        padding: 0;
        border: none;
        background: #2563eb;
        color: white;
        border-radius: 50%;
        cursor: pointer;
        font-weight: 800;
        font-size: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
        transition: background 0.18s ease, transform 0.18s ease;
    }

    .inbox-send-box button:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .inbox-send-icon {
        display: block;
        transform: rotate(180deg);
        line-height: 1;
        margin-right: -1px;
    }

    .inbox-enter-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 9px;
        font-size: 12px;
        color: #64748b;
    }

    .inbox-enter-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        user-select: none;
    }

    .inbox-enter-label input {
        margin: 0;
        accent-color: #2563eb;
    }

    .inbox-enter-hint {
        font-size: 11px;
        color: #8a94a3;
        white-space: nowrap;
    }

    .inbox-empty,
    .inbox-error {
        padding: 28px 20px;
        text-align: center;
        color: #8a94a3;
        font-size: 18px;
    }

    @media (max-width: 900px) {
        .inbox-page {
            flex-direction: column;
            height: auto;
            min-height: 78vh;
            width: calc(100% - 20px);
        }

        .inbox-conversations {
            width: 100%;
            min-width: 0;
            border-left: none;
            border-bottom: 1px solid #d9dde3;
        }

        .inbox-message {
            max-width: 78%;
        }

        .inbox-enter-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
    }
</style>
