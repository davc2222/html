<?php
// ===== FILE: includes/header.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$page = $_GET['page'] ?? 'home';

$sessionUserId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
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

                <?php if ($sessionUserId > 0 && $p === 'messages'): ?>
                    <span id="headerMessagesBadge" class="menu-badge" style="display:none;">0</span>
                <?php endif; ?>

                <?php if ($sessionUserId > 0 && $p === 'views'): ?>
                    <span id="headerViewsBadge" class="menu-badge" style="display:none;">0</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="auth">
        <?php if ($sessionUserId > 0): ?>
            <span class="welcome-user">
                שלום <?= htmlspecialchars($sessionUserName ?: 'משתמש', ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="?page=profile&id=<?= $sessionUserId ?>&edit=1" class="header-avatar-link">
                <img src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>" class="header-avatar" alt="תמונת משתמש">
            </a>

            <a href="logout.php" class="auth-btn logout-btn">התנתקות</a>
        <?php else: ?>
            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>
        <?php endif; ?>
    </div>

</header>

<?php if ($sessionUserId > 0): ?>
    <script>
        function updateHeaderBadge(el, count) {
            if (!el) return;

            count = Number(count || 0);

            if (count > 0) {
                el.textContent = count > 99 ? '99+' : String(count);
                el.style.display = 'inline-flex';
            } else {
                el.textContent = '0';
                el.style.display = 'none';
            }
        }

        function loadHeaderCounts() {
            fetch('/get_header_counts.php')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    updateHeaderBadge(document.getElementById('headerMessagesBadge'), data.messages || 0);
                    updateHeaderBadge(document.getElementById('headerViewsBadge'), data.views || 0);
                })
                .catch(function() {});
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadHeaderCounts();
            setInterval(loadHeaderCounts, 5000);
        });
    </script>
<?php endif; ?>