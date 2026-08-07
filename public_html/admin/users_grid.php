<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

const USERS_PER_PAGE = 12;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function photoUrl(?string $fileName): string
{
    $fileName = trim((string) $fileName);

    if ($fileName === '') {
        return '/admin/assets/img/user-placeholder.svg';
    }

    return '/admin/user_photo.php?name=' . rawurlencode($fileName);
}

function buildQuery(array $changes = []): string
{
    $params = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return '?' . http_build_query($params);
}

$q = trim((string) ($_GET['q'] ?? ''));
$gender = trim((string) ($_GET['gender'] ?? ''));
$verified = trim((string) ($_GET['verified'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(
        CAST(u.Id AS CHAR) LIKE :q
        OR u.Name LIKE :q
        OR u.Email LIKE :q
        OR u.Place_Str LIKE :q
    )';
    $params['q'] = '%' . $q . '%';
}

if ($gender !== '') {
    $where[] = 'u.Gender_Id = :gender';
    $params['gender'] = (int) $gender;
}

if ($verified === 'yes') {
    $where[] = 'u.email_verified = 1';
} elseif ($verified === 'no') {
    $where[] = 'u.email_verified = 0';
}

if ($status === 'active') {
    $where[] = 'u.Is_Frozen = 0';
} elseif ($status === 'frozen') {
    $where[] = 'u.Is_Frozen = 1';
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users_profile u
    WHERE $whereSql
");
$countStmt->execute($params);
$totalUsers = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalUsers / USERS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * USERS_PER_PAGE;

$sql = "
    SELECT
        u.Id,
        u.Name,
        u.Email,
        u.Gender_Str,
        u.Age,
        u.DOB,
        u.Place_Str,
        u.Zone_Str,
        u.Open_Date,
        u.last_seen,
        u.Login_Date,
        u.Login_Time,
        u.email_verified,
        u.Is_Frozen,
        (
            SELECT p.Pic_Name
            FROM user_pics p
            WHERE p.Id = u.Id
            ORDER BY p.Main_Pic DESC, p.Pic_Num ASC
            LIMIT 1
        ) AS main_photo,
        (
            SELECT COUNT(*)
            FROM user_pics p2
            WHERE p2.Id = u.Id
        ) AS photo_count
    FROM users_profile u
    WHERE $whereSql
    ORDER BY u.Id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(
        ':' . $key,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}

$stmt->bindValue(':limit', USERS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול משתמשים | LoveMatch</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=5">
    <link rel="stylesheet" href="/admin/assets/css/admin-users-grid.css?v=1">
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
        <a href="/admin/index.php"><span>▦</span>דשבורד</a>
        <a href="/admin/users.php" class="active"><span>♙</span>משתמשים</a>
        <a href="/admin/photos.php"><span>▣</span>תמונות</a>
        <a href="/admin/messages.php"><span>✉</span>הודעות</a>
        <a href="/admin/reports.php"><span>⚑</span>דיווחים</a>
        <a href="/admin/settings.php"><span>⚙</span>הגדרות</a>
    </nav>

    <a href="/admin/logout.php" class="admin-logout">יציאה</a>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <div>
            <h1>ניהול משתמשים</h1>
            <p><?= number_format($totalUsers) ?> משתמשים נמצאו</p>
        </div>

        <a href="/admin/index.php" class="admin-view-site">חזרה לדשבורד</a>
    </header>

    <section class="admin-panel users-filter-panel">
        <form method="get" class="users-filter-grid">
            <div class="users-filter-search">
                <label for="q">חיפוש</label>
                <input
                    type="search"
                    id="q"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="מספר, שם, אימייל או עיר"
                >
            </div>

            <div>
                <label for="gender">מין</label>
                <select id="gender" name="gender">
                    <option value="">הכול</option>
                    <option value="1" <?= $gender === '1' ? 'selected' : '' ?>>גבר</option>
                    <option value="2" <?= $gender === '2' ? 'selected' : '' ?>>אישה</option>
                </select>
            </div>

            <div>
                <label for="verified">אימייל מאומת</label>
                <select id="verified" name="verified">
                    <option value="">הכול</option>
                    <option value="yes" <?= $verified === 'yes' ? 'selected' : '' ?>>מאומת</option>
                    <option value="no" <?= $verified === 'no' ? 'selected' : '' ?>>לא מאומת</option>
                </select>
            </div>

            <div>
                <label for="status">סטטוס חשבון</label>
                <select id="status" name="status">
                    <option value="">הכול</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>פעיל</option>
                    <option value="frozen" <?= $status === 'frozen' ? 'selected' : '' ?>>קפוא</option>
                </select>
            </div>

            <div class="users-filter-actions">
                <button type="submit">חיפוש</button>
                <a href="/admin/users.php">נקה</a>
            </div>
        </form>
    </section>

    <?php if ($users === []): ?>
        <section class="admin-panel admin-empty">
            לא נמצאו משתמשים התואמים לחיפוש.
        </section>
    <?php else: ?>
        <section class="users-card-grid">
            <?php foreach ($users as $user): ?>
                <?php
                $lastSeen = trim((string) ($user['last_seen'] ?? ''));

                if ($lastSeen === '' && !empty($user['Login_Date'])) {
                    $lastSeen = trim(
                        (string) $user['Login_Date']
                        . ' '
                        . (string) ($user['Login_Time'] ?? '')
                    );
                }

                $place = trim((string) ($user['Place_Str'] ?? ''));

                if ($place === '') {
                    $place = trim((string) ($user['Zone_Str'] ?? ''));
                }
                ?>
                <article class="user-card">
                    <span class="user-card-id">#<?= (int) $user['Id'] ?></span>

                    <div class="user-card-head">
                        <img
                            src="<?= e(photoUrl($user['main_photo'] ?? null)) ?>"
                            alt="<?= e($user['Name'] ?? 'משתמש') ?>"
                            onerror="this.onerror=null;this.src='/admin/assets/img/user-placeholder.svg';"
                        >

                        <div>
                            <h2><?= e($user['Name'] ?: 'ללא שם') ?></h2>

                            <p class="user-card-mainline">
                                <?= e($user['Gender_Str'] ?: '—') ?>
                                <?php if ($place !== ''): ?>
                                    <span>•</span>
                                    <?= e($place) ?>
                                <?php endif; ?>
                            </p>

                            <a class="user-card-email" href="mailto:<?= e($user['Email'] ?? '') ?>">
                                <?= e($user['Email'] ?: 'ללא אימייל') ?>
                            </a>
                        </div>
                    </div>

                    <div class="user-card-badges">
                        <span class="user-badge <?= (int) $user['email_verified'] === 1 ? 'ok' : 'muted' ?>">
                            <?= (int) $user['email_verified'] === 1 ? 'אימייל מאומת' : 'לא מאומת' ?>
                        </span>

                        <span class="user-badge <?= (int) $user['Is_Frozen'] === 1 ? 'danger' : 'ok' ?>">
                            <?= (int) $user['Is_Frozen'] === 1 ? 'קפוא' : 'פעיל' ?>
                        </span>
                    </div>

                    <div class="user-card-stats">
                        <div>
                            <span>תמונות</span>
                            <strong><?= number_format((int) $user['photo_count']) ?></strong>
                        </div>

                        <div>
                            <span>התחברות אחרונה</span>
                            <strong><?= e($lastSeen !== '' ? $lastSeen : '—') ?></strong>
                        </div>

                        <div>
                            <span>תאריך הרשמה</span>
                            <strong><?= e($user['Open_Date'] ?: '—') ?></strong>
                        </div>
                    </div>

                    <a
                        href="/admin/user_view.php?id=<?= (int) $user['Id'] ?>"
                        class="user-card-button"
                    >
                        צפייה בפרופיל
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="users-pagination" aria-label="עמודים">
                <?php if ($page > 1): ?>
                    <a href="<?= e(buildQuery(['page' => $page - 1])) ?>">‹</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a
                        href="<?= e(buildQuery(['page' => $i])) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= e(buildQuery(['page' => $page + 1])) ?>">›</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>