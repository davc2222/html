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

            <a href="/?page=profile&id=<?= $sessionUserId ?>&edit=1" class="header-avatar-link">
                <img
                    src="<?= htmlspecialchars($headerAvatar, ENT_QUOTES, 'UTF-8') ?>"
                    class="header-avatar<?= $isDefaultHeaderAvatar ? ' header-avatar-default' : '' ?>"
                    alt="תמונת משתמש">
            </a>

            <a href="logout.php" class="auth-btn logout-btn">התנתקות</a>

        <?php else: ?>

            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>

        <?php endif; ?>
    </div>

</header>