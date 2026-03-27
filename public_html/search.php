<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* =======================================================
   הגדרות - תשנה כאן רק לפי המסד שלך
======================================================= */

$mainTable      = 'users_profile';   // לדוגמה: cards / members / profiles
$fieldId        = 'Id';                // לדוגמה: Card_id
$fieldName      = 'Name';              // לדוגמה: NickName
$fieldAge       = 'Age';               // לדוגמה: Age
$fieldGenderId  = 'Gender_id';         // אצלך כנראה Gender_id
$fieldZoneId    = 'Zone_id';           // אצלך כנראה Zone_id
$fieldImage     = 'Image';             // לדוגמה: MainPic
$fieldCity      = 'Place_Str';              // לדוגמה: City_Str

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
$age_range = $_GET['age_range'] ?? '';
$zone_id   = $_GET['zone_id'] ?? '';

$results = [];
$search_done = ($gender_id !== '' || $age_range !== '' || $zone_id !== '');

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

    if ($age_range !== '') {
        [$min_age, $max_age] = explode('-', $age_range);
        $sql .= " AND {$fieldAge} BETWEEN :min_age AND :max_age";
        $params[':min_age'] = (int)$min_age;
        $params[':max_age'] = (int)$max_age;
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

        <!-- מין -->
        <select name="gender_id">
            <option value="">בחר מין</option>
            <?php foreach ($genders as $gender): ?>
                <option value="<?= $gender['Gender_id'] ?>" <?= ((string)$gender_id === (string)$gender['Gender_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($gender['Gender_Str']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- גילאים -->
        <select name="age_range">
            <option value="">בחר טווח גילאים</option>
            <?php for ($start = 18; $start <= 80; $start += 5): ?>
                <?php
                $end = $start + 4;
                if ($end > 80) {
                    $end = 80;
                }
                $rangeValue = $start . '-' . $end;
                ?>
                <option value="<?= $rangeValue ?>" <?= ($age_range === $rangeValue) ? 'selected' : '' ?>>
                    <?= $start ?> - <?= $end ?>
                </option>
            <?php endfor; ?>
        </select>

        <!-- אזור -->
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
                $age  = $row[$fieldAge] ?? '-';
                $city = $row[$fieldCity] ?? '';
                $id   = $row[$fieldId] ?? '';
                ?>
                <div class="card">
                    <img class="profile-img" src="/images/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>">

                    <h3><?= htmlspecialchars($name) ?></h3>
                    <p>גיל: <?= htmlspecialchars($age) ?></p>

                    <?php if ($city !== ''): ?>
                        <p><?= htmlspecialchars($city) ?></p>
                    <?php endif; ?>

                    <a class="profile-btn" href="/profile.php?id=<?= urlencode($id) ?>">
                        לצפייה בפרופיל
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>