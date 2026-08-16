<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';
 
function adminTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");

    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * בודק אם עמודה קיימת בטבלה.
 */
function adminColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");

    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * ספירה פשוטה מטבלה קיימת.
 */
function adminCount(PDO $pdo, string $table, string $where = '1=1'): int
{
    if (!adminTableExists($pdo, $table)) {
        return 0;
    }

    try {
        return (int) $pdo
            ->query("SELECT COUNT(*) FROM `$table` WHERE $where")
            ->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$totalUsers = adminCount($pdo, 'users_profile');

$totalPhotos = adminCount($pdo, 'user_pics');

$totalMessages = adminCount($pdo, 'messages');

$totalViews = adminCount($pdo, 'views');

$totalBlocked = adminCount($pdo, 'blocked_users');

$onlineUsers = 0;

if (
    adminTableExists($pdo, 'users_profile') &&
    adminColumnExists($pdo, 'users_profile', 'last_seen')
) {
    $onlineUsers = adminCount(
        $pdo,
        'users_profile',
        "last_seen >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
    );
} elseif (
    adminTableExists($pdo, 'users_profile') &&
    adminColumnExists($pdo, 'users_profile', 'last_activity')
) {
    $onlineUsers = adminCount(
        $pdo,
        'users_profile',
        "last_activity >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
    );
}

$newUsersToday = 0;

if (
    adminTableExists($pdo, 'users_profile') &&
    adminColumnExists($pdo, 'users_profile', 'created_at')
) {
    $newUsersToday = adminCount(
        $pdo,
        'users_profile',
        "DATE(created_at) = CURDATE()"
    );
}

$messagesToday = 0;

if (
    adminTableExists($pdo, 'messages') &&
    adminColumnExists($pdo, 'messages', 'created_at')
) {
    $messagesToday = adminCount(
        $pdo,
        'messages',
        "DATE(created_at) = CURDATE()"
    );
}

$recentUsers = [];

if (adminTableExists($pdo, 'users_profile')) {
    try {
        $columns = [];

        foreach (['id', 'name', 'email', 'gender', 'created_at'] as $column) {
            if (adminColumnExists($pdo, 'users_profile', $column)) {
                $columns[] = "`$column`";
            }
        }

        if ($columns !== []) {
            $orderColumn = adminColumnExists($pdo, 'users_profile', 'id')
                ? 'id'
                : $columns[0];

            $sql = "
                SELECT " . implode(', ', $columns) . "
                FROM users_profile
                ORDER BY `$orderColumn` DESC
                LIMIT 8
            ";

            $recentUsers = $pdo->query($sql)->fetchAll();
        }
    } catch (Throwable $e) {
        $recentUsers = [];
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>פאנל ניהול | LoveMatch</title>

    <link
        rel="stylesheet"
        href="/admin/assets/css/admin.css?v=2"
    >
</head>

<body class="admin-page">

<aside class="admin-sidebar">

    <div class="admin-sidebar-logo">
        <span>❤</span>

        <div>
            <strong>LoveMatch</strong>
            <small>ניהול האתר</small>
        </div>
    </div>

    <nav class="admin-menu">

        <a href="/admin/index.php" class="active">
            <span>▦</span>
            דשבורד
        </a>

        <a href="/admin/users.php">
            <span>♙</span>
            משתמשים
        </a>

        <a href="/admin/photos.php">
            <span>▣</span>
            תמונות
        </a>

        <a href="/admin/messages.php">
            <span>✉</span>
            הודעות
        </a>

        <a href="/admin/subscriptions.php">
    <span>💎</span>
    מנויים
</a>

        <a href="/admin/reports.php">
            <span>⚑</span>
            דיווחים
        </a>

        <a href="/admin/settings.php">
            <span>⚙</span>
            הגדרות
        </a>

    </nav>

    <a href="/admin/logout.php" class="admin-logout">
        יציאה
    </a>

</aside>

<main class="admin-main">

    <header class="admin-topbar">

        <div>
            <h1>דשבורד מנהל</h1>
            <p>סקירה כללית של LoveMatch</p>
        </div>

        <a
            href="/"
            target="_blank"
            class="admin-view-site"
        >
            צפייה באתר
        </a>

    </header>

    <section class="admin-stat-grid">

        <article class="admin-stat-card">
            <span class="admin-stat-icon">♙</span>

            <div>
                <small>משתמשים</small>
                <strong><?= number_format($totalUsers) ?></strong>
                <p><?= number_format($newUsersToday) ?> חדשים היום</p>
            </div>
        </article>

        <article class="admin-stat-card">
            <span class="admin-stat-icon">●</span>

            <div>
                <small>מחוברים עכשיו</small>
                <strong><?= number_format($onlineUsers) ?></strong>
                <p>פעילים בעשר הדקות האחרונות</p>
            </div>
        </article>

        <article class="admin-stat-card">
            <span class="admin-stat-icon">▣</span>

            <div>
                <small>תמונות</small>
                <strong><?= number_format($totalPhotos) ?></strong>
                <p>תמונות שהועלו לאתר</p>
            </div>
        </article>

        <article class="admin-stat-card">
            <span class="admin-stat-icon">✉</span>

            <div>
                <small>הודעות</small>
                <strong><?= number_format($totalMessages) ?></strong>
                <p><?= number_format($messagesToday) ?> נשלחו היום</p>
            </div>
        </article>

        <article class="admin-stat-card">
            <span class="admin-stat-icon">◉</span>

            <div>
                <small>צפיות בפרופילים</small>
                <strong><?= number_format($totalViews) ?></strong>
                <p>סה״כ צפיות שנרשמו</p>
            </div>
        </article>

        <article class="admin-stat-card">
            <span class="admin-stat-icon">⊘</span>

            <div>
                <small>חסימות</small>
                <strong><?= number_format($totalBlocked) ?></strong>
                <p>חסימות בין משתמשים</p>
            </div>
        </article>

    </section>

    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>
                <h2>משתמשים אחרונים</h2>
                <p>המשתמשים האחרונים שנוספו למערכת</p>
            </div>

            <a href="/admin/users.php" class="admin-panel-link">
                לכל המשתמשים
            </a>

        </div>

        <?php if ($recentUsers === []): ?>

            <div class="admin-empty">
                לא נמצאו נתוני משתמשים להצגה.
            </div>

        <?php else: ?>

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>
                    <tr>
                        <th>מזהה</th>
                        <th>שם</th>
                        <th>אימייל</th>
                        <th>מין</th>
                        <th>תאריך הצטרפות</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($recentUsers as $user): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    (string) ($user['id'] ?? '—'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) ($user['name'] ?? 'ללא שם'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($user['email'] ?? '—'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($user['gender'] ?? '—'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) ($user['created_at'] ?? '—'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>