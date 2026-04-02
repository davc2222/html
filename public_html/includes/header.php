<?php
// =======================================================
// HEADER - כולל תפריט עליון + משתמש + badges
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* חיבור למסד */
require_once __DIR__ . '/../config/config.php';

/* דף נוכחי */
$page = $_GET['page'] ?? 'home';

/* משתמש */
$userLoggedIn = !empty($_SESSION['user_id']);
$headerUserId = $_SESSION['user_id'] ?? '';
$headerUserName = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? '');

/* תמונת משתמש */
$headerUserImage = '/images/no_photo.jpg';

if (!empty($_SESSION['user_main_pic'])) {
    $headerUserImage = $_SESSION['user_main_pic'];
} elseif (!empty($_SESSION['user_image'])) {
    $headerUserImage = '/images/' . $_SESSION['user_image'];
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>LoveMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS ראשי -->
    <link rel="stylesheet" href="/css/style.css?v=201">
</head>

<body>

<header class="site-header">

    <!-- ===================================================
         לוגו
    =================================================== -->
    <div class="logo">
        <a href="?page=home" class="logo-link">
            <span class="logo-heart">❤️</span>
            <span class="logo-text">LoveMatch</span>
        </a>
    </div>

    <!-- ===================================================
         תפריט אייקונים (חיפוש / צפיות / הודעות)
    =================================================== -->
    <nav class="links">
       <!-- בית -->
<a href="?page=home" class="menu-link <?= ($page === 'home') ? 'active' : '' ?>" title="בית">
    <span class="menu-link-icon">🏠</span>
    <span class="menu-link-text">בית</span>
</a>
        <!-- חיפוש -->
        <a href="?page=search" class="menu-link <?= ($page === 'search') ? 'active' : '' ?>" title="חיפוש">
            <span class="menu-link-icon">🔍</span>
            <span class="menu-link-text">חיפוש</span>
        </a>

        <?php if ($userLoggedIn): ?>

            <!-- צפיות -->
            <a href="?page=views" class="menu-link has-badge <?= ($page === 'views') ? 'active' : '' ?>" title="צפיות">
                <span class="menu-link-icon">👁</span>
                <span class="menu-link-text">צפיות</span>
                <span id="viewsBadge" class="menu-badge">0</span>
            </a>

            <!-- הודעות -->
            <a href="?page=messages" class="menu-link has-badge <?= ($page === 'messages') ? 'active' : '' ?>" title="הודעות">
                <span class="menu-link-icon">💬</span>
                <span class="menu-link-text">הודעות</span>
                <span id="messagesBadge" class="menu-badge">0</span>
            </a>

        <?php endif; ?>

    </nav>

    <!-- ===================================================
         אזור משתמש (תמונה + שלום + התנתקות)
    =================================================== -->
    <div class="auth">

        <?php if ($userLoggedIn): ?>

            <!-- תמונת משתמש -->
            <a href="?page=profile&id=<?= urlencode($headerUserId) ?>" class="header-avatar-link">
                <img src="<?= htmlspecialchars($headerUserImage) ?>" class="header-avatar">
            </a>

            <!-- שם משתמש -->
            <span class="welcome-user">
                שלום <?= htmlspecialchars($headerUserName) ?>
            </span>

            <!-- התנתקות -->
            <a href="/logout.php" class="auth-btn logout-btn">התנתקות</a>

        <?php else: ?>

            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>

        <?php endif; ?>

    </div>

</header>

<!-- ===================================================
     JAVASCRIPT - עדכון badge (Polling כל 5 שניות)
=================================================== -->
<script>
let lastViews = null;
let lastMessages = null;

async function updateHeaderBadges() {
    try {
        const response = await fetch('/get_header_counts.php', {
            cache: 'no-store'
        });

        if (!response.ok) return;

        const data = await response.json();

        const viewsBadge = document.getElementById('viewsBadge');
        const messagesBadge = document.getElementById('messagesBadge');

        /* ===== צפיות ===== */
        if (viewsBadge && data.views !== undefined) {
            if (data.views > 0) {
                viewsBadge.style.display = 'flex';
                viewsBadge.textContent = data.views;

                if (lastViews !== null && data.views > lastViews) {
                    viewsBadge.classList.add('badge-pulse');
                }
            } else {
                viewsBadge.style.display = 'none';
            }
            lastViews = data.views;
        }

        /* ===== הודעות ===== */
        if (messagesBadge && data.messages !== undefined) {
            if (data.messages > 0) {
                messagesBadge.style.display = 'flex';
                messagesBadge.textContent = data.messages;

                if (lastMessages !== null && data.messages > lastMessages) {
                    messagesBadge.classList.add('badge-pulse');
                }
            } else {
                messagesBadge.style.display = 'none';
            }
            lastMessages = data.messages;
        }

    } catch (error) {
        console.error('Badge update failed:', error);
    }
}

/* ריצה ראשונית + polling */
updateHeaderBadges();
setInterval(updateHeaderBadges, 5000);
</script>