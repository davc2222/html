<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);

/* ===== עדכון נוכחות ===== */
if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users_profile
            SET last_seen = NOW()
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
    } catch (Throwable $e) {
    }
}

/* ===== helpers ===== */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function is_user_online(PDO $pdo, int $userId): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT last_seen
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $ts = $stmt->fetchColumn();

        if (!$ts) {
            return false;
        }

        return (strtotime($ts) >= (time() - 120));
    } catch (Throwable $e) {
        return false;
    }
}

function getMainProfileImage(PDO $pdo, int $id): string {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $pic = $stmt->fetchColumn();

        if ($pic) {
            return '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
    }

    return '/images/no_photo.jpg';
}

/* ===== שליפה ===== */
$results = [];

$stmt = $pdo->query("
    SELECT *
    FROM users_profile
    ORDER BY Id DESC
    LIMIT 20
");

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .view-card {
        display: flex;
        gap: 30px;
        align-items: flex-start;
        background: #fff;
        border-radius: 22px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    }

    .view-card-media {
        position: relative;
        width: 220px;
        min-width: 220px;
        height: 220px;
    }

    .view-card-image {
        width: 220px;
        height: 220px;
        object-fit: cover;
        border-radius: 22px;
        display: block;
    }

    .online-badge {
        position: absolute;
        right: 10px;
        bottom: 10px;
        width: 16px;
        height: 16px;
        background: #22c55e;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
        z-index: 50;
    }

    .view-card-content {
        flex: 1;
        text-align: right;
    }

    .view-card-icons {
        display: flex;
        justify-content: flex-start;
        gap: 8px;
        margin-bottom: 6px;
    }

    .vc-icon {
        width: 18px;
        height: 18px;
        display: inline-block;
        opacity: 0.7;
    }

    .vc-search {
        background: url("/images/search.svg") center/contain no-repeat;
    }

    .view-card-name {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 10px;
        color: #111;
    }

    .view-card-detail {
        font-size: 15px;
        line-height: 1.7;
        color: #555;
        margin-bottom: 2px;
    }

    .view-card-profile-link {
        display: inline-block;
        margin-top: 12px;
        color: #12b5cb;
        text-decoration: none;
        font-weight: 700;
    }

    .view-card-profile-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .view-card {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .view-card-content {
            width: 100%;
            text-align: center;
        }

        .view-card-icons {
            justify-content: center;
        }
    }
</style>

<div class="page-shell">

    <?php foreach ($results as $user): ?>

        <?php
        $id        = (int)($user['Id'] ?? 0);
        $name      = $user['Name'] ?? '';
        $age       = $user['Age'] ?? '';
        $family    = $user['Family_Status_Str'] ?? '';
        $children  = $user['Childs_Num_Str'] ?? '';
        $zone      = $user['Zone_Str'] ?? '';
        $place     = $user['Place_Str'] ?? '';
        $height    = $user['Height_Str'] ?? '';
        $occupation = $user['Occupation_Str'] ?? '';

        $img = getMainProfileImage($pdo, $id);
        $isOnline = is_user_online($pdo, $id);
        ?>

        <div class="view-card">

            <div class="view-card-media">
                <img src="<?= e($img) ?>" alt="<?= e($name) ?>" class="view-card-image">

                <?php if ($isOnline): ?>
                    <span class="online-badge"></span>
                <?php endif; ?>
            </div>

            <div class="view-card-content">

                <div class="view-card-icons">
                    <span class="vc-icon vc-search"></span>
                </div>

                <div class="view-card-name">
                    <?= e($name) ?><?= ($age !== '' && $age !== null) ? ', ' . (int)$age : '' ?>
                </div>

                <?php if (!empty($family)): ?>
                    <div class="view-card-detail">מצב משפחתי: <?= e($family) ?></div>
                <?php endif; ?>

                <?php if (!empty($children)): ?>
                    <div class="view-card-detail">ילדים: <?= e($children) ?></div>
                <?php endif; ?>

                <?php if (!empty($zone) || !empty($place)): ?>
                    <div class="view-card-detail">
                        <?php if (!empty($zone)): ?>
                            אזור: <?= e($zone) ?>
                        <?php endif; ?>
                        <?php if (!empty($zone) && !empty($place)): ?>
                            |
                        <?php endif; ?>
                        <?php if (!empty($place)): ?>
                            מקום: <?= e($place) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($height)): ?>
                    <div class="view-card-detail">גובה: <?= e($height) ?></div>
                <?php endif; ?>

                <?php if (!empty($occupation)): ?>
                    <div class="view-card-detail">עיסוק: <?= e($occupation) ?></div>
                <?php endif; ?>

                <a href="/?page=profile&id=<?= $id ?>" class="view-card-profile-link">
                    צפייה בפרופיל
                </a>

            </div>

        </div>

    <?php endforeach; ?>

</div>