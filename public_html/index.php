<?php
// index.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'home';

/*
 * verify_email חייב לרוץ לפני כל פלט HTML
 */
if ($page === 'verify_email') {
    require __DIR__ . '/verify_email.php';
    exit;
}

/*
 * דפים שמחייבים התחברות
 * הבדיקה חייבת להיות לפני כל HTML ולפני header.php
 */
$protectedPages = ['profile', 'search', 'advanced_search', 'messages', 'views', 'inbox'];

if (in_array($page, $protectedPages, true) && empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="he">

<head>
    <meta charset="UTF-8">
    <title>LoveMatch</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.min.css">
</head>

<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <?php
    switch ($page) {
        case 'home':
            include 'home.php';
            break;

        case 'profile':
            include 'profile.php';
            break;

        case 'search':
            include 'search.php';
            break;

        case 'advanced_search':
            include 'advanced_search.php';
            break;

        case 'messages':
            include 'messages.php';
            break;

        case 'views':
            include 'views.php';
            break;

        case 'login':
            include 'login.php';
            break;

        case 'register':
            include 'register.php';
            break;

        case 'verify_notice':
            include 'verify_notice.php';
            break;

        case 'inbox':
            include 'inbox.php';
            break;


        case 'blocked_users':
            include __DIR__ . '/blocked_users.php';
            break;

        case 'viewed_by_me':
            require 'viewed_by_me.php';
            break;


        default:
            include 'home.php';
            break;
    }
    ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php include __DIR__ . '/includes/chat_windows.php'; ?>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/js/lightbox.min.js"></script>
    <script>
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'תמונה %1 מתוך %2'
        });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>