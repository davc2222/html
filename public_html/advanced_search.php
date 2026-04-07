<?php
// ===== FILE: advanced_search.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* 🔥 פונקציה מתוקנת */
function getMainProfileImage(PDO $pdo, int $id): string {
    try {
        // תמונה ראשית
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

        // אם אין ראשית - קח ראשונה
        if (!$pic) {
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id
                  AND Pic_Status = 1
                ORDER BY Main_Pic DESC, Pic_Num ASC
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $pic = $stmt->fetchColumn();
        }

        if ($pic) {
            return '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
        // ממשיכים fallback
    }

    try {
        // 🔥 fallback לפי מין
        $stmt = $pdo->prepare("
            SELECT Gender_Str
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $gender = trim((string)$stmt->fetchColumn());

        if ($gender === 'אישה') {
            return '/images/default_female.svg';
        }
    } catch (Throwable $e) {
        // ignore
    }

    return '/images/default_male.svg';
}

/* =========================
   current user gender
========================= */
$currentGenderId = null;
if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT Gender_Id FROM users_profile WHERE Id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $currentGenderId = $stmt->fetchColumn();
        if ($currentGenderId !== false) {
            $currentGenderId = (int)$currentGenderId;
        } else {
            $currentGenderId = null;
        }
    } catch (Throwable $e) {
        $currentGenderId = null;
    }
}

/* =========================
   saved preferences
========================= */
$prefs = [
    'age_min'       => 25,
    'age_max'       => 65,
    'height_min'    => 140,
    'height_max'    => 220,
    'children'      => '',
    'zone'          => [],
    'religion'      => [],
    'religion_ref'  => [],
    'smoking'       => [],
    'drinking'      => [],
    'family_status' => [],
    'body_type'     => [],
    'vegitrain'     => [],
];

if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_search_preferences WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $prefs['age_min']    = (int)$row['age_min'];
            $prefs['age_max']    = (int)$row['age_max'];
            $prefs['height_min'] = (int)$row['height_min'];
            $prefs['height_max'] = (int)$row['height_max'];
            $prefs['children']   = (string)($row['children'] ?? '');

            foreach (['zone', 'religion', 'religion_ref', 'smoking', 'drinking', 'family_status', 'body_type', 'vegitrain'] as $f) {
                $decoded = json_decode((string)($row[$f] ?? '[]'), true);
                $prefs[$f] = is_array($decoded) ? array_map('strval', $decoded) : [];
            }
        }
    } catch (Throwable $e) {
    }
}

/* =========================
   opposite gender default
========================= */
$wantedGenderId = null;
if ($currentGenderId === 1) $wantedGenderId = 2;
elseif ($currentGenderId === 2) $wantedGenderId = 1;

/* =========================
   results
========================= */
$results = [];

try {
    $sql = "SELECT * FROM users_profile WHERE Id <> :me";
    $params = [':me' => $userId];

    if ($wantedGenderId !== null) {
        $sql .= " AND Gender_Id = :wanted_gender";
        $params[':wanted_gender'] = $wantedGenderId;
    }

    $sql .= " AND TIMESTAMPDIFF(YEAR, DOB, CURDATE()) BETWEEN :age_min AND :age_max";
    $params[':age_min'] = (int)$prefs['age_min'];
    $params[':age_max'] = (int)$prefs['age_max'];

    $sql .= " ORDER BY Id DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

?>

<div class="page-shell">

    <div class="views-list">

        <?php if (!$results): ?>
            <div class="no-results">לא נמצאו תוצאות</div>
        <?php else: ?>

            <?php foreach ($results as $user): ?>

                <?php
                $id   = (int)$user['Id'];
                $name = trim((string)$user['Name']);

                $age = '';
                if (!empty($user['DOB'])) {
                    try {
                        $age = date_diff(date_create($user['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                    }
                }

                $img = getMainProfileImage($pdo, $id);
                ?>

                <div class="view-card">

                    <div class="view-card-media">
                        <img class="view-card-image"
                            src="<?= e($img) ?>"
                            alt="<?= e($name) ?>">
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= e($name) ?><?= $age ? ', ' . $age : '' ?>
                        </div>

                        <div class="view-card-divider"></div>

                        <a class="view-card-link"
                            href="/?page=profile&id=<?= $id ?>">
                            צפייה בפרופיל
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>