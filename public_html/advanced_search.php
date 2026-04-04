<?php
// ===== FILE: advanced_search.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   fetch results (כמו שהיה אצלך)
========================= */

$where = [];
$params = [];

/* גיל */
if (!empty($_GET['age_min'])) {
    $where[] = "Age >= :age_min";
    $params[':age_min'] = (int)$_GET['age_min'];
}
if (!empty($_GET['age_max'])) {
    $where[] = "Age <= :age_max";
    $params[':age_max'] = (int)$_GET['age_max'];
}

/* גובה */
if (!empty($_GET['height_min'])) {
    $where[] = "Height_Id >= :hmin";
    $params[':hmin'] = (int)$_GET['height_min'] - 139;
}
if (!empty($_GET['height_max'])) {
    $where[] = "Height_Id <= :hmax";
    $params[':hmax'] = (int)$_GET['height_max'] - 139;
}

/* מגדר */
if (!empty($_GET['gender'])) {
    $where[] = "Gender_Str = :gender";
    $params[':gender'] = $_GET['gender'];
}

/* שאילתה */
$sql = "SELECT * FROM users_profile";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY Id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   helper לתמונה
========================= */
function getMainProfileImage($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id AND Main_Pic = 1 AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $pic = $stmt->fetchColumn();
        if ($pic) return '/uploads/' . ltrim($pic, '/');
    } catch (Throwable $e) {
    }
    return '/images/no_photo.jpg';
}
?>

<div class="page-shell">

    <h2 style="text-align:center;margin-bottom:20px;">התאמות</h2>

    <div class="results">

        <?php if (!$results): ?>
            <div class="no-results">לא נמצאו תוצאות</div>
        <?php endif; ?>

        <?php foreach ($results as $user): ?>
            <?php
            $img = getMainProfileImage($pdo, (int)$user['Id']);
            $name = trim((string)($user['Name'] ?? ''));
            $age = trim((string)($user['Age'] ?? ''));
            ?>

            <div class="search-card-compact">

                <img src="<?= e($img) ?>" class="search-card-image">

                <div class="search-card-content">

                    <div class="search-card-top">
                        <a href="/?page=profile&id=<?= (int)$user['Id'] ?>" class="search-card-link">
                            לצפייה בפרופיל
                        </a>
                    </div>

                    <div class="search-card-title">
                        <?= e($name) ?><?= $age ? ', ' . e($age) : '' ?>
                    </div>

                    <div class="search-card-row">

                        <?php if (!empty($user['Zone_Str'])): ?>
                            <span><?= e($user['Zone_Str']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($user['Family_Status_Str'])): ?>
                            <span><?= e($user['Family_Status_Str']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($user['Height_Str'])): ?>
                            <span><?= e($user['Height_Str']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($user['Religion_Str'])): ?>
                            <span><?= e($user['Religion_Str']) ?></span>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>