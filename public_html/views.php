<?php
// ===== FILE: views.php =====

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
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

/* סימון צפיות כנקראו רק בכניסה לדף צפיות */
$pdo->prepare("
    UPDATE views
    SET `New` = 0
    WHERE Id = :id AND `New` = 1
")->execute([':id' => $session_user_id]);

/*
   שליפה + הדדיות באותה שאילתה
   v  = מי שצפה בי
   vm = האם גם אני צפיתי בו
*/
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
?>

<main class="page-shell">
    <div class="views-layout">

        <aside class="views-sidebar">
            <h2 class="views-sidebar-title">צפיות</h2>

            <a href="/?page=views" class="views-sidebar-link is-active">
                <span class="views-sidebar-icon">↩👤</span>
                <span>צפו בפרופיל שלי</span>
            </a>

            <a href="/?page=viewed_by_me" class="views-sidebar-link">
                <span class="views-sidebar-icon">↪👤</span>
                <span>פרופילים שצפיתי</span>
            </a>

            <a href="/?page=blocked_users" class="views-sidebar-link">
                <span class="views-sidebar-icon">⊘</span>
                <span>פרופילים שחסמתי</span>
            </a>
        </aside>

        <section class="search-container views-main-content">

            <h2 class="views-page-title">צפיות</h2>

            <?php if (!$results): ?>
                <div class="no-results">אין צפיות עדיין</div>
            <?php else: ?>

                <div class="results">

                    <?php foreach ($results as $row): ?>
                        <?php
                        $user = $row;

                        $user['Age'] = '';
                        if (!empty($user['DOB'])) {
                            try {
                                $user['Age'] = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                            } catch (Throwable $e) {
                                $user['Age'] = '';
                            }
                        }

                        $newViews = (int)($row['new_views_count'] ?? 0);

                        $cardId = '';
                        $cardTopBadge = $newViews > 0 ? '👁 ' . $newViews . ' חדשות' : '';
                        $cardSubline = '';
                        $cardShowOnline = true;

                        $cardIconsHtml = '
                        <div style="display:flex;justify-content:center;gap:10px;width:100%;">
                            <span title="צפייה נכנסת">↙️ 👁️</span>
                            <span title="צפייה יוצאת">↗️ 👁️</span>
                            <span title="הודעה נכנסת">↙️ 💬</span>
                            <span title="הודעה יוצאת">↗️ 💬</span>
                        </div>';

                        $cardActionsHtml = '<a href="/?page=profile&id=' . (int)$user['Id'] . '" class="view-card-profile-link">צפייה בפרופיל</a>';

                        $user['Image'] = getMainProfileImage($pdo, (int)$user['Id']);
                        $user['is_online'] = is_user_online($pdo, (int)$user['Id']);

                        include __DIR__ . '/includes/view_card.php';
                        ?>
                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>
    </div>
</main>