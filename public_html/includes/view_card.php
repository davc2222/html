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
$cardTopBadge    = $cardTopBadge ?? '';
$cardSubline     = $cardSubline ?? '';
$cardActionsHtml = $cardActionsHtml ?? '<a href="/?page=profile&id=' . $id . '" class="view-card-profile-link">צפייה בפרופיל</a>';
$cardShowOnline  = $cardShowOnline ?? true;

$img = $user['Image'] ?? '/images/no_photo.jpg';
$isOnline = $cardShowOnline ? !empty($user['is_online']) : false;

if (($age === '' || $age === null) && !empty($user['DOB'])) {
    try {
        $age = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
    } catch (Throwable $e) {
        $age = '';
    }
}

/* מספר הודעות חדשות לבועה הקטנה */
$unreadBadgeCount = 0;
if ($cardTopBadge !== '') {
    $digits = preg_replace('/[^\d]/', '', (string)$cardTopBadge);
    $unreadBadgeCount = (int)($digits ?: 0);
}

/* =========================
   אייקוני קשרים - 4 שאילתות
   ========================= */
$viewed_me    = false; // היא צפתה בי
$viewed_by_me = false; // אני צפיתי בה
$msg_in       = false; // היא שלחה לי
$msg_out      = false; // אני שלחתי לה

if (!empty($pdo) && $id > 0 && !empty($session_user_id) && (int)$session_user_id > 0) {
    try {
        /* 1) היא צפתה בי */
        $stmtViewIn = $pdo->prepare("
            SELECT 1
            FROM views
            WHERE Id = :me
              AND ById = :other
              AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
            LIMIT 1
        ");
        $stmtViewIn->execute([
            ':me'    => (int)$session_user_id,
            ':other' => $id
        ]);
        $viewed_me = (bool)$stmtViewIn->fetchColumn();

        /* 2) אני צפיתי בה */
        $stmtViewOut = $pdo->prepare("
            SELECT 1
            FROM views
            WHERE Id = :other
              AND ById = :me
              AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
            LIMIT 1
        ");
        $stmtViewOut->execute([
            ':me'    => (int)$session_user_id,
            ':other' => $id
        ]);
        $viewed_by_me = (bool)$stmtViewOut->fetchColumn();

        /* 3) היא שלחה לי הודעה */
        $stmtMsgIn = $pdo->prepare("
            SELECT 1
            FROM messages
            WHERE Id = :me
              AND ById = :other
              AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
            LIMIT 1
        ");
        $stmtMsgIn->execute([
            ':me'    => (int)$session_user_id,
            ':other' => $id
        ]);
        $msg_in = (bool)$stmtMsgIn->fetchColumn();

        /* 4) אני שלחתי לה הודעה */
        $stmtMsgOut = $pdo->prepare("
            SELECT 1
            FROM messages
            WHERE Id = :other
              AND ById = :me
              AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
            LIMIT 1
        ");
        $stmtMsgOut->execute([
            ':me'    => (int)$session_user_id,
            ':other' => $id
        ]);
        $msg_out = (bool)$stmtMsgOut->fetchColumn();
    } catch (Throwable $e) {
        $viewed_me = false;
        $viewed_by_me = false;
        $msg_in = false;
        $msg_out = false;
    }
}
?>

<div class="view-card" <?= $cardId !== '' ? ' id="' . e($cardId) . '"' : '' ?>>

    <div class="view-card-media">
        <img src="<?= e($img) ?>" alt="<?= e($name) ?>" class="view-card-image">

        <?php if ($isOnline): ?>
            <span class="online-badge"></span>
        <?php endif; ?>
    </div>

    <div class="view-card-content">

        <?php if ($viewed_me || $viewed_by_me || $msg_in || $msg_out): ?>
            <div class="view-card-icons">

                <?php if ($viewed_me): ?>
                    <span title="צפתה בי">↙️ 👁️</span>
                <?php endif; ?>

                <?php if ($viewed_by_me): ?>
                    <span title="צפיתי בה">↗️ 👁️</span>
                <?php endif; ?>

                <?php if ($msg_in): ?>
                    <span class="icon-with-badge" title="שלחה לי הודעה">
                        ↙️ 💬
                        <?php if ($unreadBadgeCount > 0): ?>
                            <span class="icon-badge"><?= $unreadBadgeCount ?></span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>

                <?php if ($msg_out): ?>
                    <span title="שלחתי לה הודעה">↗️ 💬</span>
                <?php endif; ?>

            </div>
        <?php endif; ?>

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