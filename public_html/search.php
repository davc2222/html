<?php
// ===== FILE: search.php =====

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
    $me = (int)($_SESSION['user_id'] ?? 0);
    $session_user_id = $me;

    $sql = "SELECT * FROM {$mainTable} WHERE 1=1";
    $params = [];

    if ($me > 0) {
        $sql .= "
            AND {$mainTable}.Id <> :me
            AND NOT EXISTS (
                SELECT 1
                FROM blocked_users bu
                WHERE (bu.Id = {$mainTable}.Id AND bu.Blocked_ById = :me)
                   OR (bu.Id = :me AND bu.Blocked_ById = {$mainTable}.Id)
            )
        ";
        $params[':me'] = $me;
    }

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
} else {
    $session_user_id = (int)($_SESSION['user_id'] ?? 0);
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
                    $user = $row;

                    $user['Age'] = '';
                    if (!empty($user['DOB'])) {
                        try {
                            $user['Age'] = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                        } catch (Throwable $e) {
                            $user['Age'] = '';
                        }
                    }

                    $cardId = '';
                    $cardMode = 'search';
                    $cardTopBadge = '';
                    $cardSubline = '';
                    $cardShowOnline = true;
                    $cardActionsHtml = '<a href="/?page=profile&id=' . (int)$user['Id'] . '" class="view-card-profile-link">צפייה בפרופיל</a>';

                    $user['Image'] = getMainProfileImage($pdo, (int)$user['Id']);
                    $user['is_online'] = is_user_online($pdo, (int)$user['Id']);

                    include __DIR__ . '/includes/view_card.php';
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </section>
</main>