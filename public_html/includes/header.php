    <!-- header.php-->

<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>LoveMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS ראשי -->
    <link rel="stylesheet" href="/css/style.css?v=125">
</head>

<body>

<header class="site-header">
    <!-- לוגו -->
    <div class="logo">
        <a href="?page=home">LoveMatch</a>
    </div>

    <!-- תפריט -->
    <nav class="links">
        <?php foreach ($menu as $p => $label): ?>
            <a href="?page=<?= $p ?>" 
               class="<?= ($page === $p) ? 'active' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- כפתורים -->
    <div class="auth">
        <?php if (!empty($_SESSION['user_logged_in'])): ?>
            
            <!-- משתמש מחובר -->
            <span class="welcome-user">
                שלום <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
            </span>

            <a href="/logout.php" class="auth-btn">התנתקות</a>

        <?php else: ?>

            <!-- משתמש לא מחובר -->
            <a href="?page=login" 
               class="auth-btn <?= ($page === 'login') ? 'active' : '' ?>">
                התחברות
            </a>

            <a href="?page=register" 
               class="auth-btn <?= ($page === 'register') ? 'active' : '' ?>">
                הרשמה
            </a>

        <?php endif; ?>
    </div>
</header>