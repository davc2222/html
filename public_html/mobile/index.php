<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$page = $_GET['page'] ?? 'home';

$allowedPages = [
    'home',
    'profile',
    'search',
    'advanced_search',
    'messages',
    'inbox', // 🔥
    'login',
    'register',
    'verify_notice',
    'views',
    'blocked',
    'contact',
    'terms',
    'privacy',
    'settings',
    'blocked_users',
    'contact',
    'terms',
    'privacy',
    'settings'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$protectedPages = ['profile', 'search', 'advanced_search', 'messages', 'inbox'];

if (in_array($page, $protectedPages, true) && empty($_SESSION['user_id'])) {
    header('Location: /mobile/?page=login');
    exit;
}

$currentUserId   = (int)($_SESSION['user_id'] ?? 0);
$currentUserName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'משתמש')));

function m_is_active(string $name, string $page): string {
    return $name === $page ? 'active' : '';
}

function m_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$mobileHeaderAvatar = '/images/default_male.svg';

if ($currentUserId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT Pic_Name FROM user_pics WHERE Id=:id AND Pic_Status=1 ORDER BY Main_Pic DESC LIMIT 1");
        $stmt->execute([':id' => $currentUserId]);
        $pic = $stmt->fetchColumn();
        if ($pic) {
            $mobileHeaderAvatar = '/uploads/' . ltrim($pic, '/');
        }
    } catch (Throwable $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="he">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch Mobile</title>
    <link rel="stylesheet" href="/mobile/css/style.css?v=<?= time() ?>">


</head>

<body>
    <div class="mobile-site">

        <header class="mobile-header">
            <div class="mobile-header-top">
                <a href="/mobile/?page=home" class="mobile-logo">
                    <span>❤</span><span>LoveMatch</span>
                </a>

                <button type="button" class="hamburger-btn" onclick="toggleSidebar()" aria-label="פתח תפריט">☰</button>
            </div>

            <div class="mobile-auth">
                <?php if ($currentUserId > 0): ?>
                    <div class="mobile-user-box">
                        <a href="/mobile/?page=profile&id=<?= $currentUserId ?>&edit=1" class="mobile-user-avatar-link">
                            <div class="mobile-user-avatar">
                                <img src="<?= m_e($mobileHeaderAvatar) ?>">
                            </div>
                        </a>

                        <div class="mobile-user-info">
                            <span class="mobile-user-hello">שלום</span>
                            <span class="mobile-user-name"><?= m_e($currentUserName) ?></span>
                        </div>
                    </div>

                    <div class="mobile-auth-actions">
                        <a href="/mobile/logout.php" class="mobile-auth-btn mobile-auth-btn-logout">התנתקות</a>
                    </div>
                <?php else: ?>
                    <div></div>

                    <div class="mobile-auth-actions">
                        <a href="/mobile/?page=login" class="mobile-auth-btn">התחברות</a>
                        <a href="/mobile/?page=register" class="mobile-auth-btn mobile-auth-btn-profile">הרשמה</a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div id="mobileSidebar" class="mobile-sidebar">
            <div class="mobile-sidebar-title">תפריט</div>
            <a href="/mobile/?page=blocked_users">חסומים</a>
            <a href="/mobile/?page=contact">קונטקט</a>
            <a href="/mobile/?page=terms">תנאי שימוש</a>
            <a href="/mobile/?page=privacy">פרטיות</a>
            <a href="/mobile/?page=settings">הגדרות</a>
        </div>

        <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main class="mobile-main">
            <?php
            switch ($page) {
                case 'home':
                    include __DIR__ . '/home.php';
                    break;
                case 'profile':
                    include __DIR__ . '/profile.php';
                    break;
                case 'search':
                    include __DIR__ . '/search.php';
                    break;
                case 'advanced_search':
                    include __DIR__ . '/advanced_search.php';
                    break;
                case 'messages':
                    include __DIR__ . '/messages.php';
                    break;
                case 'login':
                    include __DIR__ . '/login.php';
                    break;
                case 'register':
                    include __DIR__ . '/register.php';
                    break;
                case 'verify_notice':
                    include __DIR__ . '/verify_notice.php';
                    break;
                case 'views':
                    include __DIR__ . '/views.php';
                    break;
                case 'inbox':
                    include __DIR__ . '/inbox.php';
                    break;
                case 'blocked':
                    include file_exists(__DIR__ . '/blocked.php') ? __DIR__ . '/blocked.php' : __DIR__ . '/home.php';
                    break;
                case 'contact':
                    include file_exists(__DIR__ . '/contact.php') ? __DIR__ . '/contact.php' : __DIR__ . '/home.php';
                    break;
                case 'terms':
                    include file_exists(__DIR__ . '/terms.php') ? __DIR__ . '/terms.php' : __DIR__ . '/home.php';
                    break;
                case 'privacy':
                    include file_exists(__DIR__ . '/privacy.php') ? __DIR__ . '/privacy.php' : __DIR__ . '/home.php';
                    break;
                case 'settings':
                    include file_exists(__DIR__ . '/settings.php') ? __DIR__ . '/settings.php' : __DIR__ . '/home.php';
                    break;
                case 'blocked_users':
                    include __DIR__ . '/blocked_users.php';
                    break;

                default:
                    include __DIR__ . '/home.php';
            }

            ?>
        </main>

        <footer class="mobile-footer">LoveMatch</footer>

        <nav class="mobile-bottom-nav">
            <a href="/mobile/?page=home" class="<?= m_is_active('home', $page) ?>">
                <span>🏠</span><small>בית</small>
            </a>

            <?php if ($currentUserId > 0): ?>
                <a href="/mobile/?page=search" class="<?= m_is_active('search', $page) ?>">
                    <span>🔎</span><small>חיפוש</small>
                </a>

                <a href="/mobile/?page=advanced_search" class="<?= m_is_active('advanced_search', $page) ?>">
                    <span>✨</span><small>התאמות</small>
                </a>

                <a href="/mobile/?page=inbox" class="<?= m_is_active('inbox', $page) ?>">
                    <span class="mobile-nav-icon-wrap">
                        <span>💬</span>
                        <em id="messages-badge" class="mobile-nav-badge"></em>
                    </span>
                    <small>הודעות</small>
                </a>

                <a href="/mobile/?page=views" class="<?= m_is_active('views', $page) ?>">
                    <span class="mobile-nav-icon-wrap">
                        <span class="eye-icon">👁</span>
                        <em id="views-badge" class="mobile-nav-badge"></em>
                    </span>
                    <small>צפיות</small>
                </a>
            <?php else: ?>
                <a href="/mobile/?page=login"><span>🔐</span><small>כניסה</small></a>
                <a href="/mobile/?page=register"><span>📝</span><small>הרשמה</small></a>
            <?php endif; ?>
        </nav>

    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar) {
                sidebar.classList.toggle('open');
            }

            if (overlay) {
                overlay.classList.toggle('show');
            }
        }

        function updateMobileBadges() {
            fetch('/get_header_counts.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(d => {

                    // 🔥 הודעות
                    const msgBadge = document.getElementById('messages-badge');
                    if (msgBadge) {
                        if (Number(d.messages || 0) > 0) {
                            msgBadge.textContent = Number(d.messages) > 99 ? '99+' : d.messages;
                            msgBadge.style.display = 'inline-block';
                        } else {
                            msgBadge.style.display = 'none';
                        }
                    }

                    // 🔥 צפיות
                    const viewsBadge = document.getElementById('views-badge');
                    if (viewsBadge) {
                        if (Number(d.views || 0) > 0) {
                            viewsBadge.textContent = Number(d.views) > 99 ? '99+' : d.views;
                            viewsBadge.style.display = 'inline-block';
                        } else {
                            viewsBadge.style.display = 'none';
                        }
                    }

                })
                .catch(err => console.log('badge error:', err));
        }

        // טעינה ראשונית + רענון
        document.addEventListener('DOMContentLoaded', function() {
            updateMobileBadges();
            setInterval(updateMobileBadges, 3000);
        });
    </script>

</body>

</html>

<style>
    .eye-icon {
        font-size: 30px;
    }

    .mobile-nav-icon-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: visible;
    }

    .mobile-nav-badge {
        position: absolute;
        top: -6px;
        right: -10px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: #e11d48;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        line-height: 16px;
        text-align: center;
        display: none;
        z-index: 10;
    }

    .mobile-site {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .mobile-main {
        flex: 1;
    }

    .mobile-header {
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 12px 14px 10px;
    }

    .mobile-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }


    .mobile-logo {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        font-size: 18px;
        font-weight: 800;
        color: #d81b60;
        white-space: nowrap;
        order: 1;
        /* שמאל */
    }

    /* חשוב מאוד למנוע שבירה */
    .mobile-header-top>* {
        flex-shrink: 0;
    }

    .mobile-logo span:first-child {
        font-size: 18px;
        line-height: 1;
    }

    .mobile-auth {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .mobile-user-box {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .mobile-user-avatar-link {
        display: inline-flex;
        text-decoration: none;
        flex: 0 0 auto;
    }

    .mobile-user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #f2f2f2;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .mobile-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .mobile-user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.15;
        min-width: 0;
    }

    .mobile-user-hello {
        font-size: 11px;
        color: #777;
    }

    .mobile-user-name {
        font-size: 14px;
        font-weight: 800;
        color: #d81b60;
        max-width: 130px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mobile-auth-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .mobile-auth-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        background: #f3f3f3;
        color: #333;
    }

    .mobile-auth-btn-profile {
        background: #d81b60;
        color: #fff;
    }

    .mobile-auth-btn-logout {
        background: #7a7a7a;
        color: #fff;
    }

    .mobile-footer {
        text-align: center;
        color: #888;
        font-size: 12px;
        padding: 14px 10px 84px;
    }

    .mobile-bottom-nav a {
        position: relative;
    }


    /* ===== MOBILE SIDEBAR ===== */
    .hamburger-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #333;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        flex: 0 0 auto;
    }

    .mobile-sidebar {
        position: fixed;
        top: 0;
        right: -270px;
        width: 270px;
        max-width: 82vw;
        height: 100vh;
        background: #fff;
        z-index: 100000;
        transition: right 0.25s ease;
        padding: 18px 0 24px;
        box-shadow: -6px 0 22px rgba(0, 0, 0, 0.18);
        direction: rtl;
    }

    .mobile-sidebar.open {
        right: 0;
    }

    .mobile-sidebar-title {
        padding: 0 18px 14px;
        font-size: 18px;
        font-weight: 800;
        color: #d81b60;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 6px;
    }

    .mobile-sidebar a {
        display: block;
        padding: 14px 18px;
        color: #222;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
        border-bottom: 1px solid #f3f3f3;
    }

    .mobile-sidebar a:active {
        background: #f7f7f7;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        z-index: 99999;
    }

    .sidebar-overlay.show {
        display: block;
    }

    @media (max-width: 520px) {

        .mobile-header-top,
        .mobile-auth {
            gap: 8px;
        }

        .mobile-user-name {
            max-width: 90px;
            font-size: 13px;
        }

        .mobile-auth-btn {
            min-height: 34px;
            padding: 0 12px;
            font-size: 12px;
        }

        .mobile-user-avatar {
            width: 38px;
            height: 38px;
        }
    }
</style>