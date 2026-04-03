<?php
// ===== FILE: index.php =====

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'home';

$allowed = [
    'home',
    'search',
    'messages',
    'views',
    'contact',
    'login',
    'register',
    'verify_notice',
    'verify_email',
    'profile'
];

if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

$page_file = __DIR__ . "/{$page}.php";
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="site-main">
    <?php
    if (file_exists($page_file)) {
        include $page_file;
    } else {
        echo "<div class='page-shell'><p>דף לא נמצא</p></div>";
    }
    ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function forceClearOverlays() {
    document.body.classList.remove('modal-open', 'loading', 'menu-open', 'chat-open');
    document.body.style.overflow = 'auto';

    document.querySelectorAll(
        '.overlay, .modal, .backdrop, .chat-overlay, .page-overlay, .loader, .loader-screen, ' +
        '.message-modal, .message-modal-overlay, .modal-overlay, .screen-overlay'
    ).forEach(function (el) {
        el.style.display = 'none';
        el.style.visibility = 'hidden';
        el.style.opacity = '0';
        el.classList.remove('active', 'open', 'show');
    });
}

window.addEventListener('load', forceClearOverlays);
document.addEventListener('DOMContentLoaded', forceClearOverlays);
setTimeout(forceClearOverlays, 100);
setTimeout(forceClearOverlays, 500);
setTimeout(forceClearOverlays, 1200);
</script>

</body>
</html>