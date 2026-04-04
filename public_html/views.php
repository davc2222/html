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

        if ($picName) {
            return '/uploads/' . ltrim((string)$picName, '/');
        }
    } catch (Throwable $e) {
        // ignore
    }

    return '/images/no_photo.jpg';
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
    JOIN users_profile up ON up.Id = v.ById
    WHERE v.Id = :id
      AND (v.Deleted_By_Id = 0 OR v.Deleted_By_Id IS NULL)
    GROUP BY up.Id
    ORDER BY last_view_date DESC
");

$stmt->execute([':id' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-shell">

    <h2 class="views-page-title">מי צפה בי</h2>

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