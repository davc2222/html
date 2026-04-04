<?php
// ===== FILE: messages.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$me = (int)$_SESSION['user_id'];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ===== שליפה ===== */
$stmt = $pdo->prepare("
    SELECT 
        up.*,
        MAX(m.Date_Sent) AS last_msg_date,
        SUM(CASE WHEN m.Id = :me AND m.`New` = 1 THEN 1 ELSE 0 END) AS unread_count,
        CASE 
            WHEN m.ById = :me THEN m.Id
            ELSE m.ById
        END AS other_user_id
    FROM messages m
    JOIN users_profile up 
        ON up.Id = CASE 
            WHEN m.ById = :me THEN m.Id
            ELSE m.ById
        END
    WHERE (m.Id = :me OR m.ById = :me)
    GROUP BY other_user_id
    ORDER BY last_msg_date DESC
");

$stmt->execute([':me' => $me]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-shell">

    <h2 class="views-page-title">הודעות</h2>

    <?php if (!$results): ?>
        <div class="no-views-box">אין הודעות</div>
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

                $zone     = trim((string)($row['Zone_Str'] ?? ''));
                $place    = trim((string)($row['Place_Str'] ?? ''));
                $family   = trim((string)($row['Family_Status_Str'] ?? ''));
                $children = trim((string)($row['Childs_Num_Str'] ?? ''));
                $height   = trim((string)($row['Height_Str'] ?? ''));
                $smoking  = trim((string)($row['Smoking_Habbit_Str'] ?? ''));

                $unread = (int)($row['unread_count'] ?? 0);

                $img = '/images/no_photo.jpg';
                ?>

                <div class="view-card">

                    <?php if ($unread > 0): ?>
                        <div class="view-card-top-badge">
                            💬 <?= $unread ?> חדשות
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

                        <a
                            href="#"
                            class="view-card-link open-chat-btn"
                            data-user-id="<?= $id ?>">
                            פתח צ'אט
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>