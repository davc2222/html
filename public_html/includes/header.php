<?php
// ===== FILE: includes/header.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$page = $_GET['page'] ?? 'home';

$sessionUserId   = (int)($_SESSION['user_id'] ?? 0);
$sessionUserName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? '')));
$headerHasPremium = false;

if ($sessionUserId > 0) {
    $headerHasPremium = hasActiveSubscription($pdo, $sessionUserId);
}

$headerReturnUrl = $_SERVER['REQUEST_URI'] ?? '/';
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


<div class="mobile-header-row">
    <button type="button"
            class="mobile-top-menu-toggle"
            id="mobileTopMenuToggle"
            onclick="openUnifiedMobileNav()"
            aria-label="פתיחת תפריט"
            aria-expanded="false">☰</button>

    <a href="?page=home" class="mobile-site-logo">
        <img src="/images/logonew.jpeg" alt="LoveMatch">
    </a>

    <div class="mobile-auth">
        <?php if ($sessionUserId > 0): ?>
            <a href="/?page=profile&id=<?= $sessionUserId ?>&edit=1" class="mobile-avatar-link">
                <img
                    src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>"
                    class="mobile-avatar<?= $isDefaultHeaderAvatar ? ' mobile-avatar-default' : '' ?>"
                    alt="תמונת משתמש">
            </a>

            <a href="/logout.php" class="mobile-auth-btn">התנתקות</a>
        <?php else: ?>
            <a href="?page=login" class="mobile-auth-btn">התחברות</a>
            <a href="?page=register" class="mobile-auth-btn mobile-auth-btn-primary">הרשמה</a>
        <?php endif; ?>
    </div>
</div>

<header class="site-header">

<a href="?page=home" class="site-logo-text">
    <img
        src="/images/logonew.jpeg"
        alt="LoveMatch"
        class="site-logo-img">
</a>
    <!--<a href="?page=home" class="site-logo-text">
        <span class="logo-heart left">❤ </span>
        <span class="logo-text">LoveMatch</span>
        <span class="logo-heart right">&nbsp❤</span>
    </a>-->
<button type="button"
        class="mobile-menu-toggle"
        id="mobileMenuToggle"
        onclick="openUnifiedMobileNav()"
        aria-label="פתיחת תפריט"
        aria-expanded="false">☰</button>
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

    <div id="mobileNavOverlay" class="mobile-nav-overlay" onclick="closeUnifiedMobileNav()"></div>

    <aside id="mobileNav" class="mobile-nav" aria-hidden="true">
        <button type="button" class="mobile-nav-close" onclick="closeUnifiedMobileNav()" aria-label="סגירת תפריט">×</button>
        <div class="mobile-nav-title">תפריט</div>
        <div class="mobile-nav-menu">
            <a href="/?page=home">בית</a>
            <a href="/?page=search">חיפוש</a>
            <a href="/?page=advanced_search">התאמות</a>
            <?php if ($sessionUserId > 0): ?>
                <a href="/?page=messages">הודעות</a>
                <a href="/?page=inbox">תיבת דואר</a>
                <a href="/?page=views">צפיות</a>
                <a href="/?page=profile&id=<?= $sessionUserId ?>&edit=1">פרופיל</a>
                <?php if (!$headerHasPremium): ?>
                    <a href="/subscription.php?return=<?= urlencode($headerReturnUrl) ?>">רכישת מנוי</a>
                <?php endif; ?>
                <a href="/logout.php">התנתקות</a>
            <?php else: ?>
                <a href="/?page=login">התחברות</a>
                <a href="/?page=register">הרשמה</a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="auth">
        <?php if ($sessionUserId > 0): ?>

            <?php if (!$headerHasPremium): ?>
                <a
                    href="/subscription.php?return=<?= urlencode($headerReturnUrl) ?>"
                    class="header-subscription-btn"
                    onclick="
                        if (typeof showSubscriptionPopup === 'function') {
                            showSubscriptionPopup();
                            return false;
                        }
                        if (typeof openSubscriptionPopup === 'function') {
                            openSubscriptionPopup();
                            return false;
                        }
                    ">
                    💎 רכישת מנוי
                </a>
            <?php endif; ?>

            <span class="welcome-user">
                שלום <?= htmlspecialchars($sessionUserName ?: 'משתמש', ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="/?page=profile&id=<?= $sessionUserId ?>&edit=1" class="header-avatar-link">
                <img
                    src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>"
                    class="header-avatar<?= $isDefaultHeaderAvatar ? ' header-avatar-default' : '' ?>"
                    alt="תמונת משתמש">
            </a>

            <a href="/logout.php" class="auth-btn logout-btn">התנתקות</a>

        <?php else: ?>

            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>

        <?php endif; ?>
    </div>

</header>

<div class="new-site-banner">
    💙 <strong>חדש ב-LoveMatch</strong> — קהילת ההכרויות שלנו רק מתחילה, וזה הזמן להצטרף ולהכיר.
</div>

<?php
if ($sessionUserId > 0 && !$headerHasPremium) {
    require_once __DIR__ . '/../subscription_popup.php';
}
?>


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

<script>
function openUnifiedMobileNav() {
    const nav = document.getElementById('mobileNav');
    const overlay = document.getElementById('mobileNavOverlay');
    const btn = document.getElementById('mobileMenuToggle');
    const topBtn = document.getElementById('mobileTopMenuToggle');
    if (nav) { nav.classList.add('open'); nav.setAttribute('aria-hidden','false'); }
    if (overlay) overlay.classList.add('show');
    if (btn) btn.setAttribute('aria-expanded','true');
    if (topBtn) topBtn.setAttribute('aria-expanded','true');
    document.body.style.overflow='hidden';
}
function closeUnifiedMobileNav() {
    const nav = document.getElementById('mobileNav');
    const overlay = document.getElementById('mobileNavOverlay');
    const btn = document.getElementById('mobileMenuToggle');
    const topBtn = document.getElementById('mobileTopMenuToggle');
    if (nav) { nav.classList.remove('open'); nav.setAttribute('aria-hidden','true'); }
    if (overlay) overlay.classList.remove('show');
    if (btn) btn.setAttribute('aria-expanded','false');
    if (topBtn) topBtn.setAttribute('aria-expanded','false');
    document.body.style.overflow='';
}
</script>

<style>
.new-site-banner {
    width: 100%;
    box-sizing: border-box;
    background: #eef6ff;
    color: #1e3a5f;
    border-bottom: 1px solid #dbeafe;
    padding: 7px 10px;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.4;
}

.new-site-banner strong {
    color: #2563eb;
    font-weight: 800;
}

@media (max-width: 380px) {
    .new-site-banner {
        font-size: 11px;
        padding: 6px 8px;
    }
}


/* ===== Unified mobile header ===== */
.mobile-header-row {
    display: none;
}

.mobile-nav,
.mobile-nav-overlay {
    display: none;
}

@media (max-width: 640px) {

    /* Desktop header is visually removed on mobile,
       while the fixed sidebar/overlay inside it remain functional. */
    .site-header {
        width: 100% !important;
        height: 0 !important;
        min-height: 0 !important;
        max-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        overflow: visible !important;
        position: static !important;
    }

    .site-header > .site-logo-text,
    .site-header > .mobile-menu-toggle,
    .site-header > .links,
    .site-header > .auth {
        display: none !important;
    }

    .mobile-header-row {
        width: 100%;
        min-height: 78px;
        box-sizing: border-box;
        padding: 8px 12px;
        display: grid;
        grid-template-columns: 44px 1fr auto;
        align-items: center;
        gap: 8px;
        direction: ltr;
        background: #fff;
        border-bottom: 1px solid #ececec;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .06);
        position: relative;
        z-index: 101;
    }

    .mobile-top-menu-toggle {
        grid-column: 1;
        justify-self: start;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        color: #222;
        font-size: 25px;
        line-height: 1;
        cursor: pointer;
    }

    .mobile-site-logo {
        grid-column: 2;
        justify-self: center;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .mobile-site-logo img {
        display: block;
        width: 76px;
        max-width: 76px;
        height: auto;
    }

    .mobile-auth {
        grid-column: 3;
        justify-self: end;
        display: flex;
        align-items: center;
        gap: 6px;
        direction: rtl;
        white-space: nowrap;
    }

    .mobile-auth-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 34px;
        padding: 0 10px;
        border-radius: 10px;
        border: 1px solid #ececec;
        background: #fff;
        color: #b91c1c;
        text-decoration: none;
        font-size: 11px;
        font-weight: 700;
        box-sizing: border-box;
    }

    .mobile-auth-btn-primary {
        background: #d91f4f;
        color: #fff;
        border-color: #d91f4f;
    }

    .mobile-avatar-link {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 50%;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        text-decoration: none;
    }

    .mobile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .mobile-avatar-default {
        object-fit: contain;
        padding: 3px;
        box-sizing: border-box;
    }

    .new-site-banner {
        width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        position: relative !important;
        z-index: 10 !important;
        font-size: 11px !important;
        line-height: 1.45 !important;
        padding: 7px 10px !important;
        text-align: center !important;
    }

    .mobile-nav-overlay {
        position: fixed;
        inset: 0;
        z-index: 99998;
        background: rgba(0, 0, 0, .38);
    }

    .mobile-nav-overlay.show {
        display: block;
    }

    .mobile-nav {
        display: block;
        position: fixed;
        top: 0;
        right: -280px;
        width: 260px;
        max-width: 82vw;
        height: 100vh;
        z-index: 99999;
        background: #fff;
        padding: 18px 0 24px;
        box-sizing: border-box;
        transition: right .25s ease;
        overflow-y: auto;
        direction: rtl;
        box-shadow: -8px 0 28px rgba(0, 0, 0, .15);
    }

    .mobile-nav.open {
        right: 0;
    }

    .mobile-nav-title {
        padding: 8px 18px 14px;
        color: #d91f4f;
        font-size: 18px;
        font-weight: 800;
        border-bottom: 1px solid #eee;
    }

    .mobile-nav-close {
        position: absolute;
        top: 10px;
        left: 12px;
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: #f4f4f4;
        font-size: 23px;
        cursor: pointer;
    }

    .mobile-nav-menu a {
        display: block;
        padding: 14px 18px;
        border-bottom: 1px solid #eee;
        color: #222;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
    }

    .mobile-nav-menu a:hover {
        background: #f8fafc;
        color: #d91f4f;
    }
}



</style>