<?php
/* =========================
   profile.php
   ========================= */

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileFields = require __DIR__ . '/profile_fields.php';

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
    $sessionUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $profileUserId = isset($user['Id']) ? (int)$user['Id'] : 0;

    return $sessionUserId > 0 && $sessionUserId === $profileUserId;
}

function get_options(PDO $pdo, array $cfg): array
{
    if (
        empty($cfg['table']) ||
        empty($cfg['column']) ||
        !in_array(($cfg['type'] ?? ''), ['select'], true)
    ) {
        return [];
    }

    $table = $cfg['table'];
    $column = $cfg['column'];

    $allowedMaps = [
        'gender'           => 'Gender_Str',
        'age'              => 'Age_Str',
        'occupation'       => 'Occupation_Str',
        'education'        => 'Education_Str',
        'place'            => 'Place_Str',
        'family_status'    => 'Family_Status_Str',
        'childs_num'       => 'Childs_Num_Str',
        'religion'         => 'Religion_Str',
        'religion_ref'     => 'Religion_Ref_Str',
        'smoking_habbit'   => 'Smoking_Habbit_Str',
        'drinking_habbit'  => 'Drinking_Habbit_Str',
        'vegitrain'        => 'Vegitrain_Str',
        'height'           => 'Height_Str',
        'hair_color'       => 'Hair_Color_Str',
        'hair_type'        => 'Hair_Type_Str',
        'body_type'        => 'Body_Type_Str',
        'look_type'        => 'Look_Type_Str',
        'zone'             => 'Zone_Str',
    ];

    if (!isset($allowedMaps[$table]) || $allowedMaps[$table] !== $column) {
        return [];
    }

    try {
        $stmt = $pdo->query("
            SELECT {$column}
            FROM {$table}
            WHERE {$column} IS NOT NULL
              AND {$column} <> ''
            ORDER BY {$column} ASC
        ");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (Throwable $e) {
        return [];
    }
}

function detect_birthdate_value(array $user): string
{
    $possibleFields = [
        'Birth_Date',
        'BirthDate',
        'Date_Of_Birth',
        'DOB',
        'Birthday',
        'BDate',
        'Birth_Dt'
    ];

    foreach ($possibleFields as $field) {
        if (!empty($user[$field])) {
            return trim((string)$user[$field]);
        }
    }

    return '';
}

function compute_age_from_birthdate(array $user): string
{
    $birthDate = detect_birthdate_value($user);

    if ($birthDate === '') {
        return '';
    }

    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime('today');
        return (string)$birth->diff($today)->y;
    } catch (Throwable $e) {
        return '';
    }
}

function format_profile_display_value(string $field, string $value, array $cfg = []): string
{
    $value = trim($value);

    if (!empty($cfg['zero_as_none'])) {
        if ($value === '0' || $value === '0 ילדים') {
            return 'ללא';
        }
    }

    return $value;
}

/* -----------------------------
   חלוקת שדות לימין/שמאל
----------------------------- */
$rightFields = [];
$leftFields = [];

foreach ($profileFields as $field => $cfg) {
    if (($cfg['side'] ?? '') === 'right') {
        $rightFields[$field] = $cfg;
    } elseif (($cfg['side'] ?? '') === 'left') {
        $leftFields[$field] = $cfg;
    }
}

/* -----------------------------
   קבלת מזהה משתמש
----------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

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

$isOwner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$user['Id'];

/* -----------------------------
   שמירת צפייה
   Id   = מי שנצפה
   ById = מי שצפה
----------------------------- */
if ($viewerId > 0 && $viewerId !== (int)$user['Id']) {
    $deleteViewStmt = $pdo->prepare("
        DELETE FROM views
        WHERE Id = :viewed_id
          AND ById = :viewer_id
    ");
    $deleteViewStmt->execute([
        ':viewed_id' => (int)$user['Id'],
        ':viewer_id' => $viewerId
    ]);

    $insertViewStmt = $pdo->prepare("
        INSERT INTO views (Id, ById, Date, New)
        VALUES (:viewed_id, :viewer_id, NOW(), 1)
    ");
    $insertViewStmt->execute([
        ':viewed_id' => (int)$user['Id'],
        ':viewer_id' => $viewerId
    ]);
}

$editMode = $isOwner && isset($_GET['edit']) && (int)$_GET['edit'] === 1;

/* -----------------------------
   תמונת פרופיל
----------------------------- */
$profileImage = '/images/no_photo.jpg';

$picStmt = $pdo->prepare("
    SELECT Pic_Name
    FROM user_pics
    WHERE Id = :id
      AND Main_Pic = 1
      AND Pic_Status = 1
    LIMIT 1
");
$picStmt->execute([':id' => $id]);
$picRow = $picStmt->fetch(PDO::FETCH_ASSOC);

if ($picRow && !empty($picRow['Pic_Name'])) {
    $profileImage = '/upload/' . $picRow['Pic_Name'];
}

/* -----------------------------
   שם
----------------------------- */
$displayName = profile_value($user, 'Name');
if ($displayName === '') {
    $displayName = 'ללא שם';
}
?>


<div class="page-shell">
    <div class="profile-page">

        <div class="profile-left">
            <?php foreach ($leftFields as $field => $cfg): ?>
                <?php
                $title = $cfg['label'] ?? $field;
                $type = $cfg['type'] ?? 'textarea';
                $value = profile_value($user, $field);

                $displayValue = $value !== ''
                    ? nl2br(e($value))
                    : '<span style="color:#999;">אין מידע עדיין</span>';
                ?>
                <div class="profile-block" data-field="<?= e($field) ?>">
                    <div class="profile-block-head">
                        <h3><?= e($title) ?></h3>

                        <?php if ($isOwner): ?>
                            <button type="button" class="edit-btn left-edit-btn">✎</button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-block-body">
                        <div class="left-view-mode">
                            <?= $displayValue ?>
                        </div>

                        <?php if ($isOwner): ?>
                            <div class="left-edit-mode" style="display:none;">
                                <?php if ($type === 'input'): ?>
                                    <input type="text" class="profile-left-edit-input" value="<?= e($value) ?>">
                                <?php else: ?>
                                    <textarea class="profile-left-edit-textarea"><?= e($value) ?></textarea>
                                <?php endif; ?>

                                <div class="profile-left-edit-actions">
                                    <button type="button" class="profile-left-save-btn">שמירה</button>
                                    <button type="button" class="profile-left-cancel-btn">ביטול</button>
                                </div>

                                <div class="profile-left-edit-status"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <aside class="profile-right">
            <div class="profile-card">
                <div class="profile-card-image-wrap">
                    <img src="<?= e($profileImage) ?>" alt="<?= e($displayName) ?>" class="profile-card-image">
                </div>

                <div class="profile-card-main">
                    <?php $age = compute_age_from_birthdate($user); ?>

                    <h1 class="profile-card-name">
                        <?= e($displayName) ?>
                        <?php if ($age !== ''): ?>
                            <span class="profile-age">, <?= e($age) ?></span>
                        <?php endif; ?>
                    </h1>
                </div>

                <div class="profile-card-actions">
                    <?php if (!$isOwner): ?>
                        <button type="button" class="profile-message-btn" onclick="openChatWithUser(<?= (int)$user['Id'] ?>)">
                            שלח הודעה
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($isOwner && !$editMode): ?>
                    <div class="profile-right-edit-link-wrap">
                        <a href="?page=profile&id=<?= (int)$user['Id'] ?>&edit=1" class="profile-right-edit-link">
                            ✏️ עריכת פרטים  נוספים
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!$editMode): ?>
                    <div class="profile-info-list">
                        <?php foreach ($rightFields as $field => $cfg): ?>
                            <?php
                            $label = $cfg['label'] ?? $field;

                            if ($field === 'Age_Computed') {
                                $value = compute_age_from_birthdate($user);
                            } else {
                                $value = profile_value($user, $field);
                            }

                            $displayValue = format_profile_display_value($field, $value, $cfg);

                            if ($displayValue === '') {
                                continue;
                            }
                            ?>
                            <div class="profile-info-row">
                                <div class="profile-info-label"><?= e($label) ?>:</div>
                                <div class="profile-info-value-wrap">
                                    <div class="profile-info-value"><?= e($displayValue) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <form id="profileRightForm" class="profile-right-form">
                        <?php foreach ($rightFields as $field => $cfg): ?>
                            <?php
                            $label = $cfg['label'] ?? $field;
                            $type = $cfg['type'] ?? 'input';

                            if ($field === 'Age_Computed') {
                                $value = compute_age_from_birthdate($user);
                            } else {
                                $value = profile_value($user, $field);
                            }

                            $options = $type === 'select' ? get_options($pdo, $cfg) : [];
                            $readOnly = !empty($cfg['read_only']);
                            $displayValue = format_profile_display_value($field, $value, $cfg);
                            ?>
                            <div class="profile-right-edit-row">
                                <label class="profile-right-edit-label" for="field_<?= e($field) ?>"><?= e($label) ?></label>

                                <div class="profile-right-edit-control">
                                    <?php if ($readOnly): ?>
                                        <div class="profile-right-input profile-right-readonly">
                                            <?= e($displayValue) ?>
                                        </div>
                                    <?php elseif ($type === 'select'): ?>
                                        <div class="profile-right-select-wrap">
                                            <select
                                                id="field_<?= e($field) ?>"
                                                name="<?= e($field) ?>"
                                                class="profile-right-input profile-right-select">
                                                <option value="">בחר</option>
                                                <?php foreach ($options as $opt): ?>
                                                    <option value="<?= e($opt) ?>" <?= $opt === $value ? 'selected' : '' ?>>
                                                        <?= e($opt) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input
                                            id="field_<?= e($field) ?>"
                                            name="<?= e($field) ?>"
                                            type="text"
                                            class="profile-right-input"
                                            value="<?= e($value) ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="profile-right-actions">
                            <button type="button" id="saveRightProfileBtn" class="profile-right-save-btn">שמירה</button>
                            <a href="?page=profile&id=<?= (int)$user['Id'] ?>" class="profile-right-cancel-btn">ביטול</a>
                        </div>

                        <div id="profileRightEditStatus" class="profile-right-edit-status"></div>
                    </form>
                <?php endif; ?>
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
    const leftEditBtn = e.target.closest('.left-edit-btn');
    const leftCancelBtn = e.target.closest('.profile-left-cancel-btn');
    const leftSaveBtn = e.target.closest('.profile-left-save-btn');

    if (leftEditBtn) {
        const block = leftEditBtn.closest('.profile-block');
        if (!block) return;

        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');

        if (viewMode) viewMode.style.display = 'none';
        if (editMode) editMode.style.display = 'block';
        leftEditBtn.style.display = 'none';
        return;
    }

    if (leftCancelBtn) {
        const block = leftCancelBtn.closest('.profile-block');
        if (!block) return;

        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');
        const editBtn = block.querySelector('.left-edit-btn');
        const input = block.querySelector('.profile-left-edit-input');
        const textarea = block.querySelector('.profile-left-edit-textarea');
        const status = block.querySelector('.profile-left-edit-status');

        if (input) input.value = input.defaultValue;
        if (textarea) textarea.value = textarea.defaultValue;
        if (status) status.textContent = '';

        if (editMode) editMode.style.display = 'none';
        if (viewMode) viewMode.style.display = 'block';
        if (editBtn) editBtn.style.display = 'inline-block';
        return;
    }

    if (leftSaveBtn) {
        const block = leftSaveBtn.closest('.profile-block');
        if (!block) return;

        const field = block.dataset.field;
        const input = block.querySelector('.profile-left-edit-input');
        const textarea = block.querySelector('.profile-left-edit-textarea');
        const status = block.querySelector('.profile-left-edit-status');
        const editBtn = block.querySelector('.left-edit-btn');
        const viewMode = block.querySelector('.left-view-mode');
        const editMode = block.querySelector('.left-edit-mode');

        let value = '';
        if (input) value = input.value;
        if (textarea) value = textarea.value;

        if (status) status.textContent = 'שומר...';

        try {
            const response = await fetch('save_profile_field.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({
                    id: '<?= (int)$user['Id'] ?>',
                    field: field,
                    value: value
                }).toString()
            });

            const result = await response.json();

            if (!result.ok) {
                if (status) status.textContent = result.message || 'שמירה נכשלה';
                return;
            }

            if (viewMode) {
                viewMode.innerHTML = value.trim() !== ''
                    ? value
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;')
                        .replace(/\n/g, '<br>')
                    : '<span style="color:#999;">אין מידע עדיין</span>';
            }

            if (input) input.defaultValue = value;
            if (textarea) textarea.defaultValue = value;

            if (status) status.textContent = '';
            if (editMode) editMode.style.display = 'none';
            if (viewMode) viewMode.style.display = 'block';
            if (editBtn) editBtn.style.display = 'inline-block';
        } catch (err) {
            if (status) status.textContent = 'אירעה שגיאה בשמירה';
            console.error(err);
        }
    }
});

const saveRightProfileBtn = document.getElementById('saveRightProfileBtn');

if (saveRightProfileBtn) {
    saveRightProfileBtn.addEventListener('click', async function () {
        const status = document.getElementById('profileRightEditStatus');
        const form = document.getElementById('profileRightForm');
        const fields = form ? form.querySelectorAll('input[name], select[name]') : [];

        if (status) status.textContent = 'שומר...';

        try {
            for (const field of fields) {
                const response = await fetch('save_profile_field.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        id: '<?= (int)$user['Id'] ?>',
                        field: field.name,
                        value: field.value
                    }).toString()
                });

                const result = await response.json();

                if (!result.ok) {
                    if (status) status.textContent = result.message || ('שמירה נכשלה בשדה: ' + field.name);
                    return;
                }
            }

            window.location.href = '?page=profile&id=<?= (int)$user['Id'] ?>';
        } catch (err) {
            if (status) status.textContent = 'אירעה שגיאה בשמירה';
            console.error(err);
        }
    });
}
</script>