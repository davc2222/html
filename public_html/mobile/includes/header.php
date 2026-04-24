<?php
        // ===== FILE: includes/header.php =====
       
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$page = $_GET['page'] ?? 'home';

$sessionUserId   = (int)($_SESSION['user_id'] ?? 0);
$sessionUserName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? '')));

$headerAvatar = '/images/default_male.svg';

/* =========================
   1) fallback לפי מין
   ========================= */
if ($sessionUserId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT Gender_Str
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $sessionUserId]);

        $gender = trim((string)$stmt->fetchColumn());

        if ($gender === 'אישה') {
            $headerAvatar = '/images/default_female.svg';
        }
    } catch (Throwable $e) {
        $headerAvatar = '/images/default_male.svg';
    }
}

/* =========================
   2) תמונה אמיתית
   ========================= */
if ($sessionUserId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $sessionUserId]);
        $pic = $stmt->fetchColumn();

        if (!$pic) {
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id
                  AND Pic_Status = 1
                ORDER BY Main_Pic DESC, Pic_Num ASC
                LIMIT 1
            ");
            $stmt->execute([':id' => $sessionUserId]);
            $pic = $stmt->fetchColumn();
        }

        if ($pic) {
            $headerAvatar = '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
        // נשאר fallback
    }
}

$isDefaultHeaderAvatar =
    str_contains($headerAvatar, 'default_male.svg') ||
    str_contains($headerAvatar, 'default_female.svg');

$menu = [
    'home'            => ['label' => 'בית', 'icon' => '🏠'],
    'search'          => ['label' => 'חיפוש', 'icon' => '🔎'],
    'advanced_search' => ['label' => ' התאמות', 'icon' => '✨'],
    'messages'        => ['label' => 'הודעות', 'icon' => '💌'],
    'inbox'           => ['label' => 'תיבת דואר', 'icon' => '💬'],
    'views'           => ['label' => 'צפיות', 'icon' => '👀']
];
?>

<header class="site-header">

    <a href="?page=home" class="site-logo-text">
        <span class="logo-heart left">❤ </span>
        <span class="logo-text">LoveMatch</span>
        <span class="logo-heart right">&nbsp❤</span>
    </a>

    <nav class="links">
        <?php foreach ($menu as $p => $item): ?>
            <a href="?page=<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>"
                class="menu-link <?= ($page === $p) ? 'active' : '' ?>">

                <span class="menu-link-icon"><?= $item['icon'] ?></span>
                <span class="menu-link-text"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>

                <?php if ($sessionUserId > 0 && $p === 'messages'): ?>
                    <span id="headerMessagesBadge" class="menu-badge" style="display:none;">0</span>
                <?php endif; ?>

                <?php if ($sessionUserId > 0 && $p === 'inbox'): ?>
                    <span id="headerInboxBadge" class="menu-badge" style="display:none;">0</span>
                <?php endif; ?>

                <?php if ($sessionUserId > 0 && $p === 'views'): ?>
                    <span id="headerViewsBadge" class="menu-badge" style="display:none;">0</span>
                <?php endif; ?>

            </a>
        <?php endforeach; ?>
    </nav>

    <!-- ☰ -->
    <button class="hamburger-btn" onclick="toggleSidebar()">☰</button>

    <!-- SIDEBAR -->
    <div id="mobileSidebar" class="mobile-sidebar">
        <a href="/mobile/?page=blocked">חסומים</a>
        <a href="/mobile/?page=contact">קונטקט</a>
        <a href="/mobile/?page=terms">תנאי שימוש</a>
        <a href="/mobile/?page=privacy">פרטיות</a>
        <a href="/mobile/?page=settings">הגדרות</a>
    </div>

    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- AUTH -->
    <div class="auth">

        <?php if ($sessionUserId > 0): ?>

            <span class="welcome-user">
                שלום <?= htmlspecialchars($sessionUserName ?: 'משתמש', ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="/?page=profile&id=<?= $sessionUserId ?>&edit=1" class="header-avatar-link">
                <img
                    src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>"
                    class="header-avatar<?= $isDefaultHeaderAvatar ? ' header-avatar-default' : '' ?>"
                    alt="תמונת משתמש">
            </a>

            <a href="/mobile/logout.php" class="auth-btn logout-btn">התנתקות</a>

        <?php else: ?>

            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>

        <?php endif; ?>

    </div>

</header>
<script>
    /* ===== TITLE BLINK FALLBACK =====
       אם index.php כבר הגדיר את startTitleBlink/stopTitleBlink - זה לא ידרוס.
    */
    (function() {
        if (typeof window.startTitleBlink === 'function' && typeof window.stopTitleBlink === 'function') {
            return;
        }

        const normalTitle = document.title.trim() || 'LoveMatch';
        const alertTitle = '💬 הודעה חדשה!';
        let blinkInterval = null;
        let blinking = false;

        window.startTitleBlink = function() {
            if (blinking) return;

            blinking = true;
            let showAlert = true;

            blinkInterval = setInterval(function() {
                document.title = showAlert ? alertTitle : normalTitle;
                showAlert = !showAlert;
            }, 1000);
        };

        window.stopTitleBlink = function() {
            blinking = false;

            if (blinkInterval) {
                clearInterval(blinkInterval);
                blinkInterval = null;
            }

            document.title = normalTitle;
        };

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                window.stopTitleBlink();
            }
        });

        window.addEventListener('focus', function() {
            window.stopTitleBlink();
        });
    })();
</script>

<script>
    /* ===== HEADER BADGES + NEW MESSAGE DETECTION ===== */

    function updateHeaderBadges() {
        fetch('/get_header_counts.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                const currentMessages = Number(data.messages || 0);
                const currentViews = Number(data.views || 0);

                const msgBadge = document.getElementById('headerMessagesBadge');
                const viewsBadge = document.getElementById('headerViewsBadge');
                const inboxBadge = document.getElementById('headerInboxBadge');

                if (inboxBadge) {
                    if (currentMessages > 0) {
                        inboxBadge.textContent = currentMessages;
                        inboxBadge.style.display = 'inline-flex';
                    } else {
                        inboxBadge.style.display = 'none';
                    }
                }

                if (msgBadge) {
                    if (currentMessages > 0) {
                        msgBadge.textContent = currentMessages;
                        msgBadge.style.display = 'inline-flex';
                    } else {
                        msgBadge.style.display = 'none';
                    }
                }

                if (viewsBadge) {
                    if (currentViews > 0) {
                        viewsBadge.textContent = currentViews;
                        viewsBadge.style.display = 'inline-flex';
                    } else {
                        viewsBadge.style.display = 'none';
                    }
                }

                const isMessagePage =
                    window.location.search.includes('page=messages') ||
                    window.location.search.includes('page=inbox');

                if (!isMessagePage && currentMessages > 0) {
                    if (typeof window.startTitleBlink === 'function') {
                        window.startTitleBlink();
                    }
                } else {
                    if (typeof window.stopTitleBlink === 'function') {
                        window.stopTitleBlink();
                    }
                }
            })
            .catch(function() {});
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateHeaderBadges();
        setInterval(updateHeaderBadges, 3000);
    });
</script>

<script>
    (function() {
        function updatePresence() {
            fetch('/update_presence.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }).catch(function() {});
        }

        updatePresence();
        setInterval(updatePresence, 60000);
    })();
</script>

<style>
    .top-menu-wrapper {
        position: relative;
        margin-right: 10px;
    }

    .top-menu-btn {
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        font-size: 22px;
        cursor: pointer;
    }

    .top-menu-dropdown {
        display: none;
        position: absolute;
        top: 40px;
        right: 0;
        width: 160px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        z-index: 9999;
    }

    .top-menu-dropdown a {
        display: block;
        padding: 10px 12px;
        font-size: 14px;
        color: #222;
        text-decoration: none;
        border-bottom: 1px solid #eee;
    }

    .top-menu-dropdown a:last-child {
        border-bottom: none;
    }

    .top-menu-dropdown.show {
        display: block;
    }
</style>

<script>
    function toggleTopMenu() {
        document.getElementById('topMenuDropdown')?.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        const menu = document.querySelector('.top-menu-wrapper');
        if (menu && !menu.contains(e.target)) {
            document.getElementById('topMenuDropdown')?.classList.remove('show');
        }
    });
</script>

<script>
    function toggleSidebar() {
        document.getElementById('mobileSidebar')?.classList.toggle('open');
        document.getElementById('sidebarOverlay')?.classList.toggle('show');
    }
</script>
<style>
    /* ===== SIDEBAR ===== */
    @media (max-width: 768px) {

        .hamburger-btn {
            font-size: 22px;
            background: none;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }

        .mobile-sidebar {
            position: fixed;
            top: 0;
            right: -260px;
            width: 260px;
            height: 100%;
            background: #fff;
            z-index: 99999;
            transition: right 0.3s;
            padding-top: 60px;
        }

        .mobile-sidebar.open {
            right: 0;
        }

        .mobile-sidebar a {
            display: block;
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-decoration: none;
            color: #222;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 99998;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }
</style>