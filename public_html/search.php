<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

$genders = [];
$zones   = [];

try {
    $stmtGender = $pdo->query("
        SELECT Gender_id, Gender_Str
        FROM gender
        ORDER BY Gender_id ASC
    ");
    $genders = $stmtGender->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $genders = [];
}

try {
    $stmtZone = $pdo->query("
        SELECT Zone_id, Zone_Str
        FROM zone
        ORDER BY Zone_id ASC
    ");
    $zones = $stmtZone->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $zones = [];
}

$gender_id = $_GET['gender_id'] ?? '';
$age_min   = $_GET['age_min'] ?? '';
$age_max   = $_GET['age_max'] ?? '';
$zone_id   = $_GET['zone_id'] ?? '';

$results = [];
$search_done = ($gender_id !== '' || $age_min !== '' || $age_max !== '' || $zone_id !== '');

if ($age_min !== '' && $age_max !== '' && (int)$age_min > (int)$age_max) {
    $tmp = $age_min;
    $age_min = $age_max;
    $age_max = $tmp;
}

if ($search_done) {
    $sql = "
        SELECT *,
               TIMESTAMPDIFF(YEAR, DOB, CURDATE()) AS calc_age
        FROM users_profile
        WHERE email_verified = 1
          AND DOB IS NOT NULL
    ";

    $params = [];

    if ($gender_id !== '') {
        $sql .= " AND Gender_Id = :gender_id";
        $params[':gender_id'] = (int)$gender_id;
    }

    if ($age_min !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, DOB, CURDATE()) >= :age_min";
        $params[':age_min'] = (int)$age_min;
    }

    if ($age_max !== '') {
        $sql .= " AND TIMESTAMPDIFF(YEAR, DOB, CURDATE()) <= :age_max";
        $params[':age_max'] = (int)$age_max;
    }

    if ($zone_id !== '') {
        $sql .= " AND Zone_Id = :zone_id";
        $params[':zone_id'] = (int)$zone_id;
    }

    $sql .= " ORDER BY Id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        <div class="age-range-wrap">
            <select name="age_min">
                <option value="">מגיל</option>
                <?php for ($i = 18; $i <= 90; $i++): ?>
                    <option value="<?= $i ?>" <?= ((string)$age_min === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <span class="age-range-sep">עד</span>

            <select name="age_max">
                <option value="">עד גיל</option>
                <?php for ($i = 18; $i <= 90; $i++): ?>
                    <option value="<?= $i ?>" <?= ((string)$age_max === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

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

    <div class="results-list">
        <?php if (!$search_done): ?>
            <div class="no-results">בחר מין, גיל ואזור לחיפוש</div>

        <?php elseif (count($results) === 0): ?>
            <div class="no-results">לא נמצאו תוצאות מתאימות</div>

        <?php else: ?>
            <?php foreach ($results as $row): ?>
                <?php
                $name = $row['Name'] ?? 'ללא שם';
                $age  = $row['calc_age'] ?? '-';
                $city = $row['Place_Str'] ?? '';
                $id   = $row['Id'] ?? '';
                ?>
                <article class="search-result-card">
                    <div class="search-result-image">
                        <img src="/images/no_photo.jpg" alt="<?= htmlspecialchars($name) ?>">
                    </div>

                    <div class="search-result-content">
                        <h3><?= htmlspecialchars($name) ?></h3>

                        <div class="search-result-meta">
                            <span>גיל: <?= htmlspecialchars($age) ?></span>

                            <?php if ($city !== ''): ?>
                                <span>עיר: <?= htmlspecialchars($city) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="search-result-actions">
                            <a class="profile-btn" href="/profile.php?id=<?= urlencode($id) ?>">
                                לצפייה בפרופיל
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>