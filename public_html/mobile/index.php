
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['site_mode'] = 'mobile';

/*
|--------------------------------------------------------------------------
| הגדרות בסיס
|--------------------------------------------------------------------------
*/
$page = $_GET['page'] ?? 'home';

$allowedPages = [
    'home',
    'login',
    'register',
    'profile',
    'search',
    'advanced_search',
    'messages',
    'views',
    'inbox',
    'verify_notice'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$protectedPages = [
    'profile',
    'search',
    'advanced_search',
    'messages',
    'views',
    'inbox'
];

if (in_array($page, $protectedPages, true) && empty($_SESSION['user_id'])) {
    header('Location: /mobile/?page=login');
    exit;
}

if ($page === 'verify_email') {
    require __DIR__ . '/../verify_email.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch Mobile</title>

    <link rel="icon" href="/images/favicon.ico?v=2">
    <link rel="shortcut icon" href="/images/favicon.ico?v=2">
  
    <link rel="stylesheet" href="/mobile/css/style.css?v=<?= time() ?>">

</head>

<body>

    <div class="mobile-site">

        <header class="mobile-header">
            <div class="mobile-header-top">
                <a href="/mobile/?page=home" class="mobile-logo">
                    <span>❤</span>
                    <span>LoveMatch</span>
                    <span>❤</span>
                </a>
            </div>

            <div class="mobile-auth">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <span class="mobile-welcome">
                        שלום <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'משתמש', ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <a href="/mobile/?page=profile&id=<?= (int)$_SESSION['user_id'] ?>" class="mobile-auth-btn">
                        הפרופיל שלי
                    </a>

                    <a href="/logout.php" class="mobile-auth-btn mobile-auth-btn-logout">
                        התנתקות
                    </a>
                <?php else: ?>
                    <a href="/mobile/?page=login" class="mobile-auth-btn">התחברות</a>
                    <a href="/mobile/?page=register" class="mobile-auth-btn">הרשמה</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="mobile-main">
            <?php
            switch ($page) {
                case 'home':
                    if (file_exists(__DIR__ . '/../home.php')) {
                        include __DIR__ . '/../home.php';
                    } else {
                        echo '<div class="mobile-card"><h2>ברוכים הבאים ל־LoveMatch Mobile</h2><p>גרסת המובייל פעילה. עכשיו אפשר להתחיל לבנות את הדפים אחד אחד.</p></div>';
                    }
                    break;

                case 'login':
                    if (file_exists(__DIR__ . '/../login.php')) {
                        include __DIR__ . '/../login.php';
                    } else {
                        echo '<div class="mobile-card"><h2>התחברות</h2><p>דף login לא נמצא.</p></div>';
                    }
                    break;

                case 'register':
                    if (file_exists(__DIR__ . '/../register.php')) {
                        include __DIR__ . '/../register.php';
                    } else {
                        echo '<div class="mobile-card"><h2>הרשמה</h2><p>דף register לא נמצא.</p></div>';
                    }
                    break;

                case 'profile':
                    if (file_exists(__DIR__ . '/../profile.php')) {
                        include __DIR__ . '/../profile.php';
                    } else {
                        echo '<div class="mobile-card"><h2>פרופיל</h2><p>דף profile לא נמצא.</p></div>';
                    }
                    break;

                case 'search':
                    if (file_exists(__DIR__ . '/../search.php')) {
                        include __DIR__ . '/../search.php';
                    } else {
                        echo '<div class="mobile-card"><h2>חיפוש</h2><p>דף search לא נמצא.</p></div>';
                    }
                    break;

                case 'advanced_search':
                    if (file_exists(__DIR__ . '/../advanced_search.php')) {
                        include __DIR__ . '/../advanced_search.php';
                    } else {
                        echo '<div class="mobile-card"><h2>התאמות</h2><p>דף advanced_search לא נמצא.</p></div>';
                    }
                    break;

                case 'messages':
                    if (file_exists(__DIR__ . '/../messages.php')) {
                        include __DIR__ . '/../messages.php';
                    } else {
                        echo '<div class="mobile-card"><h2>הודעות</h2><p>דף messages לא נמצא.</p></div>';
                    }
                    break;

                case 'views':
                    if (file_exists(__DIR__ . '/../views.php')) {
                        include __DIR__ . '/../views.php';
                    } else {
                        echo '<div class="mobile-card"><h2>צפיות</h2><p>דף views לא נמצא.</p></div>';
                    }
                    break;

                case 'inbox':
                    if (file_exists(__DIR__ . '/../inbox.php')) {
                        include __DIR__ . '/../inbox.php';
                    } else {
                        echo '<div class="mobile-card"><h2>תיבת דואר</h2><p>דף inbox לא נמצא.</p></div>';
                    }
                    break;

                case 'verify_notice':
                    if (file_exists(__DIR__ . '/../verify_notice.php')) {
                        include __DIR__ . '/../verify_notice.php';
                    } else {
                        echo '<div class="mobile-card"><h2>אימות מייל</h2><p>בדוק את תיבת המייל שלך להמשך.</p></div>';
                    }
                    break;

                default:
                    echo '<div class="mobile-card"><h2>ברוכים הבאים</h2></div>';
                    break;
            }
            ?>
        </main>

        <footer class="mobile-footer">
            <p>LoveMatch Mobile</p>
        </footer>

    </div>

    <nav class="mobile-bottom-nav">
        <a href="/mobile/?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">
            <span>🏠</span>
            <small>בית</small>
        </a>

        <a href="/mobile/?page=search" class="<?= $page === 'search' ? 'active' : '' ?>">
            <span>🔎</span>
            <small>חיפוש</small>
        </a>

        <a href="/mobile/?page=advanced_search" class="<?= $page === 'advanced_search' ? 'active' : '' ?>">
            <span>✨</span>
            <small>התאמות</small>
        </a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="/mobile/?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>">
                <span>💬</span>
                <small>הודעות</small>
            </a>

            <a href="/mobile/?page=profile&id=<?= (int)$_SESSION['user_id'] ?>" class="<?= $page === 'profile' ? 'active' : '' ?>">
                <span>👤</span>
                <small>פרופיל</small>
            </a>
        <?php endif; ?>
    </nav>

</body>

</html>