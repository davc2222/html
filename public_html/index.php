<?php
// index.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="he">

<head>
    <meta charset="UTF-8">
    <title>LoveMatch</title>
    <link rel="stylesheet" href="/css/style.css">
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

        default:
            include 'home.php';
            break;
    }
    ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php include __DIR__ . '/includes/chat_windows.php'; ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <?php include __DIR__ . '/includes/chat_windows.php'; ?>
    <?php endif; ?>
</body>

</html>