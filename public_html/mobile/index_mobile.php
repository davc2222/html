<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$page = $_GET['page'] ?? 'home';

$allowedPages = [
    'home',
    'profile',
    'search',
    'advanced_search',
    'messages',
    'login',
    'register',
    'verify_notice'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$protectedPages = ['profile', 'search', 'advanced_search', 'messages'];

if (in_array($page, $protectedPages, true) && empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/mobile/?page=login');
    exit;
}

$currentUserId   = (int)($_SESSION['user_id'] ?? 0);
$currentUserName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'משתמש')));

function m_is_active(string $name, string $page): string {
    return $name === $page ? 'active' : '';
}

function m_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$mobileHeaderAvatar = '/images/default_male.svg';

if ($currentUserId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT Gender_Str
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $currentUserId]);

        $genderStr = trim((string)$stmt->fetchColumn());

        if ($genderStr === 'אישה') {
            $mobileHeaderAvatar = '/images/default_female.svg';
        }

        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Pic_Status = 1
            ORDER BY Main_Pic DESC, Pic_Num ASC
            LIMIT 1
        ");
        $stmt->execute([':id' => $currentUserId]);
        $pic = $stmt->fetchColumn();

        if ($pic) {
            $mobileHeaderAvatar = '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
        // fallback
    }
}
?>
<!DOCTYPE html>
<html lang="he">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch Mobile</title>
    <link rel="stylesheet" href="/mobile/css/style.css?v=<?= time() ?>">

    <style>
        .mobile-site {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .mobile-main {
            flex: 1;
        }

        .mobile-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 12px 14px 10px;
        }

        .mobile-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .mobile-logo {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 800;
            color: #d81b60;
            white-space: nowrap;
        }

        .mobile-logo span:first-child {
            font-size: 18px;
            line-height: 1;
        }

        .mobile-switch-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 12px;
            background: #ff4d6d;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .mobile-auth {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .mobile-user-box {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .mobile-user-avatar-link {
            display: inline-flex;
            text-decoration: none;
            flex: 0 0 auto;
        }

        .mobile-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #f2f2f2;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mobile-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .mobile-user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.15;
            min-width: 0;
        }

        .mobile-user-hello {
            font-size: 11px;
            color: #777;
        }

        .mobile-user-name {
            font-size: 14px;
            font-weight: 800;
            color: #d81b60;
            max-width: 130px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mobile-auth-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .mobile-auth-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            background: #f3f3f3;
            color: #333;
        }

        .mobile-auth-btn-profile {
            background: #d81b60;
            color: #fff;
        }

        .mobile-auth-btn-logout {
            background: #7a7a7a;
            color: #fff;
        }

        .mobile-footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            padding: 14px 10px 84px;
        }

        @media (max-width: 520px) {

            .mobile-header-top,
            .mobile-auth {
                gap: 8px;
            }

            .mobile-user-name {
                max-width: 90px;
                font-size: 13px;
            }

            .mobile-auth-btn,
            .mobile-switch-btn {
                min-height: 34px;
                padding: 0 12px;
                font-size: 12px;
            }

            .mobile-user-avatar {
                width: 38px;
                height: 38px;
            }
        }
    </style>
</head>

<body>
    <div class="mobile-site">

        <header class="mobile-header">
            <div class="mobile-header-top">
                <a href="<?= APP_URL ?>/mobile/?page=home" class="mobile-logo">
                    <span>❤</span>
                    <span>LoveMatch</span>
                </a>

                <?php if ($currentUserId > 0): ?>
                    <a href="<?= APP_URL ?>/mobile/?page=profile&id=<?= $currentUserId ?>&edit=1" class="mobile-switch-btn">
                        הפרופיל שלי
                    </a>
                <?php endif; ?>
            </div>

            <div class="mobile-auth">
                <?php if ($currentUserId > 0): ?>
                    <div class="mobile-user-box">
                        <a href="<?= APP_URL ?>/mobile/?page=profile&id=<?= $currentUserId ?>&edit=1" class="mobile-user-avatar-link">
                            <div class="mobile-user-avatar">
                                <img
                                    src="<?= m_e($mobileHeaderAvatar) ?>"
                                    alt="תמונת פרופיל"
                                    onerror="this.onerror=null;this.src='/images/default_male.svg';">
                            </div>
                        </a>

                        <div class="mobile-user-info">
                            <span class="mobile-user-hello">שלום</span>
                            <span class="mobile-user-name"><?= m_e($currentUserName) ?></span>
                        </div>
                    </div>

                    <div class="mobile-auth-actions">
                        <a href="<?= APP_URL ?>/mobile/?page=profile&id=<?= $currentUserId ?>&edit=1" class="mobile-auth-btn mobile-auth-btn-profile">
                            פרופיל
                        </a>

                        <a href="<?= APP_URL ?>/mobile/logout.php" class="mobile-auth-btn mobile-auth-btn-logout">
                            התנתקות
                        </a>
                    </div>
                <?php else: ?>
                    <div></div>

                    <div class="mobile-auth-actions">
                        <a href="<?= APP_URL ?>/mobile/?page=login" class="mobile-auth-btn">
                            התחברות
                        </a>

                        <a href="<?= APP_URL ?>/mobile/?page=register" class="mobile-auth-btn mobile-auth-btn-profile">
                            הרשמה
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="mobile-main">
            <?php
            switch ($page) {
                case 'home':
                    include __DIR__ . '/home.php';
                    break;

                case 'profile':
                    include __DIR__ . '/profile.php';
                    break;

                case 'search':
                    include __DIR__ . '/search.php';
                    break;

                case 'advanced_search':
                    include __DIR__ . '/advanced_search.php';
                    break;

                case 'messages':
                    include __DIR__ . '/messages.php';
                    break;

                case 'login':
                    include __DIR__ . '/login.php';
                    break;

                case 'register':
                    include __DIR__ . '/register.php';
                    break;

                case 'verify_notice':
                    include __DIR__ . '/verify_notice.php';
                    break;

                default:
                    include __DIR__ . '/home.php';
                    break;
            }
            ?>
        </main>

        <footer class="mobile-footer">
            LoveMatch
        </footer>

        <nav class="mobile-bottom-nav">
            <a href="<?= APP_URL ?>/mobile/?page=home" class="<?= m_is_active('home', $page) ?>">
                <span>🏠</span>
                <small>בית</small>
            </a>

            <?php if ($currentUserId > 0): ?>
                <a href="<?= APP_URL ?>/mobile/?page=search" class="<?= m_is_active('search', $page) ?>">
                    <span>🔎</span>
                    <small>חיפוש</small>
                </a>

                <a href="<?= APP_URL ?>/mobile/?page=advanced_search" class="<?= m_is_active('advanced_search', $page) ?>">
                    <span>✨</span>
                    <small>התאמות</small>
                </a>

                <a href="<?= APP_URL ?>/mobile/?page=messages" class="<?= m_is_active('messages', $page) ?>">
                    <span>💬</span>
                    <small>הודעות</small>
                </a>

                <a href="<?= APP_URL ?>/mobile/?page=profile&id=<?= $currentUserId ?>" class="<?= m_is_active('profile', $page) ?>">
                    <span>👤</span>
                    <small>פרופיל</small>
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/mobile/?page=login" class="<?= m_is_active('login', $page) ?>">
                    <span>🔐</span>
                    <small>כניסה</small>
                </a>

                <a href="<?= APP_URL ?>/mobile/?page=register" class="<?= m_is_active('register', $page) ?>">
                    <span>📝</span>
                    <small>הרשמה</small>
                </a>
            <?php endif; ?>
        </nav>

    </div>
</body>

</html>
