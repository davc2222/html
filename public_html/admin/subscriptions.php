<?php
// ===== FILE: admin/subscriptions.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

// אם יש אצלך קובץ אימות אדמין קיים, אפשר לטעון אותו כאן.
// require_once __DIR__ . '/../includes/admin_auth.php';

if (empty($_SESSION['csrf_subscriptions'])) {
    $_SESSION['csrf_subscriptions'] = bin2hex(random_bytes(32));
}

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusLabel(?string $status): string
{
    return match ($status) {
        'active'    => 'פעיל',
        'expired'   => 'פג תוקף',
        'frozen'    => 'מוקפא',
        'cancelled' => 'שוחרר',
        'pending'   => 'ממתין',
        default     => 'ללא מנוי',
    };
}

function statusClass(?string $status): string
{
    return match ($status) {
        'active'    => 'status-active',
        'expired'   => 'status-expired',
        'frozen'    => 'status-frozen',
        'cancelled' => 'status-cancelled',
        'pending'   => 'status-pending',
        default     => 'status-none',
    };
}

function daysText(?string $expiresAt): string
{
    if (!$expiresAt) {
        return '-';
    }

    $now = new DateTimeImmutable('now');
    $end = new DateTimeImmutable($expiresAt);

    $today = $now->setTime(0, 0, 0);
    $endDay = $end->setTime(0, 0, 0);

    $days = (int)$today->diff($endDay)->format('%r%a');

    if ($days > 0) {
        return $days . ' ימים';
    }

    if ($days === 0) {
        return 'היום';
    }

    return 'פג לפני ' . abs($days) . ' ימים';
}

function redirectBack(string $message = '', string $type = 'success'): never
{
    $params = [];
    if ($message !== '') {
        $params['msg'] = $message;
        $params['type'] = $type;
    }

    header('Location: subscriptions.php' . ($params ? '?' . http_build_query($params) : ''));
    exit;
}

function getSiteSetting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return ($value === false || $value === null) ? $default : (string)$value;
}

function saveSiteSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$key, $value]);
}

/* =========================================================
   פעולות
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');

    if (!hash_equals($_SESSION['csrf_subscriptions'], $csrf)) {
        http_response_code(403);
        exit('בקשה לא תקינה');
    }

    $action = (string)($_POST['action'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);
    $subscriptionId = (int)($_POST['subscription_id'] ?? 0);

    $siteAccessActions = [
        'save_site_range',
        'open_site_custom_days',
        'open_site_day',
        'open_site_week',
        'open_site_month',
        'close_site_now'
    ];

    if (in_array($action, $siteAccessActions, true)) {
        try {
            date_default_timezone_set('Asia/Jerusalem');

            if ($action === 'save_site_range') {
                $fromInput = trim((string)($_POST['site_open_from'] ?? ''));
                $untilInput = trim((string)($_POST['site_open_until'] ?? ''));

                $from = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $fromInput);
                $until = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $untilInput);

                if (!$from || !$until) {
                    throw new RuntimeException('אחד התאריכים אינו תקין.');
                }
                if ($until <= $from) {
                    throw new RuntimeException('מועד הסיום חייב להיות אחרי מועד ההתחלה.');
                }

                saveSiteSetting($pdo, 'site_open_from', $from->format('Y-m-d H:i:s'));
                saveSiteSetting($pdo, 'site_open_until', $until->format('Y-m-d H:i:s'));
                redirectBack('תקופת פתיחת האתר נשמרה.');
            }

            if ($action === 'open_site_custom_days') {
                $days = (int)($_POST['site_open_days'] ?? 0);
                if ($days < 1 || $days > 365) {
                    throw new RuntimeException('יש לבחור מספר ימים בין 1 ל־365.');
                }

                $now = new DateTimeImmutable('now');
                $until = $now->modify('+' . $days . ' days');

                saveSiteSetting($pdo, 'site_open_from', $now->format('Y-m-d H:i:s'));
                saveSiteSetting($pdo, 'site_open_until', $until->format('Y-m-d H:i:s'));
                redirectBack('האתר נפתח ל־' . $days . ' ימים.');
            }

            if (in_array($action, ['open_site_day','open_site_week','open_site_month'], true)) {
                $now = new DateTimeImmutable('now');

                if ($action === 'open_site_day') {
                    $until = $now->modify('+1 day');
                } elseif ($action === 'open_site_week') {
                    $until = $now->modify('+7 days');
                } else {
                    $until = $now->modify('+1 month');
                }

                saveSiteSetting($pdo, 'site_open_from', $now->format('Y-m-d H:i:s'));
                saveSiteSetting($pdo, 'site_open_until', $until->format('Y-m-d H:i:s'));
                redirectBack('האתר נפתח לכל המשתמשים לתקופה שנבחרה.');
            }

            if ($action === 'close_site_now') {
                saveSiteSetting($pdo, 'site_open_from', '2000-01-01 00:00:00');
                saveSiteSetting($pdo, 'site_open_until', '2000-01-01 00:00:00');
                redirectBack('האתר חזר למצב מנויים בלבד.');
            }
        } catch (Throwable $e) {
            redirectBack($e->getMessage(), 'error');
        }
    }

    if ($userId <= 0) {
        redirectBack('משתמש לא תקין', 'error');
    }

    try {
        $pdo->beginTransaction();

        if ($action === 'create') {
            // לא יוצרים מנוי נוסף אם כבר קיימת רשומה למשתמש.
            $check = $pdo->prepare("
                SELECT id
                FROM subscriptions
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $check->execute([$userId]);
            $existingId = (int)($check->fetchColumn() ?: 0);

            if ($existingId > 0) {
                throw new RuntimeException('כבר קיימת רשומת מנוי למשתמש');
            }

            $stmt = $pdo->prepare("
                INSERT INTO subscriptions
                    (user_id, plan, status, paid_at, expires_at)
                VALUES
                    (?, 'premium', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
            ");
            $stmt->execute([$userId]);

            $newSubscriptionId = (int)$pdo->lastInsertId();

            // פעולה ידנית של אדמין נרשמת בהיסטוריה עם סכום 0.
            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                    (user_id, subscription_id, amount, currency, provider, transaction_id, status, paid_at)
                VALUES
                    (?, ?, 0.00, 'ILS', 'admin', ?, 'paid', NOW())
            ");

            $transactionId = 'ADMIN-CREATE-' . date('YmdHis') . '-' . $newSubscriptionId;
            $paymentStmt->execute([$userId, $newSubscriptionId, $transactionId]);

            $pdo->commit();
            redirectBack('המנוי נוצר לחודש אחד');
        }

        if ($action === 'gift_month') {
            $check = $pdo->prepare("
                SELECT *
                FROM subscriptions
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $check->execute([$userId]);
            $subscription = $check->fetch(PDO::FETCH_ASSOC);

            if ($subscription) {
                $subscriptionId = (int)$subscription['id'];

                $stmt = $pdo->prepare("
                    UPDATE subscriptions
                    SET
                        plan = 'premium',
                        status = 'active',
                        expires_at = CASE
                            WHEN expires_at IS NULL OR expires_at < NOW()
                                THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
                            ELSE DATE_ADD(expires_at, INTERVAL 1 MONTH)
                        END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$subscriptionId]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO subscriptions
                        (user_id, plan, status, paid_at, expires_at)
                    VALUES
                        (?, 'premium', 'active', NULL, DATE_ADD(NOW(), INTERVAL 1 MONTH))
                ");
                $stmt->execute([$userId]);
                $subscriptionId = (int)$pdo->lastInsertId();
            }

            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                    (user_id, subscription_id, amount, currency, provider, transaction_id, status, paid_at)
                VALUES
                    (?, ?, 0.00, 'ILS', 'admin-gift', ?, 'paid', NOW())
            ");

            $transactionId = 'ADMIN-GIFT-' . date('YmdHis') . '-' . $subscriptionId;
            $paymentStmt->execute([$userId, $subscriptionId, $transactionId]);

            $pdo->commit();
            redirectBack('נוסף למשתמש חודש מתנה');
        }

        if ($subscriptionId <= 0) {
            throw new RuntimeException('מנוי לא תקין');
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM subscriptions
            WHERE id = ? AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$subscriptionId, $userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            throw new RuntimeException('המנוי לא נמצא');
        }

        if ($action === 'extend_month') {
            $stmt = $pdo->prepare("
                UPDATE subscriptions
                SET
                    plan = 'premium',
                    status = 'active',
                    expires_at = CASE
                        WHEN expires_at IS NULL OR expires_at < NOW()
                            THEN DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        ELSE DATE_ADD(expires_at, INTERVAL 1 MONTH)
                    END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$subscriptionId]);

            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                    (user_id, subscription_id, amount, currency, provider, transaction_id, status, paid_at)
                VALUES
                    (?, ?, 0.00, 'ILS', 'admin', ?, 'paid', NOW())
            ");

            $transactionId = 'ADMIN-EXT-' . date('YmdHis') . '-' . $subscriptionId;
            $paymentStmt->execute([$userId, $subscriptionId, $transactionId]);

            $pdo->commit();
            redirectBack('המנוי הוארך בחודש');
        }

        if ($action === 'release') {
            $stmt = $pdo->prepare("
                UPDATE subscriptions
                SET
                    status = 'cancelled',
                    expires_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$subscriptionId]);

            $pdo->commit();
            redirectBack('המנוי שוחרר');
        }

        throw new RuntimeException('פעולה לא מוכרת');

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('ADMIN SUBSCRIPTIONS ERROR: ' . $e->getMessage());
        redirectBack($e->getMessage(), 'error');
    }
}

/* =========================================================
   חיפוש וסינון
   ========================================================= */
$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));

$allowedStatuses = ['all', 'active', 'expired', 'frozen', 'cancelled', 'pending', 'none'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(up.Name LIKE :q OR up.Email LIKE :q OR CAST(up.Id AS CHAR) LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($status === 'none') {
    $where[] = "s.id IS NULL";
} elseif ($status !== 'all') {
    $where[] = "s.status = :status";
    $params[':status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        up.Id AS user_id,
        up.Name,
        up.Email,
        s.id AS subscription_id,
        s.status,
        s.paid_at,
        s.expires_at,
        s.created_at,
        s.updated_at
    FROM users_profile up
    LEFT JOIN subscriptions s
        ON s.id = (
            SELECT s2.id
            FROM subscriptions s2
            WHERE s2.user_id = up.Id
            ORDER BY s2.id DESC
            LIMIT 1
        )
    {$whereSql}
    ORDER BY
        CASE
            WHEN s.status = 'active' THEN 0
            WHEN s.status = 'expired' THEN 1
            WHEN s.status = 'frozen' THEN 2
            WHEN s.status = 'cancelled' THEN 3
            ELSE 4
        END,
        s.expires_at DESC,
        up.Id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   היסטוריית רכישות
   ========================================================= */
$historyUserId = (int)($_GET['history_user_id'] ?? 0);
$history = [];
$historyUser = null;

if ($historyUserId > 0) {
    $userStmt = $pdo->prepare("
        SELECT Id, Name, Email
        FROM users_profile
        WHERE Id = ?
        LIMIT 1
    ");
    $userStmt->execute([$historyUserId]);
    $historyUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($historyUser) {
        $histStmt = $pdo->prepare("
            SELECT
                p.id,
                p.amount,
                p.currency,
                p.provider,
                p.transaction_id,
                p.status,
                p.paid_at,
                p.created_at,
                s.expires_at
            FROM payments p
            LEFT JOIN subscriptions s ON s.id = p.subscription_id
            WHERE p.user_id = ?
            ORDER BY COALESCE(p.paid_at, p.created_at) DESC, p.id DESC
        ");
        $histStmt->execute([$historyUserId]);
        $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

date_default_timezone_set('Asia/Jerusalem');

$siteOpenFrom = getSiteSetting($pdo, 'site_open_from', '2000-01-01 00:00:00');
$siteOpenUntil = getSiteSetting($pdo, 'site_open_until', '2000-01-01 00:00:00');

$siteOpenFromTs = strtotime($siteOpenFrom) ?: 0;
$siteOpenUntilTs = strtotime($siteOpenUntil) ?: 0;
$siteIsOpenNow = time() >= $siteOpenFromTs && time() <= $siteOpenUntilTs;

$siteOpenFromInput = $siteOpenFrom !== '2000-01-01 00:00:00'
    ? date('Y-m-d\\TH:i', $siteOpenFromTs)
    : date('Y-m-d\\TH:i');

$siteOpenUntilInput = $siteOpenUntil !== '2000-01-01 00:00:00'
    ? date('Y-m-d\\TH:i', $siteOpenUntilTs)
    : date('Y-m-d\\TH:i', strtotime('+1 day'));

$msg = trim((string)($_GET['msg'] ?? ''));
$type = (string)($_GET['type'] ?? 'success');

?>


<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול מנויים | LoveMatch</title>

    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=2">

    <style>
.admin-subscriptions-page{
    direction:rtl;
    width:100%;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
    color:#263238;
}
.admin-subscriptions-main{
    min-width:0;
    width:100%;
}
.admin-subscriptions-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}
.admin-subscriptions-head h1{margin:0;font-size:28px;}
.admin-price-note{
    font-size:13px;
    color:#607d8b;
}
.admin-box{
    background:#fff;
    border:1px solid #e3e8ee;
    border-radius:12px;
    padding:16px;
    margin-bottom:18px;
}
.admin-filter-form{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.admin-filter-form input,
.admin-filter-form select{
    height:42px;
    border:1px solid #ccd5df;
    border-radius:8px;
    padding:0 12px;
    font-size:14px;
    background:#fff;
    box-sizing:border-box;
}
.admin-filter-form input{min-width:260px;flex:1;}
.admin-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:36px;
    border:0;
    border-radius:8px;
    padding:0 12px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    white-space:nowrap;
    box-sizing:border-box;
}
.admin-btn-search{background:#1769aa;color:#fff;}
.admin-btn-create{background:#e8f5e9;color:#1b5e20;}
.admin-btn-gift{background:#fff3e0;color:#b45309;}
.admin-btn-extend{background:#e3f2fd;color:#0d47a1;}
.admin-btn-release{background:#ffebee;color:#b71c1c;}
.admin-btn-history{background:#eef3f8;color:#37474f;}
.admin-btn-clear{background:#f1f3f5;color:#455a64;}
.admin-alert{
    padding:12px 15px;
    border-radius:9px;
    margin-bottom:16px;
    font-weight:700;
}
.admin-alert.success{background:#e8f5e9;color:#1b5e20;}
.admin-alert.error{background:#ffebee;color:#b71c1c;}
.admin-table-wrap{
    overflow:auto;
    background:#fff;
    border:1px solid #e3e8ee;
    border-radius:12px;
}
.admin-table{
    width:100%;
    border-collapse:collapse;
    min-width:1050px;
}
.admin-table th,
.admin-table td{
    padding:12px 10px;
    border-bottom:1px solid #edf1f4;
    text-align:right;
    vertical-align:middle;
    font-size:13px;
}
.admin-table th{
    background:#f7f9fb;
    font-size:12px;
    color:#546e7a;
}
.admin-table tr:last-child td{border-bottom:0;}
.status-badge{
    display:inline-block;
    padding:5px 9px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}
.status-active{background:#e8f5e9;color:#1b5e20;}
.status-expired{background:#fff3e0;color:#e65100;}
.status-frozen{background:#e3f2fd;color:#0d47a1;}
.status-cancelled{background:#eceff1;color:#455a64;}
.status-pending{background:#fff8e1;color:#8d6e00;}
.status-none{background:#f5f5f5;color:#757575;}
.actions-row{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.actions-row form{margin:0;}
.history-title{margin:0 0 4px;font-size:20px;}
.history-subtitle{margin:0 0 15px;color:#607d8b;font-size:13px;}
.empty-state{padding:22px;text-align:center;color:#78909c;}
.manual-payment{color:#607d8b;}
.days-positive{font-weight:700;color:#2e7d32;}
.days-expired{font-weight:700;color:#c62828;}

.site-open-box{background:#fff;border:1px solid #dbe4ee;border-radius:14px;padding:18px 20px;margin-bottom:18px;box-shadow:0 5px 16px rgba(15,23,42,.04)}
.site-open-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}
.site-open-title{font-size:19px;font-weight:900;color:#0f172a}
.site-open-status{padding:7px 12px;border-radius:999px;font-size:13px;font-weight:800;white-space:nowrap}
.site-open-status.open{background:#dcfce7;color:#166534}
.site-open-status.closed{background:#f1f5f9;color:#475569}
.site-open-range{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;margin-bottom:12px}
.site-open-field label{display:block;margin-bottom:5px;font-size:12px;font-weight:800;color:#475569}
.site-open-field input{width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;box-sizing:border-box}
.site-open-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.site-open-tools form{margin:0}
.site-open-days-form{display:flex;align-items:center;gap:8px}
.site-open-days-form input{width:84px;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 9px;box-sizing:border-box}
.site-open-btn{min-height:38px;border:0;border-radius:8px;padding:0 13px;font-weight:800;cursor:pointer}
.site-open-btn.save{background:#2563eb;color:#fff}
.site-open-btn.quick{background:#eef2ff;color:#3730a3}
.site-open-btn.custom{background:#ecfeff;color:#155e75}
.site-open-btn.close{background:#fee2e2;color:#991b1b}
.site-open-summary{margin-top:12px;font-size:12px;color:#64748b}

@media(max-width:800px){
    .admin-subscriptions-page{padding:0;}
    .admin-filter-form{flex-direction:column;}
    .admin-filter-form input,
    .admin-filter-form select,
    .admin-filter-form .admin-btn{width:100%;}
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

        <a href="/admin/index.php">
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

        <a href="/admin/subscriptions.php" class="active">
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
            <h1>ניהול מנויים</h1>
            <p>מנויים, הארכות, חודש מתנה והיסטוריית רכישות</p>
        </div>

        <a href="/" target="_blank" class="admin-view-site">
            צפייה באתר
        </a>
    </header>

    <div class="admin-subscriptions-page">
        <div class="admin-subscriptions-main">


        <div class="admin-subscriptions-head">
            <h1>ניהול מנויים</h1>
            <div class="admin-price-note">
                מנוי Premium אחד · 39.90 ₪ · חודש אחד בלבד · ללא חידוש אוטומטי
            </div>
        </div>

        <?php if ($msg !== ''): ?>
            <div class="admin-alert <?= $type === 'error' ? 'error' : 'success' ?>">
                <?= h($msg) ?>
            </div>
        <?php endif; ?>

        <div class="site-open-box">
            <div class="site-open-head">
                <div class="site-open-title">🌍 פתיחת האתר לכל המשתמשים</div>
                <div class="site-open-status <?= $siteIsOpenNow ? 'open' : 'closed' ?>">
                    <?php if ($siteIsOpenNow): ?>
                        🟢 פתוח עד <?= h(date('d/m/Y H:i', $siteOpenUntilTs)) ?>
                    <?php else: ?>
                        🔴 מצב מנויים בלבד
                    <?php endif; ?>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                <input type="hidden" name="action" value="save_site_range">

                <div class="site-open-range">
                    <div class="site-open-field">
                        <label>מתאריך</label>
                        <input type="datetime-local" name="site_open_from" value="<?= h($siteOpenFromInput) ?>" required>
                    </div>

                    <div class="site-open-field">
                        <label>עד תאריך</label>
                        <input type="datetime-local" name="site_open_until" value="<?= h($siteOpenUntilInput) ?>" required>
                    </div>

                    <button type="submit" class="site-open-btn save">שמור תקופה</button>
                </div>
            </form>

            <div class="site-open-tools">
                <form method="post" class="site-open-days-form">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                    <input type="hidden" name="action" value="open_site_custom_days">
                    <strong>מספר ימים:</strong>
                    <input type="number" name="site_open_days" min="1" max="365" value="7" required>
                    <button type="submit" class="site-open-btn custom">פתח למספר הימים</button>
                </form>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                    <input type="hidden" name="action" value="open_site_day">
                    <button type="submit" class="site-open-btn quick">24 שעות</button>
                </form>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                    <input type="hidden" name="action" value="open_site_week">
                    <button type="submit" class="site-open-btn quick">שבוע</button>
                </form>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                    <input type="hidden" name="action" value="open_site_month">
                    <button type="submit" class="site-open-btn quick">חודש</button>
                </form>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                    <input type="hidden" name="action" value="close_site_now">
                    <button type="submit" class="site-open-btn close" onclick="return confirm('להחזיר את האתר למצב מנויים בלבד?');">סגור עכשיו</button>
                </form>
            </div>

            <div class="site-open-summary">
                בתקופת הפתיחה כל משתמש מחובר יכול לקבל גישה לאזורים המוגבלים בלי לשנות את המנוי האישי שלו.
            </div>
        </div>

        <div class="admin-box">
            <form method="get" class="admin-filter-form">
                <input
                    type="text"
                    name="q"
                    value="<?= h($q) ?>"
                    placeholder="חיפוש לפי שם, אימייל או מספר משתמש">

                <select name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>כל הסטטוסים</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>פעיל</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>פג תוקף</option>
                    <option value="frozen" <?= $status === 'frozen' ? 'selected' : '' ?>>מוקפא</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>שוחרר</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>ממתין</option>
                    <option value="none" <?= $status === 'none' ? 'selected' : '' ?>>ללא מנוי</option>
                </select>

                <button type="submit" class="admin-btn admin-btn-search">חיפוש</button>
                <a href="subscriptions.php" class="admin-btn admin-btn-clear">נקה</a>
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>משתמש</th>
                    <th>אימייל</th>
                    <th>סטטוס</th>
                    <th>שולם בתאריך</th>
                    <th>מועד פקיעה</th>
                    <th>נותרו / עברו</th>
                    <th>פעולות</th>
                </tr>
                </thead>
                <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="empty-state">לא נמצאו משתמשים</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($rows as $row): ?>
                        <?php
                        $subscriptionId = (int)($row['subscription_id'] ?? 0);
                        $userId = (int)$row['user_id'];
                        $rowStatus = $row['status'] ?? null;
                        $days = daysText($row['expires_at'] ?? null);
                        $daysClass = ($row['expires_at'] && strtotime($row['expires_at']) >= time())
                            ? 'days-positive'
                            : (($row['expires_at']) ? 'days-expired' : '');
                        ?>
                        <tr>
                            <td>
                                <strong><?= h($row['Name']) ?></strong><br>
                                <small>#<?= $userId ?></small>
                            </td>

                            <td><?= h($row['Email']) ?></td>

                            <td>
                                <span class="status-badge <?= h(statusClass($rowStatus)) ?>">
                                    <?= h(statusLabel($rowStatus)) ?>
                                </span>
                            </td>

                            <td>
                                <?= !empty($row['paid_at'])
                                    ? h(date('d/m/Y H:i', strtotime($row['paid_at'])))
                                    : '-' ?>
                            </td>

                            <td>
                                <?= !empty($row['expires_at'])
                                    ? h(date('d/m/Y H:i', strtotime($row['expires_at'])))
                                    : '-' ?>
                            </td>

                            <td class="<?= h($daysClass) ?>">
                                <?= h($days) ?>
                            </td>

                            <td>
                                <div class="actions-row">

                                    <?php if (!$subscriptionId): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                                            <input type="hidden" name="action" value="create">
                                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                                            <button
                                                type="submit"
                                                class="admin-btn admin-btn-create"
                                                onclick="return confirm('ליצור למשתמש מנוי Premium לחודש אחד?');">
                                                צור מנוי
                                            </button>
                                        </form>
                                    <?php else: ?>

                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                                            <input type="hidden" name="action" value="extend_month">
                                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                                            <input type="hidden" name="subscription_id" value="<?= $subscriptionId ?>">
                                            <button
                                                type="submit"
                                                class="admin-btn admin-btn-extend"
                                                onclick="return confirm('להאריך את המנוי בחודש?');">
                                                הארך חודש
                                            </button>
                                        </form>

                                        <?php if ($rowStatus !== 'cancelled'): ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                                                <input type="hidden" name="action" value="release">
                                                <input type="hidden" name="user_id" value="<?= $userId ?>">
                                                <input type="hidden" name="subscription_id" value="<?= $subscriptionId ?>">
                                                <button
                                                    type="submit"
                                                    class="admin-btn admin-btn-release"
                                                    onclick="return confirm('לשחרר את המנוי? המשתמש יאבד גישה להודעות.');">
                                                    שחרר מנוי
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_subscriptions']) ?>">
                                        <input type="hidden" name="action" value="gift_month">
                                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                                        <input type="hidden" name="subscription_id" value="<?= $subscriptionId ?>">
                                        <button
                                            type="submit"
                                            class="admin-btn admin-btn-gift"
                                            onclick="return confirm('לתת למשתמש חודש Premium במתנה?');">
                                            🎁 חודש מתנה
                                        </button>
                                    </form>

                                    <a
                                        class="admin-btn admin-btn-history"
                                        href="subscriptions.php?history_user_id=<?= $userId ?>">
                                        היסטוריית רכישות
                                    </a>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <?php if ($historyUser): ?>
            <div class="admin-box" style="margin-top:20px;">
                <h2 class="history-title">היסטוריית רכישות — <?= h($historyUser['Name']) ?></h2>
                <p class="history-subtitle">
                    <?= h($historyUser['Email']) ?> · משתמש #<?= (int)$historyUser['Id'] ?>
                </p>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>תאריך</th>
                            <th>סכום</th>
                            <th>ספק</th>
                            <th>מספר עסקה</th>
                            <th>סטטוס</th>
                            <th>מועד פקיעת המנוי</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php if (!$history): ?>
                            <tr>
                                <td colspan="6" class="empty-state">אין היסטוריית רכישות למשתמש זה</td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($history as $payment): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $paymentDate = $payment['paid_at'] ?: $payment['created_at'];
                                        echo $paymentDate
                                            ? h(date('d/m/Y H:i', strtotime($paymentDate)))
                                            : '-';
                                        ?>
                                    </td>

                                    <td class="<?= (float)$payment['amount'] === 0.0 ? 'manual-payment' : '' ?>">
                                        <?= number_format((float)$payment['amount'], 2) ?>
                                        <?= h($payment['currency']) ?>
                                    </td>

                                    <td>
                                        <?php
                                        if ($payment['provider'] === 'admin-gift') {
                                            echo '🎁 חודש מתנה';
                                        } elseif ($payment['provider'] === 'admin') {
                                            echo 'פעולת אדמין';
                                        } else {
                                            echo h($payment['provider'] ?: '-');
                                        }
                                        ?>
                                    </td>

                                    <td><?= h($payment['transaction_id'] ?: '-') ?></td>
                                    <td><?= h($payment['status']) ?></td>

                                    <td>
                                        <?= !empty($payment['expires_at'])
                                            ? h(date('d/m/Y H:i', strtotime($payment['expires_at'])))
                                            : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    
        </div>
    </div>

</main>

</body>
</html>