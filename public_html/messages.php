<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    try {
        $stmtPresence = $pdo->prepare("
            UPDATE users
            SET last_seen = NOW()
            WHERE Id = :id
            LIMIT 1
        ");
        $stmtPresence->execute([':id' => (int)$_SESSION['user_id']]);
    } catch (Throwable $e) {
        // לא להפיל דף בגלל נוכחות
    }
}
?>

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

/* 🔥 פונקציה מתוקנת */
function get_profile_image(PDO $pdo, int $userId): string {
    try {
        // ניסיון תמונה ראשית
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

        // אם אין ראשית - מביא ראשונה
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
        // ממשיכים ל fallback
    }

    try {
        // 🔥 fallback לפי מין
        $stmt = $pdo->prepare("
            SELECT Gender_Str
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $gender = trim((string)$stmt->fetchColumn());

        if ($gender === 'אישה') {
            return '/images/default_female.svg';
        }
    } catch (Throwable $e) {
        // ignore
    }

    return '/images/default_male.svg';
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
      AND (
            (m.Id = :me AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL))
         OR (m.ById = :me AND (m.Deleted_By_ById = 0 OR m.Deleted_By_ById IS NULL))
      )
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
                $otherUserId = (int)($row['other_user_id'] ?? 0);
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

                $unread = (int)($row['unread_count'] ?? 0);

                $img = get_profile_image($pdo, $otherUserId);
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

                        <p>
                            <a
                                href="#"
                                class="view-card-link"
                                onclick="openMessageModal(<?= (int)$otherUserId ?>, '<?= h($name) ?>', '<?= h($img) ?>'); return false;">
                                פתח צ'אט
                            </a>
                            |
                            <a href="/?page=profile&id=<?= $otherUserId ?>" class="view-card-link">
                                פתח פרופיל
                            </a>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>