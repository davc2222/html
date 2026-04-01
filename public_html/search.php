<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* =======================================================
   הגדרות
======================================================= */

$mainTable      = 'users_profile';
$fieldId        = 'Id';
$fieldName      = 'Name';
$fieldDob       = 'DOB';
$fieldGenderId  = 'Gender_id';
$fieldZoneId    = 'Zone_id';
$fieldImage     = 'Image';
$fieldCity      = 'Place_Str';

/* =======================================================
   שליפת מגדרים
======================================================= */

$genders = [];

try {
    $stmtGender = $pdo->query("
        SELECT Gender_id, Gender_Str
        FROM gender
        ORDER BY Gender_id ASC
    ");
    $genders = $stmtGender->fetchAll();
} catch (PDOException $e) {
    $genders = [];
}

/* =======================================================
   שליפת אזורים
======================================================= */

$zones = [];

try {
    $stmtZone = $pdo->query("
        SELECT Zone_id, Zone_Str
        FROM zone
        ORDER BY Zone_id ASC
    ");
    $zones = $stmtZone->fetchAll();
} catch (PDOException $e) {
    $zones = [];
}

/* =======================================================
   קבלת נתוני טופס
======================================================= */

$gender_id = $_GET['gender_id'] ?? '';
$age_from  = $_GET['age_from'] ?? '';
$age_to    = $_GET['age_to'] ?? '';
$zone_id   = $_GET['zone_id'] ?? '';

if ($age_from !== '' && !is_numeric($age_from)) {
    $age_from = '';
}

if ($age_to !== '' && !is_numeric($age_to)) {
    $age_to = '';
}

if ($age_from !== '' && $age_to !== '' && (int)$age_from > (int)$age_to) {
    $tmp = $age_from;
    $age_from = $age_to;
    $age_to = $tmp;
}

$results = [];
$search_done = ($gender_id !== '' || $age_from !== '' || $age_to !== '' || $zone_id !== '');

/* =======================================================
   חיפוש
======================================================= */

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

    $sql .= " ORDER BY {$fieldId} ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "<div class='search-container'><div class='no-results'>שגיאה בחיפוש: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    }
}
?>

<section class="search-container">
    <h2>חיפוש משתמשים</h2>

    <form class="search-form" method="GET" action="">
        <input type="hidden" name="page" value="search">

        <select name="gender_id">
            <option value="">בחר מין</option>
            <?php foreach ($genders as $gender): ?>
                <option value="<?= $gender['Gender_id'] ?>" <?= ((string)$gender_id === (string)$gender['Gender_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($gender['Gender_Str']) ?>
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
            <option value="">בחר אזור</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?= $zone['Zone_id'] ?>" <?= ((string)$zone_id === (string)$zone['Zone_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($zone['Zone_Str']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">חפש</button>
    </form>

    <div class="results">
        <?php if (!$search_done): ?>
            <div class="no-results">בחר מין, טווח גילאים ואזור לחיפוש</div>

        <?php elseif (count($results) === 0): ?>
            <div class="no-results">לא נמצאו תוצאות מתאימות</div>

        <?php else: ?>
            <?php foreach ($results as $row): ?>
                <?php
                $img  = !empty($row[$fieldImage]) ? $row[$fieldImage] : 'no_photo.jpg';
                $name = $row[$fieldName] ?? 'ללא שם';
                $city = trim((string)($row[$fieldCity] ?? ''));
                $id   = $row[$fieldId] ?? '';

                $age = '';
                if (!empty($row[$fieldDob])) {
                    $age = (string) date_diff(date_create($row[$fieldDob]), date_create('today'))->y;
                }

                $zodiac       = trim((string)($row['ZODIAC'] ?? ''));
                $familyStatus = trim((string)($row['family_status'] ?? ''));
                $religion     = trim((string)($row['religion'] ?? ''));
                $religionRef  = trim((string)($row['religion_ref'] ?? ''));
                $place        = trim((string)($row['place'] ?? $city));
                $height       = trim((string)($row['height'] ?? ''));
                $smoking      = trim((string)($row['smoking_habbit'] ?? ''));

                $childrenRaw = $row['childs_num'] ?? '';
                if ($childrenRaw === '' || $childrenRaw === null) {
                    $children = '';
                } else {
                    $children = ((int)$childrenRaw > 0) ? $childrenRaw . '+' : '0';
                }
                ?>

                <div class="card">
                    <img
                        class="profile-img"
                        src="/images/<?= htmlspecialchars($img) ?>"
                        alt="<?= htmlspecialchars($name) ?>"
                    >

                    <div class="card-content">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($name) ?></h3>
                            <div class="card-line"></div>
                        </div>

                        <div class="card-info">
                            <div class="info-row">
                                <?php if ($age !== ''): ?>
                                    <span><?= htmlspecialchars($age) ?></span>
                                <?php endif; ?>

                                <?php if ($zodiac !== ''): ?>
                                    <span><?= htmlspecialchars($zodiac) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="info-row">
                                <?php if ($familyStatus !== ''): ?>
                                    <span><?= htmlspecialchars($familyStatus) ?></span>
                                <?php endif; ?>

                                <?php if ($children !== ''): ?>
                                    <span><?= htmlspecialchars($children) ?></span>
                                <?php endif; ?>

                                <?php if ($religion !== ''): ?>
                                    <span><?= htmlspecialchars($religion) ?></span>
                                <?php endif; ?>

                                <?php if ($religionRef !== ''): ?>
                                    <span><?= htmlspecialchars($religionRef) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="info-row">
                                <?php if ($place !== ''): ?>
                                    <span><?= htmlspecialchars($place) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="info-row">
                                <?php if ($height !== ''): ?>
                                    <span><?= htmlspecialchars($height) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="info-row">
                                <?php if ($smoking !== ''): ?>
                                    <span><?= htmlspecialchars($smoking) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a class="profile-link" href="/?page=profile&id=<?= urlencode($id) ?>">
    לצפיה בפרופיל
</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>