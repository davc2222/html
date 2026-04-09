<?php
if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$id         = (int)($user['Id'] ?? 0);
$name       = $user['Name'] ?? '';
$age        = $user['Age'] ?? '';
$family     = $user['Family_Status_Str'] ?? '';
$children   = $user['Childs_Num_Str'] ?? '';
$zone       = $user['Zone_Str'] ?? '';
$place      = $user['Place_Str'] ?? '';
$height     = $user['Height_Str'] ?? '';
$occupation = $user['Occupation_Str'] ?? '';
$smoking    = $user['Smoking_Habbit_Str'] ?? '';

$cardId          = $cardId ?? '';
$cardIconClass   = $cardIconClass ?? 'vc-search';
$cardTopBadge    = $cardTopBadge ?? '';
$cardSubline     = $cardSubline ?? '';
$cardActionsHtml = $cardActionsHtml ?? '<a href="/?page=profile&id=' . $id . '" class="view-card-profile-link">צפייה בפרופיל</a>';
$cardShowOnline  = $cardShowOnline ?? true;

/* ===== חשוב: לא עושים כאן שאילתות ===== */
$img = $user['Image'] ?? '/images/no_photo.jpg';
$isOnline = $cardShowOnline ? !empty($user['is_online']) : false;

if (($age === '' || $age === null) && !empty($user['DOB'])) {
    try {
        $age = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
    } catch (Throwable $e) {
        $age = '';
    }
}
?>

<div class="view-card" <?= $cardId !== '' ? ' id="' . e($cardId) . '"' : '' ?>>

    <?php if ($cardTopBadge !== ''): ?>
        <div class="view-card-top-badge"><?= $cardTopBadge ?></div>
    <?php endif; ?>

    <div class="view-card-media">
        <img src="<?= e($img) ?>" alt="<?= e($name) ?>" class="view-card-image">

        <?php if ($isOnline): ?>
            <span class="online-badge"></span>
        <?php endif; ?>
    </div>

    <div class="view-card-content">

        <div class="view-card-icons">
            <span class="vc-icon <?= e($cardIconClass) ?>"></span>
        </div>

        <div class="view-card-name">
            <?= e($name) ?><?= ($age !== '' && $age !== null) ? ', ' . (int)$age : '' ?>
        </div>

        <?php if ($cardSubline !== ''): ?>
            <div class="view-card-detail"><?= e($cardSubline) ?></div>
        <?php endif; ?>

        <?php if (!empty($family)): ?>
            <div class="view-card-detail">מצב משפחתי: <?= e($family) ?></div>
        <?php endif; ?>

        <div class="view-card-detail">
            ילדים:
            <?= ($children === '' || $children === '0') ? 'ללא' : e($children) . '+' ?>
        </div>

        <?php if (!empty($zone) || !empty($place)): ?>
            <div class="view-card-detail">
                <?php if (!empty($zone)): ?>
                    אזור: <?= e($zone) ?>
                <?php endif; ?>

                <?php if (!empty($zone) && !empty($place)): ?>
                    |
                <?php endif; ?>

                <?php if (!empty($place)): ?>
                    מקום: <?= e($place) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($height)): ?>
            <div class="view-card-detail">גובה: <?= e($height) ?></div>
        <?php endif; ?>

        <?php if (!empty($smoking)): ?>
            <div class="view-card-detail">עישון: <?= e($smoking) ?></div>
        <?php endif; ?>

        <?php if (!empty($occupation)): ?>
            <div class="view-card-detail">עיסוק: <?= e($occupation) ?></div>
        <?php endif; ?>

        <div class="view-card-actions">
            <?= $cardActionsHtml ?>
        </div>

    </div>

</div>