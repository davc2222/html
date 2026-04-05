<?php
// ===== FILE: advanced_search.php =====

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
            WHERE Id = :id AND Main_Pic = 1 AND Pic_Status = 1
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

/* ========= filters ========= */
$where = [];
$params = [];

if (!empty($_GET['age_min'])) {
    $where[] = "Age >= :age_min";
    $params[':age_min'] = (int)$_GET['age_min'];
}
if (!empty($_GET['age_max'])) {
    $where[] = "Age <= :age_max";
    $params[':age_max'] = (int)$_GET['age_max'];
}

if (!empty($_GET['height_min'])) {
    $where[] = "Height_Id >= :hmin";
    $params[':hmin'] = (int)$_GET['height_min'] - 139;
}
if (!empty($_GET['height_max'])) {
    $where[] = "Height_Id <= :hmax";
    $params[':hmax'] = (int)$_GET['height_max'] - 139;
}

if (!empty($_GET['gender'])) {
    $where[] = "Gender_Str = :gender";
    $params[':gender'] = $_GET['gender'];
}

$hasFilters = !empty($where);
$results = [];

$sql = "SELECT * FROM users_profile";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY Id DESC LIMIT 50";

if ($hasFilters) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-shell">

    <h2 style="text-align:center;margin-bottom:20px;">חיפוש מתקדם</h2>

    <!-- 🔥 טופס -->
    <form method="GET" action="/" class="search-form" style="text-align:center;margin-bottom:20px;">
        <input type="hidden" name="page" value="advanced_search">

        <select name="gender">
            <option value="">מין</option>
            <option value="גבר" <?= (($_GET['gender'] ?? '') === 'גבר') ? 'selected' : '' ?>>גבר</option>
            <option value="אישה" <?= (($_GET['gender'] ?? '') === 'אישה') ? 'selected' : '' ?>>אישה</option>
        </select>

        <select name="age_min">
            <option value="">מגיל</option>
            <?php for ($i = 18; $i <= 80; $i++): ?>
                <option value="<?= $i ?>" <?= (($_GET['age_min'] ?? '') == $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <select name="age_max">
            <option value="">עד גיל</option>
            <?php for ($i = 18; $i <= 80; $i++): ?>
                <option value="<?= $i ?>" <?= (($_GET['age_max'] ?? '') == $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">חפש</button>
    </form>

    <!-- 🔥 תוצאות -->
    <div class="results">

        <?php if (!$hasFilters): ?>
            <div class="no-results">בחר העדפות חיפוש</div>

        <?php elseif (!$results): ?>
            <div class="no-results">לא נמצאו תוצאות</div>

        <?php else: ?>
            <?php foreach ($results as $user): ?>
                <?php
                $img = getMainProfileImage($pdo, (int)$user['Id']);
                ?>

                <div class="search-card-compact">
                    <img src="<?= e($img) ?>" class="search-card-image">

                    <div class="search-card-content">
                        <a href="/?page=profile&id=<?= (int)$user['Id'] ?>">
                            <?= e($user['Name']) ?>, <?= e($user['Age']) ?>
                        </a>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>