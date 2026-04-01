<?php

// =======================
// FILE: profile.php
// =======================

require_once __DIR__ . '/config/config.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = ?");
$stmt->execute([(int)$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("משתמש לא נמצא");
}

$isOwnProfile = !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$id;
$editMode = $isOwnProfile && isset($_GET['edit']);

/* ===== צד ימין ===== */

$img  = !empty($user['Image']) ? $user['Image'] : 'no_photo.jpg';
$name = $user['Name'] ?? '';

$age = '';
if (!empty($user['DOB'])) {
    $age = (string) date_diff(date_create($user['DOB']), date_create('today'))->y;
}

$zodiac       = trim((string)($user['ZODIAC'] ?? ''));
$familyStatus = trim((string)($user['family_status'] ?? ''));
$religion     = trim((string)($user['religion'] ?? ''));
$religionRef  = trim((string)($user['religion_ref'] ?? ''));
$place        = trim((string)($user['place'] ?? ''));
$height       = trim((string)($user['height'] ?? ''));
$smoking      = trim((string)($user['smoking_habbit'] ?? ''));

$childrenRaw = $user['childs_num'] ?? '';
if ($childrenRaw === '' || $childrenRaw === null) {
    $children = '';
} else {
    $children = ((int)$childrenRaw > 0) ? $childrenRaw . '+' : '0';
}

/* ===== צד שמאל ===== */

function profile_text(?string $value = null, string $default = 'טרם השיב/ה'): string
{
    $v = trim((string)$value);
    return (mb_strlen($v) < 4) ? $default : $v;
}

$whoAmI          = profile_text($user['Who_Am_I'] ?? '');
$iLookingFor     = profile_text($user['I_Looking_For'] ?? '');
$idealRelationIs = profile_text($user['Ideal_Relation_Is'] ?? '', 'אספר בהמשך');
$hobbies         = profile_text($user['Hobbies'] ?? '');
$spending        = profile_text($user['Spending'] ?? '');
$favoritemovies  = profile_text($user['Favorite_Movies'] ?? '');
$favoriteTV      = profile_text($user['Favorite_TV'] ?? '');
$favoritebooks   = profile_text($user['Favorite_Books'] ?? '');
$favoritemusic   = profile_text($user['Favorite_Music'] ?? '');

$leftBlocks = [
    ['title' => 'קצת על עצמי', 'field' => 'Who_Am_I', 'value' => $whoAmI],
    ['title' => 'רוצה להכיר', 'field' => 'I_Looking_For', 'value' => $iLookingFor],
    ['title' => 'מערכת יחסים שהייתי רוצה', 'field' => 'Ideal_Relation_Is', 'value' => $idealRelationIs],
    ['title' => 'תחביבים', 'field' => 'Hobbies', 'value' => $hobbies],
    ['title' => 'בילוי מועדף', 'field' => 'Spending', 'value' => $spending],
    ['title' => 'סרטים אהובים', 'field' => 'Favorite_Movies', 'value' => $favoritemovies],
    ['title' => 'תוכניות טלוויזיה אהובות', 'field' => 'Favorite_TV', 'value' => $favoriteTV],
    ['title' => 'ספרים', 'field' => 'Favorite_Books', 'value' => $favoritebooks],
    ['title' => 'מוזיקה', 'field' => 'Favorite_Music', 'value' => $favoritemusic],
];
?>
<main class="page-shell">

<div class="profile-wrap">

    <div class="profile-right">
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
            </div>
        </div>
    </div>

    <div class="profile-left">
        <?php foreach ($leftBlocks as $block): ?>
            <div
                class="profile-block editable-block"
                data-field="<?= htmlspecialchars($block['field']) ?>"
                data-user-id="<?= (int)$id ?>"
            >
                <div class="profile-block-head">
                    <?php if ($editMode): ?>
                        <button type="button" class="edit-btn" title="עריכה">✎</button>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($block['title']) ?></h3>
                </div>

                <div class="profile-view">
                    <?= nl2br(htmlspecialchars($block['value'])) ?>
                </div>

                <?php if ($editMode): ?>
                    <div class="profile-edit" style="display:none;">
                        <textarea class="edit-textarea"><?= htmlspecialchars($block['value']) ?></textarea>

                        <div class="edit-actions">
                            <button type="button" class="save-btn">שמירה</button>
                            <a href="#" class="cancel-btn">ביטול</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>
</main>
<?php if ($editMode): ?>
<script>
document.addEventListener('click', async function (e) {
    const editBtn = e.target.closest('.edit-btn');
    const saveBtn = e.target.closest('.save-btn');
    const cancelBtn = e.target.closest('.cancel-btn');

    if (!editBtn && !saveBtn && !cancelBtn) return;

    const block = e.target.closest('.editable-block');
    if (!block) return;

    const view = block.querySelector('.profile-view');
    const edit = block.querySelector('.profile-edit');
    const textarea = block.querySelector('.edit-textarea');

    if (editBtn) {
        e.preventDefault();
        view.style.display = 'none';
        edit.style.display = 'block';
        textarea.focus();
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        return;
    }

    if (cancelBtn) {
        e.preventDefault();
        textarea.value = view.innerText.trim();
        edit.style.display = 'none';
        view.style.display = 'block';
        return;
    }

    if (saveBtn) {
        e.preventDefault();

        const userId = block.dataset.userId;
        const field = block.dataset.field;
        const value = textarea.value;

        const formData = new FormData();
        formData.append('id', userId);
        formData.append('field', field);
        formData.append('value', value);

        try {
            const response = await fetch('/save_profile_field.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.ok) {
                view.innerHTML = value.replace(/\n/g, '<br>');
                edit.style.display = 'none';
                view.style.display = 'block';
            } else {
                alert(data.message || 'שגיאה בשמירה');
            }
        } catch (err) {
            alert('שגיאת תקשורת');
        }
    }
});
</script>
<?php endif; ?>