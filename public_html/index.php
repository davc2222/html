<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'home';

if ($page === 'verify_email') {
    require __DIR__ . '/verify_email.php';
    exit;
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LoveMatch - אתר הכרויות חכם למציאת אהבה</title>

    <!-- SEO -->
    <meta name="description" content="LoveMatch הוא אתר הכרויות חכם למציאת זוגיות אמיתית. הירשם עכשיו, מצא התאמות ושלח הודעות בקלות.">
    <meta name="keywords" content="אתר הכרויות, הכרויות בישראל, זוגיות, דייטים, אהבה, LoveMatch">
    <meta name="author" content="LoveMatch">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="LoveMatch - אתר הכרויות">
    <meta property="og:description" content="מצא אהבה אמיתית ב-LoveMatch">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost">
    <meta property="og:image" content="http://localhost/images/og-image.jpg">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="LoveMatch">
    <meta name="twitter:description" content="מצא אהבה בקלות">
    <meta name="twitter:image" content="http://localhost/images/og-image.jpg">

    <!-- FAVICON -->
    <link rel="icon" href="/images/favicon.ico?v=2">
    <link rel="shortcut icon" href="/images/favicon.ico?v=2">

    <!-- CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.min.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php
switch ($page) {
    case 'home': include 'home.php'; break;
    case 'profile': include 'profile.php'; break;
    case 'search': include 'search.php'; break;
    case 'advanced_search': include 'advanced_search.php'; break;
    case 'messages': include 'messages.php'; break;
    case 'views': include 'views.php'; break;
    case 'login': include 'login.php'; break;
    case 'register': include 'register.php'; break;
    case 'verify_notice': include 'verify_notice.php'; break;
    case 'inbox': include 'inbox.php'; break;
    case 'blocked_users': include __DIR__ . '/blocked_users.php'; break;
    case 'viewed_by_me': require 'viewed_by_me.php'; break;
    default: include 'home.php'; break;
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

<!-- 🔥 TITLE BLINK -->
<script>
(function () {
    const normalTitle = document.title.trim() || 'LoveMatch';
    const alertTitle = '💬 הודעה חדשה!';
    let interval = null;
    let isBlinking = false;

    function startBlink() {
        if (isBlinking) return;

        isBlinking = true;
        let showAlert = false;

        interval = setInterval(() => {
            document.title = showAlert ? alertTitle : normalTitle;
            showAlert = !showAlert;
        }, 1000);
    }

    function stopBlink() {
        isBlinking = false;

        if (interval) {
            clearInterval(interval);
            interval = null;
        }

        document.title = normalTitle;
    }

    window.startTitleBlink = startBlink;
    window.stopTitleBlink = stopBlink;
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>