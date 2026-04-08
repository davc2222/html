<?php
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

/* 🔥 פונקציית תמונה כמו בכל האתר */
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
    }

    return '/images/no_photo.jpg';
}

/* שליפה */
$stmt = $pdo->prepare("
    SELECT up.*
    FROM blocked_users bu
    JOIN users_profile up ON up.Id = bu.Id
    WHERE bu.Blocked_ById = :me
    ORDER BY bu.Created_At DESC
");
$stmt->execute([':me' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-shell">

    <h2 class="views-page-title">פרופילים שחסמתי</h2>

    <?php if (!$results): ?>
        <div class="no-views-box">אין חסומים עדיין</div>
    <?php else: ?>

        <div class="views-list">

            <?php foreach ($results as $row): ?>

                <?php
                $id   = (int)$row['Id'];
                $name = trim((string)$row['Name']);

                $age = '';
                if (!empty($row['DOB'])) {
                    try {
                        $age = date_diff(date_create($row['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                    }
                }

                $zone     = trim((string)($row['Zone_Str'] ?? ''));
                $place    = trim((string)($row['Place_Str'] ?? ''));
                $family   = trim((string)($row['Family_Status_Str'] ?? ''));
                $children = trim((string)($row['Childs_Num_Str'] ?? ''));
                $height   = trim((string)($row['Height_Str'] ?? ''));
                $smoking  = trim((string)($row['Smoking_Habbit_Str'] ?? ''));

                $img = get_profile_image($pdo, $id);
                ?>

                <div class="view-card" id="blocked-card-<?= $id ?>">

                    <div class="view-card-media">
                        <img src="<?= h($img) ?>" class="view-card-image">
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= h($name) ?>
                            <?= $age ? ', ' . $age : '' ?>
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
                                    <?= $zone ? 'אזור: ' . h($zone) : '' ?>
                                    <?= ($zone && $place) ? ' | ' : '' ?>
                                    <?= $place ? 'מקום: ' . h($place) : '' ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($height !== ''): ?>
                                <div>גובה: <?= h($height) ?></div>
                            <?php endif; ?>

                            <?php if ($smoking !== ''): ?>
                                <div>עישון: <?= h($smoking) ?></div>
                            <?php endif; ?>

                        </div>

                        <div class="blocked-card-actions">

                            <a class="view-card-link" href="/?page=profile&id=<?= $id ?>">
                                צפייה בפרופיל
                            </a>

                            <span>|</span>

                            <a href="#"
                                class="view-card-link unblock-link"
                                onclick="unblockUser(<?= $id ?>); return false;">
                                בטל חסימה
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>
    function unblockUser(userId) {

        if (!confirm('לבטל חסימה למשתמש זה?')) {
            return;
        }

        const formData = new FormData();
        formData.append('blocked_id', userId);

        fetch('unblock_user.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    alert('שגיאה');
                    return;
                }

                document.getElementById('blocked-card-' + userId)?.remove();
            })
            .catch(() => alert('שגיאה'));
    }
</script>
🚨 למה דווקא שם?
אחרי כל ה־HTML ✔️