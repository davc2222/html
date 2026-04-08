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

$stmt = $pdo->prepare("
    SELECT
        v.Num,
        v.Date,
        up.*
    FROM views v
    JOIN users_profile up
        ON up.Id = v.Id
    WHERE v.ById = :me
      AND (v.Deleted_By_ById = 0 OR v.Deleted_By_ById IS NULL)
    ORDER BY v.Date DESC
");
$stmt->execute([':me' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-shell">

    <h2 class="views-page-title">פרופילים שצפיתי בהם</h2>

    <?php if (!$results): ?>
        <div class="no-views-box">עדיין לא צפית בפרופילים</div>
    <?php else: ?>

        <div class="views-list">

            <?php foreach ($results as $row): ?>

                <?php
                $id   = (int)$row['Id'];
                $num  = (int)$row['Num'];
                $name = trim((string)($row['Name'] ?? ''));

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
                $img      = get_profile_image($pdo, $id);
                $isOnline = is_user_online($pdo, $id);

                $viewDate = '';
                if (!empty($row['Date'])) {
                    try {
                        $viewDate = date('d/m/Y H:i', strtotime((string)$row['Date']));
                    } catch (Throwable $e) {
                    }
                }
                ?>

                <div class="view-card" id="viewed-card-<?= $num ?>">

                    <div class="view-card-media">
                        <img src="<?= h($img) ?>" class="view-card-image" alt="<?= h($name) ?>">

                        <?php if ($isOnline): ?>
                            <span class="online-badge" title="מחובר כעת"></span>
                        <?php endif; ?>
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-icons">
                            <span class="vc-icon vc-eye-reverse" title="צפיתי"></span>
                            <span class="view-card-status-text">צפיתי</span>
                        </div>

                        <div class="view-card-name">
                            <?= h($name) ?>
                            <?= $age !== '' ? ', ' . h((string)$age) : '' ?>
                        </div>

                        <?php if ($viewDate !== ''): ?>
                            <div class="view-card-date">נצפה בתאריך: <?= h($viewDate) ?></div>
                        <?php endif; ?>

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
                                    <?= $zone !== '' ? 'אזור: ' . h($zone) : '' ?>
                                    <?= ($zone !== '' && $place !== '') ? ' | ' : '' ?>
                                    <?= $place !== '' ? 'מקום: ' . h($place) : '' ?>
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
                                class="view-card-link"
                                onclick="deleteViewedCard(<?= $num ?>); return false;">
                                הסר מהרשימה
                            </a>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>
    window.showToast = function(message, isError = false) {
        let toast = document.getElementById('siteToast');

        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'siteToast';
            toast.className = 'site-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.remove('is-error', 'show');

        if (isError) {
            toast.classList.add('is-error');
        }

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        clearTimeout(window.siteToastTimer);
        window.siteToastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    };

    window.deleteViewedCard = function(viewNum) {
        if (!confirm('להסיר מהרשימה?')) {
            return;
        }

        const card = document.getElementById('viewed-card-' + viewNum);
        if (!card) {
            showToast('הכרטיס לא נמצא', true);
            return;
        }

        const formData = new FormData();
        formData.append('view_num', viewNum);

        fetch('/delete_viewed_by_me.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data = null;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON from delete_viewed_by_me.php:', text);
                    showToast('תגובה לא תקינה מהשרת', true);
                    return;
                }

                if (!data.ok) {
                    showToast(data.error || 'שגיאה בהסרה', true);
                    return;
                }

                card.classList.add('is-removing');

                setTimeout(() => {
                    card.remove();

                    const list = document.querySelector('.views-list');
                    if (list && !list.querySelector('.view-card')) {
                        list.outerHTML = '<div class="no-views-box">עדיין לא צפית בפרופילים</div>';
                    }
                }, 280);

                showToast('הוסר מהרשימה');
            })
            .catch(err => {
                console.error(err);
                showToast('שגיאה בשרת', true);
            });
    };
</script>