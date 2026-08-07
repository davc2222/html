<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

const ADMIN_USERS_PER_PAGE = 12;

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminUserPhoto(?string $fileName): string
{
    $fileName = trim((string) $fileName);

    if ($fileName === '') {
        return '/admin/assets/img/user-placeholder.svg';
    }

    return '/admin/user_photo.php?name=' . rawurlencode($fileName);
}

function adminUsersQuery(array $changes = []): string
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

$totalPages = max(1, (int) ceil($totalUsers / ADMIN_USERS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * ADMIN_USERS_PER_PAGE;

$sql = "
    SELECT
        u.Id,
        u.Name,
        u.Email,
        u.Gender_Str,
        u.Age,
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

$stmt->bindValue(':limit', ADMIN_USERS_PER_PAGE, PDO::PARAM_INT);
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

    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=2">

    <style>
    /* Scoped styles: affect only this users page */
    .lm-users-filter-box {
        margin-bottom: 22px;
        padding: 18px;
        border: 1px solid #dfe6ef;
        border-radius: 14px;
        background: #fff;
    }

    .lm-users-filter-grid {
        display: grid;
        grid-template-columns: minmax(260px, 2fr) repeat(3, minmax(140px, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }

    .lm-users-filter-grid label {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
    }

    .lm-users-filter-grid input,
    .lm-users-filter-grid select {
        width: 100%;
        height: 42px;
        box-sizing: border-box;
        padding: 0 11px;
        border: 1px solid #d9e1eb;
        border-radius: 9px;
        background: #fff;
        font: inherit;
    }

    .lm-users-filter-actions {
        display: flex;
        gap: 8px;
    }

    .lm-users-filter-actions button,
    .lm-users-filter-actions a {
        min-height: 42px;
        padding: 0 14px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 800;
        white-space: nowrap;
    }

    .lm-users-filter-actions button {
        border: 0;
        background: #2563eb;
        color: #fff;
        cursor: pointer;
    }

    .lm-users-filter-actions a {
        border: 1px solid #d9e1eb;
        background: #fff;
        color: #334155;
    }

    .lm-users-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        width: 100%;
    }

    .lm-user-card {
        position: relative;
        min-width: 0;
        padding: 15px;
        border: 1px solid #dde5ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .05);
    }

    .lm-user-id {
        position: absolute;
        top: 16px;
        left: 16px;
        padding: 5px 9px;
        border-radius: 7px;
        background: #eef2f7;
        color: #475569;
        font-size: 13px;
        font-weight: 800;
    }

    .lm-user-main {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr);
        gap: 15px;
        align-items: center;
        min-height: 108px;
    }

    .lm-user-main img {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f1f5f9;
        background: #eef2f7;
    }

    .lm-user-main-text {
        min-width: 0;
    }

    .lm-user-main h2 {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: 18px;
    }

    .lm-user-main p {
        margin: 0 0 6px;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
    }

    .lm-user-main p span {
        padding: 0 4px;
    }

    .lm-user-main a {
        display: block;
        color: #64748b;
        font-size: 13px;
        text-decoration: none;
        overflow-wrap: anywhere;
    }

    .lm-user-badges {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
        margin: 15px 0;
    }

    .lm-user-badges span {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }

    .lm-user-badges .is-good {
        background: #dcfce7;
        color: #16803d;
    }

    .lm-user-badges .is-muted {
        background: #e2e8f0;
        color: #475569;
    }

    .lm-user-badges .is-danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .lm-user-stats {
        display: grid;
        grid-template-columns: .7fr 1.3fr 1fr;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
    }

    .lm-user-stats > div {
        min-width: 0;
        padding: 0 5px;
        text-align: center;
        border-left: 1px solid #e5e7eb;
    }

    .lm-user-stats > div:last-child {
        border-left: 0;
    }

    .lm-user-stats small {
        display: block;
        min-height: 29px;
        margin-bottom: 5px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .lm-user-stats strong {
        display: block;
        color: #0f172a;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .lm-user-view-btn {
        width: 100%;
        min-height: 43px;
        margin-top: 17px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        background: #2563eb;
        color: #fff;
        text-decoration: none;
        font-weight: 900;
    }

    .lm-user-view-btn:hover {
        background: #1d4ed8;
    }

    .lm-users-empty {
        padding: 36px;
        border: 1px solid #dde5ef;
        border-radius: 14px;
        background: #fff;
        text-align: center;
        color: #64748b;
    }

    .lm-users-pagination {
        display: flex;
        justify-content: center;
        gap: 7px;
        margin-top: 24px;
        padding: 15px;
        border: 1px solid #dde5ef;
        border-radius: 13px;
        background: #fff;
    }

    .lm-users-pagination a {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #334155;
        text-decoration: none;
        font-weight: 800;
    }

    .lm-users-pagination a.active {
        background: #2563eb;
        color: #fff;
    }

    @media (max-width: 1450px) {
        .lm-users-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 1180px) {
        .lm-users-filter-grid {
            grid-template-columns: repeat(3, minmax(150px, 1fr));
        }

        .lm-users-search {
            grid-column: span 2;
        }

        .lm-users-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .lm-users-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .lm-users-filter-grid,
        .lm-users-grid {
            grid-template-columns: 1fr;
        }

        .lm-users-search {
            grid-column: auto;
        }
    }

    @media (max-width: 520px) {
        .lm-user-card {
            padding: 16px;
        }

        .lm-user-main {
            grid-template-columns: 78px minmax(0, 1fr);
        }

        .lm-user-main img {
            width: 78px;
            height: 78px;
        }

        .lm-user-stats {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .lm-user-stats > div {
            padding: 0;
            border-left: 0;
            text-align: right;
        }

        .lm-user-stats small {
            min-height: 0;
        }
    }
    </style>

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

    <section class="lm-users-filter-box">
        <form method="get" class="lm-users-filter-grid">
            <div class="lm-users-search">
                <label for="q">חיפוש</label>
                <input
                    type="search"
                    id="q"
                    name="q"
                    value="<?= h($q) ?>"
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
                <label for="verified">אימות אימייל</label>
                <select id="verified" name="verified">
                    <option value="">הכול</option>
                    <option value="yes" <?= $verified === 'yes' ? 'selected' : '' ?>>מאומת</option>
                    <option value="no" <?= $verified === 'no' ? 'selected' : '' ?>>לא מאומת</option>
                </select>
            </div>

            <div>
                <label for="status">מצב חשבון</label>
                <select id="status" name="status">
                    <option value="">הכול</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>פעיל</option>
                    <option value="frozen" <?= $status === 'frozen' ? 'selected' : '' ?>>קפוא</option>
                </select>
            </div>

            <div class="lm-users-filter-actions">
                <button type="submit">חיפוש</button>
                <a href="/admin/users.php">נקה</a>
            </div>
        </form>
    </section>

    <?php if ($users === []): ?>
        <section class="lm-users-empty">
            לא נמצאו משתמשים התואמים לחיפוש.
        </section>
    <?php else: ?>
        <section class="lm-users-grid">
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

                <article class="lm-user-card">
                    <span class="lm-user-id">#<?= (int) $user['Id'] ?></span>

                    <div class="lm-user-main">
                        <img
                            src="<?= h(adminUserPhoto($user['main_photo'] ?? null)) ?>"
                            alt="<?= h($user['Name'] ?? 'משתמש') ?>"
                            onerror="this.onerror=null;this.src='/admin/assets/img/user-placeholder.svg';"
                        >

                        <div class="lm-user-main-text">
                            <h2><?= h($user['Name'] ?: 'ללא שם') ?></h2>

                            <p>
                                <?= h($user['Gender_Str'] ?: '—') ?>
                                <?php if ($place !== ''): ?>
                                    <span>•</span>
                                    <?= h($place) ?>
                                <?php endif; ?>
                            </p>

                            <a href="mailto:<?= h($user['Email'] ?? '') ?>">
                                <?= h($user['Email'] ?: 'ללא אימייל') ?>
                            </a>
                        </div>
                    </div>

                    <div class="lm-user-badges">
                        <span class="<?= (int) $user['email_verified'] === 1 ? 'is-good' : 'is-muted' ?>">
                            <?= (int) $user['email_verified'] === 1 ? 'אימייל מאומת' : 'לא מאומת' ?>
                        </span>

                        <span class="<?= (int) $user['Is_Frozen'] === 1 ? 'is-danger' : 'is-good' ?>">
                            <?= (int) $user['Is_Frozen'] === 1 ? 'קפוא' : 'פעיל' ?>
                        </span>
                    </div>

                    <div class="lm-user-stats">
                        <div>
                            <small>תמונות</small>
                            <strong><?= number_format((int) $user['photo_count']) ?></strong>
                        </div>

                        <div>
                            <small>התחברות אחרונה</small>
                            <strong><?= h($lastSeen !== '' ? $lastSeen : '—') ?></strong>
                        </div>

                        <div>
                            <small>תאריך הרשמה</small>
                            <strong><?= h($user['Open_Date'] ?: '—') ?></strong>
                        </div>
                    </div>

                    <a
                        href="/admin/user_view.php?id=<?= (int) $user['Id'] ?>"
                        class="lm-user-view-btn"
                    >
                        צפייה בפרופיל
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="lm-users-pagination" aria-label="עמודים">
                <?php if ($page > 1): ?>
                    <a href="<?= h(adminUsersQuery(['page' => $page - 1])) ?>">‹</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a
                        href="<?= h(adminUsersQuery(['page' => $i])) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= h(adminUsersQuery(['page' => $page + 1])) ?>">›</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>