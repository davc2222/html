<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php
// ===== FILE: views.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

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

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function get_profile_image(PDO $pdo, int $userId): string {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $picName = $stmt->fetchColumn();

        if (!$picName) {
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id
                  AND Pic_Status = 1
                ORDER BY Main_Pic DESC, Pic_Num ASC
                LIMIT 1
            ");
            $stmt->execute([':id' => $userId]);
            $picName = $stmt->fetchColumn();
        }

        if ($picName) {
            return '/uploads/' . ltrim((string)$picName, '/');
        }
    } catch (Throwable $e) {
        // ממשיכים ל-fallback לפי מין
    }

    try {
        $stmt = $pdo->prepare("
            SELECT Gender_Str
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $genderValue = trim((string)$stmt->fetchColumn());

        if ($genderValue === 'אישה') {
            return '/images/default_female.svg';
        }
    } catch (Throwable $e) {
        // אם גם זה נכשל, נופלים לברירת המחדל
    }

    return '/images/default_male.svg';
}

function is_user_online(PDO $pdo, int $userId): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT CASE
                WHEN last_seen IS NOT NULL
                 AND last_seen >= (NOW() - INTERVAL 120 SECOND)
                THEN 1
                ELSE 0
            END
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/* סימון צפיות כנקראו רק בכניסה לדף צפיות */
$pdo->prepare("
    UPDATE views
    SET `New` = 0
    WHERE Id = :id AND `New` = 1
")->execute([':id' => $session_user_id]);

/* שליפה */
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
        ) AS new_views_count
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

<div class="page-shell views-page-shell">

    <div class="views-layout">

        <div class="views-main-col">
            <h2 class="views-page-title">צפיות</h2>

            <?php if (!$results): ?>
                <div class="no-views-box">אין צפיות עדיין</div>
            <?php else: ?>

                <div class="views-list">

                    <?php foreach ($results as $row): ?>

                        <?php
                        $id   = (int)($row['Id'] ?? 0);
                        $name = trim((string)($row['Name'] ?? ''));

                        $age = '';
                        if (!empty($row['DOB'])) {
                            try {
                                $age = date_diff(date_create($row['DOB']), date_create('today'))->y;
                            } catch (Throwable $e) {
                                $age = '';
                            }
                        }

                        $zone     = trim((string)($row['Zone_Str'] ?? ''));
                        $place    = trim((string)($row['Place_Str'] ?? ''));
                        $family   = trim((string)($row['Family_Status_Str'] ?? ''));
                        $children = trim((string)($row['Childs_Num_Str'] ?? ''));
                        $height   = trim((string)($row['Height_Str'] ?? ''));
                        $smoking  = trim((string)($row['Smoking_Habbit_Str'] ?? ''));
                        $newViews = (int)($row['new_views_count'] ?? 0);

                        $img = get_profile_image($pdo, $id);
                        $isOnline = is_user_online($pdo, $id);
                        ?>

                        <div class="view-card">

                            <?php if ($newViews > 0): ?>
                                <div class="view-card-top-badge">
                                    👁 <?= $newViews ?> חדשות
                                </div>
                            <?php endif; ?>

                            <div class="view-card-media">
                                <img
                                    class="view-card-image"
                                    src="<?= h($img) ?>"
                                    alt="<?= h($name) ?>">

                                <?php if ($isOnline): ?>
                                    <span class="online-badge" title="מחובר כעת"></span>
                                <?php endif; ?>
                            </div>

                            <div class="view-card-content">

                                <div class="view-card-name">
                                    <?= h($name) ?>
                                    <?= $age !== '' ? ', ' . h((string)$age) : '' ?>
                                </div>

                                <div class="view-card-divider"></div>

                                <div class="view-card-details">

                                    <?php if ($family !== ''): ?>
                                        <div>מצב משפחתי: <?= h($family) ?></div>
                                    <?php endif; ?>

                                    <div>
                                        ילדים:
                                        <?= ($children === '' || $children === '0') ? 'ללא' : h($children) . '+' ?>
                                    </div>

                                    <?php if ($zone !== '' || $place !== ''): ?>
                                        <div>
                                            <?php if ($zone !== ''): ?>
                                                אזור: <?= h($zone) ?>
                                            <?php endif; ?>

                                            <?php if ($zone !== '' && $place !== ''): ?>
                                                |
                                            <?php endif; ?>

                                            <?php if ($place !== ''): ?>
                                                מקום: <?= h($place) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($height !== ''): ?>
                                        <div>גובה: <?= h($height) ?></div>
                                    <?php endif; ?>

                                    <?php if ($smoking !== ''): ?>
                                        <div>עישון: <?= h($smoking) ?></div>
                                    <?php endif; ?>

                                </div>

                                <a class="view-card-link" href="/?page=profile&id=<?= $id ?>">
                                    צפייה בפרופיל
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </div>

        <aside class="views-side-col">
            <div class="views-side-card">
                <h3 class="views-side-title">צפיות</h3>

                <nav class="views-side-nav">
                    <a href="/?page=views" class="views-side-link is-active">
                        <span class="views-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </span>
                        <span class="views-side-text">צפו בפרופיל שלי</span>
                    </a>

                    <a href="/?page=views_by_me" class="views-side-link">
                        <span class="views-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5a7 7 0 1 0 0 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                <path d="M9 12h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                <path d="m17 8 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="views-side-text">פרופילים שצפיתי</span>
                    </a>

                    <a href="/?page=blocked_users" class="views-side-link">
                        <span class="views-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
                                <path d="M8 8l8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        <span class="views-side-text">פרופילים שחסמתי</span>
                    </a>
                </nav>
            </div>
        </aside>

    </div>

</div>