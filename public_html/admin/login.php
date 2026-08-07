<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/config.php';

if (!is_array($config) || empty($config['admin_password'])) {
    die('שגיאה בקובץ admin/config.php: לא הוגדרה סיסמת מנהל');
}

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim((string) ($_POST['password'] ?? ''));

    if (hash_equals((string) $config['admin_password'], $password)) {
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;

        header('Location: /admin/index.php');
        exit;
    }

    $error = 'סיסמת מנהל שגויה';
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>כניסת מנהל</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="admin-login-page">

<div class="admin-login-box">

    <div class="admin-login-logo">
        <div class="admin-logo-icon">❤</div>
        <h1>LoveMatch</h1>
        <p>כניסה לפאנל הניהול</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="admin-alert admin-alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/admin/login.php" class="admin-login-form">

        <label for="password">סיסמת מנהל</label>

        <input
            type="password"
            id="password"
            name="password"
            required
            autofocus
            autocomplete="current-password"
        >

        <button type="submit">כניסה</button>

    </form>

</div>

</body>
</html>