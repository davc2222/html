<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -----------------------------
   עזר
----------------------------- */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function profile_value(array $user, string $field): string
{
    return isset($user[$field]) && $user[$field] !== null ? trim((string)$user[$field]) : '';
}

function can_edit_profile(array $user): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    return (int)$_SESSION['user_id'] === (int)$user['Id'];
}

/* -----------------------------
   קבלת מזהה משתמש
----------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    exit;
}

/* -----------------------------
   שליפת משתמש
----------------------------- */
$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    exit;
}

$editMode = can_edit_profile($user) && isset($_GET['edit']) && (int)$_GET['edit'] === 1;

/* -----------------------------
   תמונת פרופיל
----------------------------- */
$profileImage = !empty($user['ProfileImage'])
    ? $user['ProfileImage']
    : (!empty($user['profile_image']) ? $user['profile_image'] : 'images/no_photo.jpg');

/* -----------------------------
   שם ותיאור קצר
----------------------------- */
$displayName = profile_value($user, 'Name');
if ($displayName === '') {
    $displayName = 'ללא שם';
}

function calculate_age_from_dob(?string $dob): string
{
    if (empty($dob)) {
        return '';
    }

    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        return (string)$today->diff($birthDate)->y;
    } catch (Exception $e) {
        return '';
    }
}

$age        = calculate_age_from_dob($user['DOB'] ?? '');
$city       = profile_value($user, 'Area');
$gender     = profile_value($user, 'Gender');
$lookingFor = profile_value($user, 'LookingFor');

/* -----------------------------
   שדות ימין
----------------------------- */
$rightFields = [
    'Age'            => 'גיל',
    'Gender'         => 'מין',
    'Area'           => 'מקום מיגורים',
    'Height'         => 'גובה',
    'BodyType'       => 'מבנה גוף',
    'Status'         => 'סטטוס',
    'Religion'       => 'דת',
    'Smoking'        => 'מעשן/ת',
    'Drinking'       => 'שותה/ה',
    'Children'       => 'ילדים',
    'Education'      => 'השכלה',
    'Occupation'     => 'עיסוק',
    'LookingFor'     => 'מחפש/ת',
    'Vegitrain_Str'  => 'תזונה'
];

/* -----------------------------
   בלוקים שמאל
----------------------------- */
$leftBlocks = [
    'AboutMe'        => 'קצת על עצמי',
    'MyMatch'        => 'מה אני מחפש/ת',
    'Hobbies'        => 'תחביבים',
    'FavoriteMusic'  => 'מוזיקה אהובה',
    'FavoriteFood'   => 'אוכל אהוב'
  
];
?>
<div class="page-shell">
    <div class="profile-page">

        <!-- צד שמאל -->
        <div class="profile-left">

            <?php foreach ($leftBlocks as $field => $title): ?>
    <?php
    $value = profile_value($user, $field);
    if ($value === '') continue;
    ?>
    <div class="profile-block" data-field="<?= e($field) ?>">
                    <div class="profile-block-head">
                        <h3><?= e($title) ?></h3>

                        <?php if ($editMode): ?>
                            <div class="profile-block-actions">
                                <button type="button" class="edit-btn">✎</button>
                                <button type="button" class="save-btn" style="display:none;">שמור</button>
                                <button type="button" class="cancel-btn" style="display:none;">ביטול</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-block-body">
                        <div class="view-mode">
                            <?= $value !== '' ? nl2br(e($value)) : '<span class="empty-text">לא הוזן מידע</span>' ?>
                        </div>

                        <?php if ($editMode): ?>
                            <div class="edit-mode" style="display:none;">
                                <textarea class="profile-textarea" rows="5"><?= e($value) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- צד ימין -->
        <aside class="profile-right">
            <div class="profile-card">
                <div class="profile-card-image-wrap">
                    <img src="<?= e($profileImage) ?>" alt="<?= e($displayName) ?>" class="profile-card-image">
                </div>

                <div class="profile-card-main">
                    <h1 class="profile-card-name"><?= e($displayName) ?></h1>

                

                <div class="profile-card-actions">
                    <?php if (!$editMode): ?>
                        <button type="button" class="profile-message-btn" onclick="openChatWithUser(<?= (int)$user['Id'] ?>)">
                            שלח הודעה
                        </button>
                    <?php else: ?>
                        <a class="profile-edit-link" href="?page=profile&id=<?= (int)$user['Id'] ?>">
                            סיום עריכה
                        </a>
                    <?php endif; ?>
                </div>

                <div class="profile-info-list">
                    <?php foreach ($rightFields as $field => $label): ?>
    <?php
    $value = profile_value($user, $field);
    if ($value === '') continue;
    ?>
    <div class="profile-info-row" data-field="<?= e($field) ?>">
                            <div class="profile-info-label"><?= e($label) ?>:</div>

                            <div class="profile-info-value-wrap">
                                <div class="view-mode profile-info-value">
                                    <?= $value !== '' ? e($value) : '<span class="empty-text">לא צוין</span>' ?>
                                </div>

                                <?php if ($editMode): ?>
                                    <div class="edit-mode" style="display:none;">
                                        <input type="text" class="profile-input" value="<?= e($value) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($editMode): ?>
                                <div class="profile-info-actions">
                                    <button type="button" class="edit-btn">✎</button>
                                    <button type="button" class="save-btn" style="display:none;">שמור</button>
                                    <button type="button" class="cancel-btn" style="display:none;">ביטול</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </aside>
    </div>
</div>

<script>
function openChatWithUser(userId) {
    const params = new URLSearchParams(window.location.search);
    params.set('chat_user', userId);

    if (!params.get('page')) {
        params.set('page', 'profile');
    }

    window.location.search = params.toString();
}

document.addEventListener('click', async function (e) {
    const editBtn = e.target.closest('.edit-btn');
    const saveBtn = e.target.closest('.save-btn');
    const cancelBtn = e.target.closest('.cancel-btn');

    if (editBtn) {
        const container = editBtn.closest('.profile-block, .profile-info-row');
        if (!container) return;

        const viewMode = container.querySelector('.view-mode');
        const editMode = container.querySelector('.edit-mode');

        if (viewMode) viewMode.style.display = 'none';
        if (editMode) editMode.style.display = 'block';

        const localEditBtn = container.querySelector('.edit-btn');
        const localSaveBtn = container.querySelector('.save-btn');
        const localCancelBtn = container.querySelector('.cancel-btn');

        if (localEditBtn) localEditBtn.style.display = 'none';
        if (localSaveBtn) localSaveBtn.style.display = 'inline-block';
        if (localCancelBtn) localCancelBtn.style.display = 'inline-block';
        return;
    }

    if (cancelBtn) {
        const container = cancelBtn.closest('.profile-block, .profile-info-row');
        if (!container) return;

        const viewMode = container.querySelector('.view-mode');
        const editMode = container.querySelector('.edit-mode');

        const textarea = container.querySelector('textarea');
        const input = container.querySelector('input');

        if (textarea) {
            textarea.value = textarea.defaultValue;
        }

        if (input) {
            input.value = input.defaultValue;
        }

        if (editMode) editMode.style.display = 'none';
        if (viewMode) viewMode.style.display = 'block';

        const localEditBtn = container.querySelector('.edit-btn');
        const localSaveBtn = container.querySelector('.save-btn');
        const localCancelBtn = container.querySelector('.cancel-btn');

        if (localEditBtn) localEditBtn.style.display = 'inline-block';
        if (localSaveBtn) localSaveBtn.style.display = 'none';
        if (localCancelBtn) localCancelBtn.style.display = 'none';
        return;
    }

    if (saveBtn) {
        const container = saveBtn.closest('.profile-block, .profile-info-row');
        if (!container) return;

        const field = container.dataset.field;
        if (!field) return;

        let value = '';
        const textarea = container.querySelector('textarea');
        const input = container.querySelector('input');

        if (textarea) {
            value = textarea.value;
        } else if (input) {
            value = input.value;
        }

        try {
            const response = await fetch('save_profile_field.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({
                    field: field,
                    value: value,
                    user_id: '<?= (int)$user['Id'] ?>'
                }).toString()
            });

            const result = await response.json();

            if (!result.success) {
                alert(result.message || 'שמירה נכשלה');
                return;
            }

            const viewMode = container.querySelector('.view-mode');
            const editMode = container.querySelector('.edit-mode');

            if (viewMode) {
                if (textarea) {
                    viewMode.innerHTML = value.trim() !== ''
                        ? value
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;')
                            .replace(/\n/g, '<br>')
                        : '<span class="empty-text">לא הוזן מידע</span>';

                    textarea.defaultValue = value;
                } else {
                    viewMode.innerHTML = value.trim() !== ''
                        ? value
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;')
                        : '<span class="empty-text">לא צוין</span>';

                    input.defaultValue = value;
                }
            }

            if (editMode) editMode.style.display = 'none';
            if (viewMode) viewMode.style.display = 'block';

            const localEditBtn = container.querySelector('.edit-btn');
            const localSaveBtn = container.querySelector('.save-btn');
            const localCancelBtn = container.querySelector('.cancel-btn');

            if (localEditBtn) localEditBtn.style.display = 'inline-block';
            if (localSaveBtn) localSaveBtn.style.display = 'none';
            if (localCancelBtn) localCancelBtn.style.display = 'none';

        } catch (err) {
            alert('אירעה שגיאה בשמירה');
            console.error(err);
        }
    }
});
</script>