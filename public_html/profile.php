```php
<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileFields = require __DIR__ . '/profile_fields.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);
$viewerId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "משתמש לא נמצא";
    exit;
}

$isOwner = ($viewerId === (int)$user['Id']);

/* 🔥 חישוב גיל מ-DOB */
$age = null;
if (!empty($user['DOB'])) {
    try {
        $dob = new DateTime($user['DOB']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
    } catch (Exception $e) {
        $age = null;
    }
}

/* רישום צפייה */
if ($viewerId > 0 && !$isOwner) {
    $stmt = $pdo->prepare("
        SELECT Num FROM views
        WHERE Id = :profile_id AND ById = :viewer_id
        LIMIT 1
    ");
    $stmt->execute([
        ':profile_id' => $id,
        ':viewer_id'  => $viewerId
    ]);
    $existingView = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingView) {
        $stmt = $pdo->prepare("
            UPDATE views
            SET Date = NOW(), `New` = 1, Deleted_By_Id = 0
            WHERE Num = :num LIMIT 1
        ");
        $stmt->execute([':num' => $existingView['Num']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO views (Id, ById, Date, `New`, Deleted_By_Id, Deleted_By_ById)
            VALUES (:profile_id, :viewer_id, NOW(), 1, 0, 0)
        ");
        $stmt->execute([
            ':profile_id' => $id,
            ':viewer_id'  => $viewerId
        ]);
    }
}

/* תמונה ראשית */
$profileImage = '/images/no_photo.jpg';

$stmt = $pdo->prepare("SELECT Pic_Name FROM user_pics WHERE Id = :id AND Main_Pic = 1 LIMIT 1");
$stmt->execute([':id' => $id]);
if ($pic = $stmt->fetchColumn()) {
    $profileImage = '/uploads/' . $pic;
}

/* גלריה */
$stmt = $pdo->prepare("SELECT Pic_Num, Pic_Name, Main_Pic FROM user_pics WHERE Id = :id ORDER BY Main_Pic DESC, Pic_Num");
$stmt->execute([':id' => $id]);
$pics = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* פיצול שדות */
$right = [];
$left = [];

foreach ($profileFields as $k => $cfg) {
    if (($cfg['side'] ?? '') === 'right') $right[$k] = $cfg;
    if (($cfg['side'] ?? '') === 'left')  $left[$k] = $cfg;
}
?>

<div class="page-shell profile-shell">
    <div class="profile-layout">

        <!-- RIGHT -->
        <div class="profile-right-col">
            <div class="profile-right-card">

                <div class="profile-main-image-wrap">
                    <img src="<?= e($profileImage) ?>" class="profile-main-image">
                </div>

                <h2 class="profile-main-title">
                    <?= e($user['Name'] ?? 'ללא שם') ?>
                    <?php if ($age !== null): ?>
                        , <?= $age ?>
                    <?php endif; ?>
                </h2>

                <?php if (!$isOwner && $viewerId > 0): ?>
                    <a href="#" class="open-chat-btn profile-main-btn" data-user-id="<?= (int)$user['Id'] ?>">
                        ✉ שלח הודעה
                    </a>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                    <a href="#" class="profile-right-edit-link" id="profileRightEditBtn">
                        ✎ פרטים נוספים
                    </a>
                <?php endif; ?>

                <div class="profile-right-facts" id="profileRightFacts">
                    <?php foreach ($right as $field => $cfg): ?>
                        <?php $val = trim((string)($user[$field] ?? '')); ?>

                        <div class="profile-right-row" data-field="<?= e($field) ?>" data-label="<?= e($cfg['label']) ?>">
                            <span class="profile-right-label"><?= e($cfg['label']) ?>:</span>

                            <span class="profile-right-value">
                                <?php
                                if (($cfg['label'] ?? '') === 'גיל') {
                                    echo $age !== null ? e($age) : 'לא מולא';
                                } else {
                                    echo $val !== '' ? e($val) : 'לא מולא';
                                }
                                ?>
                            </span>
                        </div>

                    <?php endforeach; ?>
                </div>

            </div>
        </div>

        <!-- LEFT -->
        <div class="profile-left-col">

            <?php foreach ($left as $field => $cfg): ?>
                <?php $val = trim((string)($user[$field] ?? '')); ?>

                <div class="profile-left-card">
                    <div class="profile-left-card-head">
                        <h3><?= e($cfg['label']) ?></h3>

                        <?php if ($isOwner): ?>
                            <a href="#" class="profile-inline-edit-btn edit-btn" data-field="<?= e($field) ?>">✎</a>
                        <?php endif; ?>
                    </div>

                    <div class="profile-left-view<?= $val === '' ? ' is-empty' : '' ?>" data-field="<?= e($field) ?>">
                        <?= $val !== '' ? nl2br(e($val)) : 'לא מולא' ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</div>

<script>
    /* הקוד JS שלך נשאר כמו שהוא — לא נגעתי */
</script>
```