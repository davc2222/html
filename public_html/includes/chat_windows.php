<?php
// ===== FILE: includes/chat_windows.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$chatViewerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$chatViewerName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'אני')));
$chatViewerImage = '/images/no_photo.jpg';

if (!empty($_SESSION['user_main_pic'])) {
    $chatViewerImage = (string)$_SESSION['user_main_pic'];
} elseif (!empty($_SESSION['user_image'])) {
    $chatViewerImage = '/images/' . $_SESSION['user_image'];
}
?>

<?php if ($chatViewerId > 0): ?>
    <div id="chatWindowsLayer" class="chat-windows-layer"></div>

    <script>
        window.chatViewer = {
            id: <?= (int)$chatViewerId ?>,
            name: <?= json_encode($chatViewerName !== '' ? $chatViewerName : 'אני', JSON_UNESCAPED_UNICODE) ?>,
            image: <?= json_encode($chatViewerImage, JSON_UNESCAPED_UNICODE) ?>
        };

        window.chatWindows = window.chatWindows || {};
        window.unreadByUser = window.unreadByUser || {};
        window.chatBadgeRefreshTimer = window.chatBadgeRefreshTimer || null;
        window.chatWindowZCounter = window.chatWindowZCounter || 10000;

        function getChatStorageKey() {
            return 'lovematch_chat_windows_v4';
        }

        function getChatWindowId(userId) {
            return 'chat-window-' + String(userId);
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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

        function saveChatWindowsState() {
            const data = [];

            Object.keys(window.chatWindows).forEach((userId) => {
                const chat = window.chatWindows[userId];
                const root = document.getElementById(getChatWindowId(userId));

                if (!chat || !root) return;

                data.push({
                    userId: chat.userId,
                    userName: chat.userName,
                    userImage: chat.userImage,
                    lastMessageId: chat.lastMessageId || 0,
                    minimized: !!chat.minimized,
                    dragged: !!chat.dragged,
                    left: root.style.left || '',
                    top: root.style.top || '',
                    right: root.style.right || '',
                    bottom: root.style.bottom || '',
                    zIndex: root.style.zIndex || ''
                });
            });

            localStorage.setItem(getChatStorageKey(), JSON.stringify(data));
        }

        function getOpenWindowIds() {
            return Object.keys(window.chatWindows)
                .filter(userId => !!document.getElementById(getChatWindowId(userId)));
        }

        function refreshDefaultWindowPositions() {
            const openIds = getOpenWindowIds().sort((a, b) => Number(a) - Number(b));

            openIds.forEach((userId, index) => {
                const chat = window.chatWindows[userId];
                const elements = getChatWindowElements(userId);
                if (!chat || !elements) return;

                if (!chat.dragged) {
                    elements.root.style.left = 'auto';
                    elements.root.style.top = 'auto';
                    elements.root.style.right = (18 + (index * 320)) + 'px';
                    elements.root.style.bottom = '18px';
                }
            });

            saveChatWindowsState();
        }

        function bringChatWindowToFront(userId) {
            const elements = getChatWindowElements(userId);
            if (!elements) return;

            window.chatWindowZCounter += 1;
            elements.root.style.zIndex = String(window.chatWindowZCounter);
            saveChatWindowsState();
        }

        function buildReadStatusHtml(msg) {
            if (!msg.is_me) {
                return '';
            }

            if (msg.is_read) {
                return '<span class="message-read-state message-read-double" title="נקראה">✓✓</span>';
            }

            return '<span class="message-read-state message-read-single" title="נשלחה / עדיין לא נקראה">✓</span>';
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
            bubble.title = msg.full_date || msg.date_sent || '';

            const meta = document.createElement('div');
            meta.className = 'message-meta';
            meta.innerHTML =
                '<span class="message-time">' + (msg.date_sent || '') + '</span>' +
                buildReadStatusHtml(msg);

            bubbleWrap.appendChild(sender);
            bubbleWrap.appendChild(bubble);
            bubbleWrap.appendChild(meta);

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

        function createChatWindow(userId, userName, userImage) {
            const layer = document.getElementById('chatWindowsLayer');
            if (!layer) return null;

            const existing = document.getElementById(getChatWindowId(userId));
            if (existing) return existing;

            const safeUserName = escapeHtml(userName);
            const safeUserImage = escapeHtml(userImage || '/images/no_photo.jpg');

            const root = document.createElement('div');
            root.id = getChatWindowId(userId);
            root.className = 'message-modal-window';
            root.style.display = 'block';

            root.innerHTML = `
        <div class="message-window-card">
            <div class="message-window-head">
               <div class="message-window-head-user">
    <a href="/?page=profile&id=${Number(userId)}" class="message-head-user-link" title="מעבר לפרופיל">
        <img class="message-modal-head-image" src="${safeUserImage}" alt="">
        <div class="message-modal-head-text">
            <h3 class="message-modal-title">${safeUserName}</h3>
            <div class="message-modal-head-subtitle">היסטוריית הודעות</div>
        </div>
    </a>
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

            window.chatWindows[userId] = {
                userId: Number(userId),
                userName: userName,
                userImage: userImage || '/images/no_photo.jpg',
                viewerName: window.chatViewer.name || 'אני',
                viewerImage: window.chatViewer.image || '/images/no_photo.jpg',
                lastMessageId: 0,
                minimized: false,
                dragged: false,
                timer: null,
                isDragging: false,
                dragOffsetX: 0,
                dragOffsetY: 0
            };

            bindChatWindowEvents(userId);
            refreshDefaultWindowPositions();
            bringChatWindowToFront(userId);

            return root;
        }

        function bindChatWindowEvents(userId) {
            const elements = getChatWindowElements(userId);
            if (!elements) return;

            const chat = window.chatWindows[userId];
            if (!chat) return;

            const sendBtn = elements.root.querySelector('.message-send-btn');
            const closeBtn = elements.root.querySelector('.message-modal-close');
            const cancelBtn = elements.root.querySelector('.message-cancel-btn');
            const minimizeBtn = elements.root.querySelector('.message-window-minimize');

            elements.root.addEventListener('mousedown', function() {
                bringChatWindowToFront(userId);
            });

            minimizeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMessageWindowMinimize(userId, true);
            });

            closeBtn.addEventListener('click', function() {
                closeMessageModal(userId);
            });

            cancelBtn.addEventListener('click', function() {
                closeMessageModal(userId);
            });

            sendBtn.addEventListener('click', function() {
                sendProfileMessage(userId);
            });

            elements.text.addEventListener('focus', function() {
                markConversationAsRead(userId);
            });

            elements.text.addEventListener('click', function() {
                markConversationAsRead(userId);
            });

            elements.history.addEventListener('click', function() {
                markConversationAsRead(userId);
            });

            elements.text.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendProfileMessage(userId);
                }
            });

            elements.head.addEventListener('mousedown', function(e) {
                if (
                    e.target.closest('.message-window-minimize') ||
                    e.target.closest('.message-modal-close')
                ) {
                    return;
                }

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
                bringChatWindowToFront(userId);
                saveChatWindowsState();
            });
        }

        document.addEventListener('mousemove', function(e) {
            Object.keys(window.chatWindows).forEach(userId => {
                const chat = window.chatWindows[userId];
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

        document.addEventListener('mouseup', function() {
            let hadDragging = false;

            Object.keys(window.chatWindows).forEach(userId => {
                const chat = window.chatWindows[userId];
                if (chat && chat.isDragging) {
                    chat.isDragging = false;
                    hadDragging = true;
                }
            });

            if (hadDragging) {
                document.body.classList.remove('message-window-dragging');
                saveChatWindowsState();
            }
        });

        async function refreshMessagesBadge() {
            const badge = document.getElementById('messagesBadge');
            if (!badge) return;

            try {
                const response = await fetch('get_unread_count.php', {
                    cache: 'no-store'
                });
                const result = await response.json();

                if (!result.ok) {
                    return;
                }

                window.unreadByUser = result.by_user || {};
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

        async function refreshViewsBadge() {
            const badge = document.getElementById('viewsBadge');
            if (!badge) return;

            try {
                const response = await fetch('get_views_count.php', {
                    cache: 'no-store'
                });
                const result = await response.json();

                if (!result.ok) {
                    return;
                }

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
                    window.unreadByUser[userId] = 0;
                    refreshMessagesBadge();
                    loadMessageHistory(userId, false, false);
                }
            } catch (err) {
                console.error(err);
            }
        }

        function openMessageModal(userId, userName, userImage, viewerName, viewerImage, persistState = true) {
            userId = Number(userId);

            createChatWindow(userId, userName, userImage);

            const chat = window.chatWindows[userId];
            const elements = getChatWindowElements(userId);

            if (!chat || !elements) return;

            elements.root.style.display = 'block';
            elements.root.classList.remove('is-minimized');
            elements.body.style.display = 'block';
            elements.title.textContent = userName;
            elements.image.src = userImage || '/images/no_photo.jpg';

            chat.userName = userName;
            chat.userImage = userImage || '/images/no_photo.jpg';
            chat.viewerName = viewerName || window.chatViewer.name || 'אני';
            chat.viewerImage = viewerImage || window.chatViewer.image || '/images/no_photo.jpg';
            chat.minimized = false;

            loadMessageHistory(userId, true, true);

            elements.text.focus();

            if (chat.timer) {
                clearInterval(chat.timer);
            }

            chat.timer = setInterval(() => {
                if (!chat.minimized) {
                    loadMessageHistory(userId, false, false);
                }
                refreshMessagesBadge();
            }, 2000);

            refreshDefaultWindowPositions();

            if (persistState) {
                saveChatWindowsState();
            }
        }

        function closeMessageModal(userId) {
            const chat = window.chatWindows[userId];
            const elements = getChatWindowElements(userId);

            if (chat && chat.timer) {
                clearInterval(chat.timer);
                chat.timer = null;
            }

            if (elements) {
                elements.root.remove();
            }

            delete window.chatWindows[userId];
            refreshDefaultWindowPositions();
            saveChatWindowsState();
        }

        function toggleMessageWindowMinimize(userId, persistState = true) {
            const chat = window.chatWindows[userId];
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

            if (persistState) {
                saveChatWindowsState();
            }
        }

        async function loadMessageHistory(userId, forceScrollToBottom = false, firstLoad = false) {
            const chat = window.chatWindows[userId];
            const elements = getChatWindowElements(userId);

            if (!chat || !elements) return;

            const history = elements.history;
            const nearBottomBefore = isHistoryNearBottom(history);

            try {
                const response = await fetch(
                    'get_messages.php?user_id=' + encodeURIComponent(userId) + '&last_id=0', {
                        cache: 'no-store'
                    }
                );

                const result = await response.json();

                if (!result.ok) {
                    if (firstLoad) {
                        history.innerHTML = '<div class="message-history-empty">לא ניתן לטעון הודעות</div>';
                    }
                    return;
                }

                const messages = Array.isArray(result.messages) ? result.messages : [];
                history.innerHTML = '';

                if (messages.length === 0) {
                    history.innerHTML = '<div class="message-history-empty">עדיין אין הודעות ביניכם</div>';
                } else {
                    messages.forEach(msg => {
                        history.appendChild(buildMessageRow(msg, chat));
                    });
                }

                if (typeof result.last_id !== 'undefined') {
                    chat.lastMessageId = parseInt(result.last_id, 10) || 0;
                }

                if (firstLoad || forceScrollToBottom || nearBottomBefore) {
                    scrollHistoryToBottom(history);
                }

                saveChatWindowsState();
            } catch (err) {
                if (firstLoad) {
                    history.innerHTML = '<div class="message-history-empty">שגיאה בטעינת ההודעות</div>';
                }
                console.error(err);
            }
        }

        async function sendProfileMessage(userId) {
            const chat = window.chatWindows[userId];
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
                refreshMessagesBadge();
            } catch (err) {
                elements.status.textContent = 'אירעה שגיאה בשליחה';
                console.error(err);
            }
        }

        function restoreChatWindowsState() {
            const raw = localStorage.getItem(getChatStorageKey());
            if (!raw) return;

            let data = [];
            try {
                data = JSON.parse(raw);
            } catch (e) {
                return;
            }

            if (!Array.isArray(data)) return;

            data.forEach(chat => {
                openMessageModal(
                    chat.userId,
                    chat.userName || 'משתמש',
                    chat.userImage || '/images/no_photo.jpg',
                    window.chatViewer.name,
                    window.chatViewer.image,
                    false
                );

                const state = window.chatWindows[chat.userId];
                const elements = getChatWindowElements(chat.userId);

                if (!state || !elements) return;

                state.lastMessageId = parseInt(chat.lastMessageId || 0, 10) || 0;

                if (chat.dragged) {
                    state.dragged = true;
                    elements.root.style.left = chat.left || '';
                    elements.root.style.top = chat.top || '';
                    elements.root.style.right = 'auto';
                    elements.root.style.bottom = 'auto';
                } else {
                    state.dragged = false;
                    elements.root.style.right = chat.right || elements.root.style.right;
                    elements.root.style.bottom = chat.bottom || elements.root.style.bottom;
                }

                if (chat.zIndex) {
                    elements.root.style.zIndex = chat.zIndex;
                }

                if (chat.minimized && !state.minimized) {
                    toggleMessageWindowMinimize(chat.userId, false);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            refreshMessagesBadge();
            refreshViewsBadge();
            restoreChatWindowsState();

            if (!window.chatBadgeRefreshTimer) {
                window.chatBadgeRefreshTimer = setInterval(() => {
                    refreshMessagesBadge();
                    refreshViewsBadge();
                }, 5000);
            }
        });

        window.addEventListener('beforeunload', function() {
            saveChatWindowsState();
        });
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.open-chat-btn');
            if (!btn) return;

            e.preventDefault();

            const userId = btn.dataset.userId;
            if (!userId) return;

            // 🔥 כאן אנחנו מפעילים את הצ'אט
            openMessageModal(
                userId,
                btn.dataset.userName || 'משתמש',
                btn.dataset.userImage || '/images/no_photo.jpg',
                window.chatViewer.name,
                window.chatViewer.image
            );
        });
    </script>
<?php endif; ?>