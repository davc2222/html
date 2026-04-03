<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* =======================================================
   SEARCH PAGE
   חיפוש משתמשים + הצגת כרטיסים קומפקטיים
======================================================= */

/* =======================================================
   1) הגדרות בסיס
======================================================= */
$mainTable      = 'users_profile';
$fieldId        = 'Id';
$fieldDob       = 'DOB';
$fieldGenderId  = 'Gender_Id';
$fieldZoneId    = 'Zone_Id';

/* =======================================================
   2) שליפת רשימות עזר
======================================================= */
$genders = [];
$zones   = [];

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

/* =======================================================
   3) קבלת קלט מהטופס
======================================================= */
$gender_id = $_GET['gender_id'] ?? '';
$age_from  = $_GET['age_from'] ?? '';
$age_to    = $_GET['age_to'] ?? '';
$zone_id   = $_GET['zone_id'] ?? '';

$results = [];
$search_done = ($gender_id !== '' || $age_from !== '' || $age_to !== '' || $zone_id !== '');

/* =======================================================
   4) חיפוש בפועל
======================================================= */
if ($search_done) {
    $sql = "SELECT * FROM {$mainTable} WHERE 1=1";
    $params = [];

    if ($gender_id !== '') {
        $sql .= " AND {$fieldGenderId} = :gender_id";
        $params[':gender_id'] = $gender_id;
    }

    if ($age_from !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, {$fieldDob}, CURDATE()) >= :age_from";
        $params[':age_from'] = $age_from;
    }

    if ($age_to !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, {$fieldDob}, CURDATE()) <= :age_to";
        $params[':age_to'] = $age_to;
    }

    if ($zone_id !== '') {
        $sql .= " AND {$fieldZoneId} = :zone_id";
        $params[':zone_id'] = $zone_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main class="page-shell">
    <section class="search-container">

        <h2>חיפוש משתמשים</h2>

        <!-- ===================================================
             5) טופס חיפוש
        ==================================================== -->
        <form method="GET" class="search-form">
            <input type="hidden" name="page" value="search">

            <select name="gender_id">
                <option value="">מין</option>
                <?php foreach ($genders as $g): ?>
                    <option value="<?= htmlspecialchars($g['Gender_Id']) ?>" <?= ((string)$gender_id === (string)$g['Gender_Id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['Gender_Str']) ?>
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
                    <option value="<?= htmlspecialchars($z['Zone_Id']) ?>" <?= ((string)$zone_id === (string)$z['Zone_Id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['Zone_Str']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">חפש</button>
        </form>

        <!-- ===================================================
             6) תוצאות
        ==================================================== -->
        <div class="results">

            <?php if (!$search_done): ?>
                <div class="no-results">בחר סינון</div>

            <?php elseif (!$results): ?>
                <div class="no-results">אין תוצאות</div>

            <?php else: ?>
                <?php foreach ($results as $row): ?>

                    <?php
                    /* ---------- נתוני משתמש ---------- */
                    $id   = (int)($row['Id'] ?? 0);
                    $name = trim((string)($row['Name'] ?? ''));

                    $age = '';
                    if (!empty($row['DOB'])) {
                        $age = date_diff(date_create($row['DOB']), date_create('today'))->y;
                    }

                    /* ---------- שדות תצוגה ---------- */
                    $zone        = trim((string)($row['Zone_Str'] ?? ''));
                    $place       = trim((string)($row['Place_Str'] ?? ''));
                    $family      = trim((string)($row['Family_Status_Str'] ?? ''));
                    $children    = trim((string)($row['Childs_Num_Str'] ?? ''));
                    $religion    = trim((string)($row['Religion_Str'] ?? ''));
                    $religionRef = trim((string)($row['Religion_Ref_Str'] ?? ''));
                    $smoking     = trim((string)($row['Smoking_Habbit_Str'] ?? ''));
                    $drinking    = trim((string)($row['Drinking_Habbit_Str'] ?? ''));
                    $height      = trim((string)($row['Height_Str'] ?? ''));

                    /* ---------- תמונה ---------- */
                    $img = '/images/no_photo.jpg';
                    ?>

                    <div class="card search-card-compact">

                        <a class="card-profile-link-top" href="/?page=profile&id=<?= $id ?>">צפייה</a>

                        <img
                            class="profile-img"
                            src="<?= htmlspecialchars($img) ?>"
                            alt="<?= htmlspecialchars($name) ?>"
                        >

                        <div class="card-content">

                            <div class="card-header">
                                <h3>
                                    <?= htmlspecialchars($name) ?>
                                    <?= $age !== '' ? ', ' . htmlspecialchars((string)$age) : '' ?>
                                </h3>
                            </div>

                            <div class="compact-row">
                                <?php if ($zone !== ''): ?><span><?= htmlspecialchars($zone) ?></span><?php endif; ?>
                                <?php if ($place !== ''): ?><span><?= htmlspecialchars($place) ?></span><?php endif; ?>
                            </div>

                            <div class="compact-row">
                                <?php if ($family !== ''): ?><span><?= htmlspecialchars($family) ?></span><?php endif; ?>
                                <?php if ($children !== ''): ?><span><?= htmlspecialchars($children) ?></span><?php endif; ?>
                                <?php if ($religion !== ''): ?><span><?= htmlspecialchars($religion) ?></span><?php endif; ?>
                                <?php if ($religionRef !== ''): ?><span><?= htmlspecialchars($religionRef) ?></span><?php endif; ?>
                            </div>

                            <div class="compact-row">
                                <?php if ($smoking !== ''): ?><span><?= htmlspecialchars($smoking) ?></span><?php endif; ?>
                                <?php if ($drinking !== ''): ?><span><?= htmlspecialchars($drinking) ?></span><?php endif; ?>
                                <?php if ($height !== ''): ?><span><?= htmlspecialchars($height) ?></span><?php endif; ?>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </section>
</main>