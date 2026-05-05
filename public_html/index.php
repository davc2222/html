<?php
// ================================
// INDEX DESKTOP (רגיל)
// ================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$host       = $_SERVER['HTTP_HOST'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isGooglebot = preg_match('/Googlebot/i', $userAgent);

/*
|------------------------------------------------------------
| אחרי logout לא מעבירים למובייל
|------------------------------------------------------------
*/
$forceDesktop =
    (isset($_GET['desktop']) && $_GET['desktop'] === '1') ||
    (isset($_GET['from_logout']) && $_GET['from_logout'] === '1') ||
    (!empty($_COOKIE['force_desktop_after_logout']) && $_COOKIE['force_desktop_after_logout'] === '1');

if ($forceDesktop) {
    $_SESSION['force_desktop'] = true;
}
/*
|------------------------------------------------------------
| זיהוי סביבת לוקאל
|------------------------------------------------------------
*/
$isLocalhost =
    stripos($host, 'localhost') !== false ||
    stripos($host, '127.0.0.1') !== false ||
    preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host);

$isMobile = false;

/*
|------------------------------------------------------------
| זיהוי אייפון
|------------------------------------------------------------
*/
if (preg_match('/iPhone|iPod/i', $userAgent)) {
    $isMobile = true;
}

/*
|------------------------------------------------------------
| זיהוי אנדרואיד
|------------------------------------------------------------
| רק טלפונים אמיתיים.
| לא מחשב, לא Windows, לא מצב דפדפן שמתחזה חלקית.
*/
if (
    preg_match('/Android/i', $userAgent) &&
    preg_match('/Mobile/i', $userAgent) &&
    !preg_match('/Windows NT|Win64|x64/i', $userAgent)
) {
    $isMobile = true;
}

/*
|------------------------------------------------------------
| כפיית אתר רגיל
|------------------------------------------------------------
| אחרי logout אנחנו מגיעים עם:
| /?desktop=1
|
| זה אומר:
| אל תעביר אוטומטית למובייל.
*/
if (isset($_GET['desktop']) && $_GET['desktop'] === '1') {
    $_SESSION['force_desktop'] = true;
}

/*
|------------------------------------------------------------
| מעבר ידני למובייל
|------------------------------------------------------------
| מאפשר מעבר רק כאשר יש לחיצה אמיתית באתר.
*/
if (
    isset($_GET['mobile']) &&
    $_GET['mobile'] === '1' &&
    strpos($requestUri, '/mobile') !== 0 &&
    !empty($_SERVER['HTTP_REFERER']) &&
    strpos($_SERVER['HTTP_REFERER'], '/mobile') === false
) {
    unset($_SESSION['force_desktop']);

    header('Location: /mobile/');
    exit;
}

/*
|------------------------------------------------------------
| מעבר אוטומטי למובייל
|------------------------------------------------------------
| עובד רק אם:
| - זה לא לוקאל
| - זה באמת נייד
| - לא נמצאים כבר במובייל
| - לא הופעל force_desktop
*/
if (
    !$isGooglebot &&
    !$forceDesktop &&
    empty($_COOKIE['force_desktop_after_logout']) &&
    !$isLocalhost &&
    $isMobile &&
    strpos($requestUri, '/mobile') !== 0
) {
    header('Location: /mobile/');
    exit;
}

//*----------------------------------------------------------------------------------------------------------------------*?/

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

$page = $_GET['page'] ?? 'home';

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
    <meta name="googlebot" content="index, follow">
    <meta name="description" content="LoveMatch הוא אתר הכרויות חכם למציאת זוגיות אמיתית. הירשם עכשיו, מצא התאמות ושלח הודעות בקלות.">
    <meta name="keywords" content="אתר הכרויות, הכרויות בישראל, זוגיות, דייטים, אהבה, LoveMatch , טינדר  לאבמי  רוסיות, אתר חינמי">
    <meta name="author" content="LoveMatch">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="https://lovematch.co.il/">
    <link rel="alternate" media="only screen and (max-width: 640px)" href="https://lovematch.co.il/mobile/">

    <meta property="og:title" content="LoveMatch - אתר הכרויות">
    <meta property="og:description" content="מצא אהבה אמיתית ב-LoveMatch">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://lovematch.co.il">
    <meta property="og:image" content="https://lovematch.co.il/images/og-image.jpg">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="LoveMatch">
    <meta name="twitter:description" content="מצא אהבה בקלות">
    <meta name="twitter:image" content="https://lovematch.co.il/images/og-image.jpg">

    <link rel="icon" href="/images/favicon.ico?v=2">
    <link rel="shortcut icon" href="/images/favicon.ico?v=2">

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.min.css">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-1039498648"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-1039498648');
    </script>


    <!-- Event snippet for צפייה בדף conversion page -->
    <script>
        gtag('event', 'conversion', {
            'send_to': 'AW-1039498648/n0mGCL3x46UcEJj71e8D',
            'value': 1.0,
            'currency': 'ILS'
        });
    </script>
</head>

<body>

    <div class="site-page">

        <?php include __DIR__ . '/includes/header.php'; ?>

        <div class="site-main">
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
        </div>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <?php include __DIR__ . '/includes/chat_windows.php'; ?>
        <?php endif; ?>

        <?php include __DIR__ . '/includes/footer.php'; ?>

    </div>

    <?php include __DIR__ . '/popups.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/js/lightbox.min.js"></script>

    <script>
        lightbox.option({
            resizeDuration: 200,
            wrapAround: true,
            albumLabel: 'תמונה %1 מתוך %2'
        });
    </script>

    <script>
        (function() {
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

</body>

</html>