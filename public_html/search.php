<?php
// ===== FILE: search.php =====

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
        // ignore
    }

    return '/images/no_photo.jpg';
}

/* =======================================================
   SEARCH PAGE
======================================================= */

$mainTable      = 'users_profile';
$fieldId        = 'Id';
$fieldDob       = 'DOB';
$fieldGenderId  = 'Gender_Id';
$fieldZoneId    = 'Zone_Id';

/* ===== genders ===== */
$genders = [];
try {
    $stmt = $pdo->query("
        SELECT Gender_Id, Gender_Str
        FROM gender
        ORDER BY Gender_Id
    ");
    $genders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $genders = [];
}

/* ===== zones ===== */
$zones = [];
try {
    $stmt = $pdo->query("
        SELECT Zone_Id, Zone_Str
        FROM zone
        ORDER BY Zone_Id
    ");
    $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $zones = [];
}

/* ===== input ===== */
$gender_id = trim((string)($_GET['gender_id'] ?? ''));
$age_from  = trim((string)($_GET['age_from'] ?? ''));
$age_to    = trim((string)($_GET['age_to'] ?? ''));
$zone_id   = trim((string)($_GET['zone_id'] ?? ''));

$results = [];
$search_done = ($gender_id !== '' || $age_from !== '' || $age_to !== '' || $zone_id !== '');

/* ===== search ===== */
if ($search_done) {
    $sql = "SELECT * FROM {$mainTable} WHERE 1=1";
    $params = [];

    if ($gender_id !== '') {
        $sql .= " AND {$fieldGenderId} = :gender_id";
        $params[':gender_id'] = (int)$gender_id;
    }

    if ($age_from !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, {$fieldDob}, CURDATE()) >= :age_from";
        $params[':age_from'] = (int)$age_from;
    }

    if ($age_to !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, {$fieldDob}, CURDATE()) <= :age_to";
        $params[':age_to'] = (int)$age_to;
    }

    if ($zone_id !== '') {
        $sql .= " AND {$fieldZoneId} = :zone_id";
        $params[':zone_id'] = (int)$zone_id;
    }

    $sql .= " ORDER BY {$fieldId} DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main class="page-shell">
    <section class="search-container">

        <h2>חיפוש משתמשים</h2>

        <form method="GET" class="search-form">
            <input type="hidden" name="page" value="search">

            <select name="gender_id">
                <option value="">מין</option>
                <?php foreach ($genders as $g): ?>
                    <option
                        value="<?= e($g['Gender_Id']) ?>"
                        <?= ((string)$gender_id === (string)$g['Gender_Id']) ? 'selected' : '' ?>>
                        <?= e($g['Gender_Str']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="age_from">
                <option value="">מגיל</option>
                <?php for ($i = 18; $i <= 80; $i++): ?>
                    <option value="<?= $i ?>" <?= ((string)$age_from === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="age_to">
                <option value="">עד גיל</option>
                <?php for ($i = 18; $i <= 80; $i++): ?>
                    <option value="<?= $i ?>" <?= ((string)$age_to === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="zone_id">
                <option value="">אזור</option>
                <?php foreach ($zones as $z): ?>
                    <option
                        value="<?= e($z['Zone_Id']) ?>"
                        <?= ((string)$zone_id === (string)$z['Zone_Id']) ? 'selected' : '' ?>>
                        <?= e($z['Zone_Str']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">חפש</button>
        </form>

        <div class="results">

            <?php if (!$search_done): ?>
                <div class="no-results">בחר סינון</div>

            <?php elseif (!$results): ?>
                <div class="no-results">אין תוצאות</div>

            <?php else: ?>
                <?php foreach ($results as $row): ?>

                    <?php
                    $id   = (int)($row['Id'] ?? 0);
                    $name = trim((string)($row['Name'] ?? ''));

                    $age = '';
                    if (!empty($row['DOB'])) {
                        try {
                            $age = date_diff(date_create((string)$row['DOB']), date_create('today'))->y;
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

                    $img = getMainProfileImage($pdo, $id);
                    ?>

                    <div class="view-card">

                        <div class="view-card-media">
                            <img
                                class="view-card-image"
                                src="<?= e($img) ?>"
                                alt="<?= e($name) ?>">
                        </div>

                        <div class="view-card-content">

                            <div class="view-card-name">
                                <?= e($name) ?>
                                <?= $age !== '' ? ', ' . e((string)$age) : '' ?>
                            </div>

                            <div class="view-card-divider"></div>

                            <div class="view-card-details">

                                <?php if ($family !== ''): ?>
                                    <div>מצב משפחתי: <?= e($family) ?></div>
                                <?php endif; ?>

                                <div>
                                    ילדים:
                                    <?= ($children === '' || $children === '0') ? 'ללא' : e($children) . '+' ?>
                                </div>

                                <?php if ($zone !== '' || $place !== ''): ?>
                                    <div>
                                        <?php if ($zone !== ''): ?>
                                            אזור: <?= e($zone) ?>
                                        <?php endif; ?>

                                        <?php if ($zone !== '' && $place !== ''): ?>
                                            |
                                        <?php endif; ?>

                                        <?php if ($place !== ''): ?>
                                            מקום: <?= e($place) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($height !== ''): ?>
                                    <div>גובה: <?= e($height) ?></div>
                                <?php endif; ?>

                                <?php if ($smoking !== ''): ?>
                                    <div>עישון: <?= e($smoking) ?></div>
                                <?php endif; ?>

                            </div>

                            <a class="view-card-link" href="/?page=profile&id=<?= $id ?>">
                                צפייה בפרופיל
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </section>
</main>