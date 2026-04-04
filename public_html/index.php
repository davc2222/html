<?php
// ===== FILE: index.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch</title>
    <link rel="stylesheet" href="/css/style.css">
    <?php include __DIR__ . '/includes/chat_windows.php'; ?>
</head>

<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="site-main">
        <?php
        switch ($page) {
            case 'home':
                include __DIR__ . '/home.php';
                break;

            case 'search':
                include __DIR__ . '/search.php';
                break;

            case 'advanced_search':
                include __DIR__ . '/advanced_search.php';
                break;

            case 'profile':
                include __DIR__ . '/profile.php';
                break;

            case 'messages':
                include __DIR__ . '/messages.php';
                break;

            case 'views':
                include __DIR__ . '/views.php';
                break;

            case 'login':
                include __DIR__ . '/login.php';
                break;

            case 'register':
                include __DIR__ . '/register.php';
                break;

            default:
                include __DIR__ . '/home.php';
                break;
        }
        ?>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>

</html>