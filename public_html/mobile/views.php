<?php
// ===== FILE: mobile/views.php =====

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/profile_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /mobile/?page=login');
    exit;
}

if (!empty($_SESSION['user_id'])) {
    try {
        $stmtPresence = $pdo->prepare("
            UPDATE users_profile
            SET last_seen = NOW()
            WHERE Id = :id
            LIMIT 1
        ");
        $stmtPresence->execute([':id' => (int)$_SESSION['user_id']]);
    } catch (Throwable $e) {
        // לא להפיל דף בגלל נוכחות
    }
}

$session_user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        up.*,
        MAX(v.Date) AS last_view_date,

        SUM(
            CASE
                WHEN v.Id = :id
                 AND v.`New` = 1
                 AND (v.Deleted_By_Id = 0 OR v.Deleted_By_Id IS NULL)
                THEN 1
                ELSE 0
            END
        ) AS new_views_count,

        EXISTS (
            SELECT 1
            FROM views v2
            WHERE v2.Id = up.Id
              AND v2.ById = :id
              AND (v2.Deleted_By_ById = 0 OR v2.Deleted_By_ById IS NULL)
        ) AS is_mutual

    FROM views v
    JOIN users_profile up
        ON up.Id = v.ById

    WHERE v.Id = :id
      AND up.Is_Frozen = 0
      AND v.ById <> :id
      AND (v.Deleted_By_Id = 0 OR v.Deleted_By_Id IS NULL)

      AND NOT EXISTS (
            SELECT 1
            FROM blocked_users bu
            WHERE (bu.Id = up.Id AND bu.Blocked_ById = :id)
               OR (bu.Id = :id AND bu.Blocked_ById = up.Id)
      )

    GROUP BY up.Id
    ORDER BY last_view_date DESC
");

$stmt->execute([':id' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* רק אחרי השליפה - סימון צפיות כנקראו */
$pdo->prepare("
    UPDATE views
    SET `New` = 0
    WHERE Id = :id AND `New` = 1
")->execute([':id' => $session_user_id]);
?>
<style>
    .mobile-views-page {
        padding: 14px;
        padding-bottom: 90px;
    }

    .mobile-views-title {
        margin: 0 0 14px;
        font-size: 24px;
        font-weight: 800;
        color: #222;
        text-align: right;
    }

    .mobile-views-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .view-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 16px;
        padding: 12px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    }

    .view-card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
    }

    .view-card-img-wrap {
        position: relative;
        flex: 0 0 auto;
    }

    .view-card-img {
        width: 74px;
        height: 74px;
        object-fit: cover;
        border-radius: 14px;
        display: block;
        background: #f5f5f5;
    }

    .view-card-info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 6px;
    }

    .view-card-name {
        font-size: 17px;
        font-weight: 700;
        color: #222;
        line-height: 1.3;
        word-break: break-word;
    }

    .view-card-date {
        font-size: 13px;
        color: #777;
    }

    .no-results {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 16px;
        padding: 18px;
        text-align: center;
        color: #666;
    }
</style>

<main class="mobile-views-page">
    <h2 class="mobile-views-title">צפיות</h2>

    <?php if (!$results): ?>
        <div class="no-results">אין צפיות עדיין</div>
    <?php else: ?>

        <div class="mobile-views-list">

            <?php foreach ($results as $user): ?>
                <?php
                $age = '';
                if (!empty($user['DOB'])) {
                    try {
                        $age = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                        $age = '';
                    }
                }

                $img = getMainProfileImage($pdo, (int)$user['Id']);

                $lastViewText = '';
                if (!empty($user['last_view_date'])) {
                    try {
                        $dt = new DateTime((string)$user['last_view_date']);
                        $lastViewText = $dt->format('d/m/Y H:i');
                    } catch (Throwable $e) {
                        $lastViewText = '';
                    }
                }
                ?>

                <div class="view-card">
                    <a href="/mobile/?page=profile&id=<?= (int)$user['Id'] ?>" class="view-card-link">
                        <div class="view-card-img-wrap">
                            <img
                                src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                                class="view-card-img"
                                alt="<?= htmlspecialchars((string)($user['Name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="view-card-info">
                            <div class="view-card-name">
                                <?= htmlspecialchars((string)($user['Name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= $age !== '' ? ', ' . (int)$age : '' ?>
                            </div>

                            <?php if ($lastViewText !== ''): ?>
                                <div class="view-card-date">צפה/תה לאחרונה: <?= htmlspecialchars($lastViewText, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>
</main>
