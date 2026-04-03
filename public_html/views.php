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

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* סימון כנקראו */
$pdo->prepare("
    UPDATE views
    SET `New` = 0
    WHERE Id = :id AND `New` = 1
")->execute([':id' => $session_user_id]);

/* שליפה */
$stmt = $pdo->prepare("
    SELECT up.*, MAX(v.Date) AS last_view_date
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
                    $age = date_diff(date_create($row['DOB']), date_create('today'))->y;
                }

                $zone        = trim((string)($row['Zone_Str'] ?? ''));
                $place       = trim((string)($row['Place_Str'] ?? ''));
                $family      = trim((string)($row['Family_Status_Str'] ?? ''));
                $children    = trim((string)($row['Childs_Num_Str'] ?? ''));
                $religion    = trim((string)($row['Religion_Str'] ?? ''));
                $religionRef = trim((string)($row['Religion_Ref_Str'] ?? ''));
                $height      = trim((string)($row['Height_Str'] ?? ''));

                $img = '/images/no_photo.jpg';

                $time = '';
                if (!empty($row['last_view_date'])) {
                    $time = date('d/m/Y H:i', strtotime($row['last_view_date']));
                }
                ?>

                <div class="view-card">

                    <div class="view-card-media">
                        <img
                            class="view-card-image"
                            src="<?= h($img) ?>"
                            alt="<?= h($name) ?>"
                        >

                        <?php if ($time): ?>
                            <div class="view-card-time"><?= h($time) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= h($name) ?>
                            <?= $age !== '' ? ', ' . h((string)$age) : '' ?>
                        </div>

                        <div class="view-card-divider"></div>

                        <div class="view-card-details">
                            <?php if ($zone !== ''): ?><?= h($zone) ?><?php endif; ?>
                            <?php if ($place !== ''): ?> | <?= h($place) ?><?php endif; ?>
                            <?php if ($family !== ''): ?> | <?= h($family) ?><?php endif; ?>
                            <?php if ($children !== ''): ?> | <?= h($children) ?><?php endif; ?>
                            <?php if ($religion !== ''): ?> | <?= h($religion) ?><?php endif; ?>
                            <?php if ($religionRef !== ''): ?> | <?= h($religionRef) ?><?php endif; ?>
                            <?php if ($height !== ''): ?> | <?= h($height) ?><?php endif; ?>
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