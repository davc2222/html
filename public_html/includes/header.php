<?php
// ===== FILE: includes/header.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$page = $_GET['page'] ?? 'home';

$sessionUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$sessionUserName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? '')));

$headerAvatar = '/images/no_photo.jpg';

if (!empty($_SESSION['user_main_pic'])) {
    $headerAvatar = (string)$_SESSION['user_main_pic'];
} elseif (!empty($_SESSION['user_image'])) {
    $headerAvatar = '/images/' . $_SESSION['user_image'];
}

$menu = [
    'home'            => ['label' => 'בית', 'icon' => '🏠'],
    'search'          => ['label' => 'חיפוש', 'icon' => '🔎'],
    'advanced_search' => ['label' => 'חיפוש מתקדם', 'icon' => '✨'],
    'messages'        => ['label' => 'הודעות', 'icon' => '💌'],
    'views'           => ['label' => 'צפיות', 'icon' => '👁']
];
?>

<header class="site-header">

    <div class="logo">
        <a href="?page=home" class="logo-link">
            <span class="logo-text">LoveMatch</span>
            <span class="logo-heart">❤</span>
        </a>
    </div>

    <nav class="links">
        <?php foreach ($menu as $p => $item): ?>
            <a href="?page=<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>"
                class="menu-link <?= ($page === $p) ? 'active' : '' ?>">

                <span class="menu-link-icon"><?= $item['icon'] ?></span>
                <span class="menu-link-text"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>

                <?php if ($p === 'messages'): ?>
                    <span id="messagesBadge" class="menu-badge"></span>
                <?php endif; ?>

                <?php if ($p === 'views'): ?>
                    <span id="viewsBadge" class="menu-badge"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="auth">
        <?php if ($sessionUserId > 0): ?>
            <span class="welcome-user">
                שלום <?= htmlspecialchars($sessionUserName !== '' ? $sessionUserName : 'משתמש', ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="?page=profile&id=<?= $sessionUserId ?>&edit=1" class="header-avatar-link" title="הפרופיל שלי">
                <img src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="user" class="header-avatar">
            </a>

            <a href="logout.php" class="auth-btn logout-btn">התנתקות</a>
        <?php else: ?>
            <a href="?page=login" class="auth-btn <?= ($page === 'login') ? 'active' : '' ?>">התחברות</a>
            <a href="?page=register" class="auth-btn <?= ($page === 'register') ? 'active' : '' ?>">הרשמה</a>
        <?php endif; ?>
    </div>

</header>

<?php include __DIR__ . '/chat_windows.php'; ?>