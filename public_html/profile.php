<?php
// =======================
// FILE: profile.php
// =======================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<main class='page-shell'><p>משתמש לא נמצא</p></main>";
    return;
}

$isOwnProfile = !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id;
$editMode = $isOwnProfile && isset($_GET['edit']);

/* =========================================
   עזר להצגת שדות ריקים
========================================= */
function showField($value): string
{
    return ($value !== null && $value !== '')
        ? htmlspecialchars((string)$value)
        : '<span class="empty-field">טרם מולא</span>';
}

/* =========================================
   צד ימין
========================================= */

$img  = !empty($user['Image']) ? $user['Image'] : 'no_photo.jpg';
$name = trim((string)($user['Name'] ?? ''));

$age = '';
if (!empty($user['DOB'])) {
    $age = (string)date_diff(date_create($user['DOB']), date_create('today'))->y;
}

$place        = $user['Place_Str'] ?? ($user['place'] ?? '');
$height       = $user['height'] ?? '';
$smoking      = $user['smoking_habbit'] ?? '';
$familyStatus = $user['family_status'] ?? '';
$religion     = $user['religion'] ?? '';
$religionRef  = $user['religion_ref'] ?? '';
$zodiac       = $user['ZODIAC'] ?? '';
$vegetrain    = $user['Vegitrain_Str'] ?? '';

$childrenRaw = $user['childs_num'] ?? '';
if ($childrenRaw === '' || $childrenRaw === null) {
    $childrenCount = '<span class="empty-field">טרם מולא</span>';
} else {
    $childrenCount = ((int)$childrenRaw > 0)
        ? htmlspecialchars((string)$childrenRaw . '+')
        : '0';
}

/* =========================================
   צד שמאל
========================================= */

function profileText(?string $value, string $default = 'טרם מולא'): string
{
    $v = trim((string)$value);
    return ($v !== '') ? htmlspecialchars($v) : $default;
}

$leftBlocks = [
    [
        'title' => 'קצת על עצמי',
        'field' => 'Who_Am_I',
        'value' => profileText($user['Who_Am_I'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'מה אני מחפש',
        'field' => 'I_Looking_For',
        'value' => profileText($user['I_Looking_For'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'מערכת יחסים שהייתי רוצה',
        'field' => 'Ideal_Relation_Is',
        'value' => profileText($user['Ideal_Relation_Is'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'תחביבים',
        'field' => 'Hobbies',
        'value' => profileText($user['Hobbies'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'בילוי מועדף',
        'field' => 'Spending',
        'value' => profileText($user['Spending'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'סרטים אהובים',
        'field' => 'Favorite_Movies',
        'value' => profileText($user['Favorite_Movies'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'תוכניות טלוויזיה אהובות',
        'field' => 'Favorite_TV',
        'value' => profileText($user['Favorite_TV'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'ספרים',
        'field' => 'Favorite_Books',
        'value' => profileText($user['Favorite_Books'] ?? '', 'טרם מולא')
    ],
    [
        'title' => 'מוזיקה',
        'field' => 'Favorite_Music',
        'value' => profileText($user['Favorite_Music'] ?? '', 'טרם מולא')
    ],
];
?>

<main class="page-shell">
    <div class="profile-wrap">

        <section class="profile-left">
            <?php foreach ($leftBlocks as $block): ?>
                <div class="profile-block editable-block"
                     data-field="<?= htmlspecialchars($block['field']) ?>"
                     data-user-id="<?= (int)$id ?>">

                    <div class="profile-block-head">
                        <h3><?= htmlspecialchars($block['title']) ?></h3>

                        <?php if ($editMode): ?>
                            <button type="button" class="edit-btn" title="עריכה">✎</button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-view">
                        <?= nl2br($block['value']) ?>
                    </div>

                    <?php if ($editMode): ?>
                        <div class="profile-edit" style="display:none;">
                            <textarea class="edit-textarea"><?= htmlspecialchars(trim((string)($user[$block['field']] ?? ''))) ?></textarea>

                            <div class="edit-actions">
                                <button type="button" class="save-btn">שמירה</button>
                                <a href="#" class="cancel-btn">ביטול</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <aside class="profile-right">
            <div class="profile-card">
                <img
                    src="/images/<?= htmlspecialchars($img) ?>"
                    alt="<?= htmlspecialchars($name) ?>"
                    class="profile-card-img"
                >

                <div class="profile-card-content">
                    <h2 class="profile-name"><?= htmlspecialchars($name) ?></h2>

                    <div class="profile-row">
                        <strong>גיל:</strong>
                        <span><?= ($age !== '') ? htmlspecialchars($age) : '<span class="empty-field">טרם מולא</span>' ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>מקום מגורים:</strong>
                        <span><?= showField($place) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>גובה:</strong>
                        <span><?= showField($height) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>עישון:</strong>
                        <span><?= showField($smoking) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>צמחונות:</strong>
                        <span><?= showField($vegetrain) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>מצב משפחתי:</strong>
                        <span><?= showField($familyStatus) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>דת:</strong>
                        <span><?= showField($religion) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>דתיות:</strong>
                        <span><?= showField($religionRef) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>מזל:</strong>
                        <span><?= showField($zodiac) ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>ילדים:</strong>
                        <span><?= ($childrenRaw !== '' && $childrenRaw !== null && (int)$childrenRaw > 0) ? 'יש' : (($childrenRaw !== '' && $childrenRaw !== null) ? 'אין' : '<span class="empty-field">טרם מולא</span>') ?></span>
                    </div>

                    <div class="profile-row">
                        <strong>מספר ילדים:</strong>
                        <span><?= $childrenCount ?></span>
                    </div>
                </div>
            </div>
        </aside>

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
                view.innerHTML = value.trim() !== ''
                    ? value.replace(/\n/g, '<br>')
                    : '<span class="empty-field">טרם מולא</span>';

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