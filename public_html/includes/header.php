<?php
// =======================
// FILE: header.php
// =======================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'home';

$userLoggedIn = !empty($_SESSION['user_id']);

$headerUserId = $_SESSION['user_id'] ?? '';
$headerUserName = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? '');

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
    <link rel="stylesheet" href="/css/style.css?v=126">
</head>

<body>

    <header class="site-header">

        <div class="logo">
          <a href="?page=home" class="logo-link">
    <span class="logo-heart">❤️</span>
    <span class="logo-text">LoveMatch</span>
</a>
        </div>

        <nav class="links">
            <?php foreach ($menu as $p => $label): ?>
            <a href="?page=<?= $p ?>" class="<?= ($page === $p) ? 'active' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="auth">

            <?php if ($userLoggedIn): ?>

            <a href="?page=profile&id=<?= urlencode($headerUserId) ?>&edit=1" class="header-avatar-link">

                <img src="<?= htmlspecialchars($headerUserImage) ?>" class="header-avatar">
            </a>

            <span class="welcome-user">
                שלום <?= htmlspecialchars($headerUserName) ?>
            </span>

            <a href="/logout.php" class="auth-btn">התנתקות</a>

            <?php else: ?>

            <a href="?page=login" class="auth-btn">התחברות</a>
            <a href="?page=register" class="auth-btn">הרשמה</a>

            <?php endif; ?>

        </div>

    </header>