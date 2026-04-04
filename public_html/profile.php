<?php
// ===== FILE: profile.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   profile fields config
========================= */
$profileFields = [];
$profileFieldsFile = __DIR__ . '/profile_fields.php';
if (file_exists($profileFieldsFile)) {
    $tmpFields = require $profileFieldsFile;
    if (is_array($tmpFields)) {
        $profileFields = $tmpFields;
    }
}

/* =========================
   helpers
========================= */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function profile_value(array $user, string $field): string {
    return isset($user[$field]) && $user[$field] !== null ? trim((string)$user[$field]) : '';
}

function detect_birthdate_value(array $user): string {
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

function compute_age_from_birthdate(array $user): string {
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

function format_profile_display_value(string $field, string $value, array $cfg = []): string {
    $value = trim($value);

    if (!empty($cfg['zero_as_none'])) {
        if ($value === '0' || $value === '0 ילדים') {
            return 'ללא';
        }
    }

    return $value;
}

function get_options(PDO $pdo, array $cfg): array {
    if (
        empty($cfg['table']) ||
        empty($cfg['column']) ||
        (($cfg['type'] ?? '') !== 'select')
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

/* =========================
   split fields by side
========================= */
$rightFields = [];
$leftFields  = [];

foreach ($profileFields as $field => $cfg) {
    if (($cfg['side'] ?? '') === 'right') {
        $rightFields[$field] = $cfg;
    } elseif (($cfg['side'] ?? '') === 'left') {
        $leftFields[$field] = $cfg;
    }
}

/* =========================
   ids
========================= */
$id = (int)($_GET['id'] ?? 0);
$viewerId = (int)($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    return;
}

/* =========================
   fetch user
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM users_profile
    WHERE Id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='page-shell'>משתמש לא נמצא</div>";
    return;
}

$isOwner = ($viewerId > 0 && $viewerId === (int)$user['Id']);

/* =========================
   save view
========================= */
if ($viewerId > 0 && $viewerId !== (int)$user['Id']) {
    try {
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
    } catch (Throwable $e) {
        // keep page alive
    }
}

/* =========================
   profile image
========================= */
$profileImage = '/images/no_photo.jpg';

try {
    $picStmt = $pdo->prepare("
        SELECT Pic_Name
        FROM user_pics
        WHERE Id = :id
          AND Main_Pic = 1
          AND Pic_Status = 1
        LIMIT 1
    ");
    $picStmt->execute([':id' => (int)$user['Id']]);
    $picName = $picStmt->fetchColumn();

    if ($picName) {
        $profileImage = '/uploads/' . ltrim((string)$picName, '/');
    }
} catch (Throwable $e) {
    // use default image
}

/* =========================
   title
========================= */
$name = trim((string)($user['Name'] ?? ''));
$age  = compute_age_from_birthdate($user);
$mainTitle = $name . ($age !== '' ? ', ' . $age : '');

/* =========================
   prebuild right-side values
========================= */
$rightRows = [];
foreach ($rightFields as $field => $cfg) {
    $value = ($field === 'Age_Computed')
        ? compute_age_from_birthdate($user)
        : profile_value($user, $field);

    $value = format_profile_display_value($field, $value, $cfg);

    if ($value === '') {
        continue;
    }

    $rightRows[] = [
        'field' => $field,
        'label' => $cfg['label'] ?? $field,
        'value' => $value,
    ];
}
?>

<div class="page-shell profile-shell">
    <div class="profile-layout">

        <!-- LEFT SIDE -->
        <section class="profile-left-col">
            <?php
            $hasLeftContent = false;
            foreach ($leftFields as $field => $cfg):
                $value = profile_value($user, $field);
                $fieldType = $cfg['type'] ?? 'input';
                $hasLeftContent = true;
            ?>
                <div class="profile-left-card" data-field="<?= e($field) ?>">
                    <div class="profile-left-card-head">
                        <h3><?= e($cfg['label'] ?? $field) ?></h3>

                        <?php if ($isOwner && empty($cfg['read_only'])): ?>
                            <button type="button" class="profile-inline-edit-btn" title="עריכה">✎</button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-left-view<?= $value === '' ? ' is-empty' : '' ?>">
                        <?php if ($value !== ''): ?>
                            <?= nl2br(e($value)) ?>
                        <?php else: ?>
                            עדיין לא מולא.
                        <?php endif; ?>
                    </div>

                    <?php if ($isOwner && empty($cfg['read_only'])): ?>
                        <form class="profile-left-edit" action="/save_profile_field.php" method="POST" style="display:none;">
                            <input type="hidden" name="id" value="<?= (int)$user['Id'] ?>">
                            <input type="hidden" name="field" value="<?= e($field) ?>">

                            <?php if ($fieldType === 'textarea'): ?>
                                <textarea name="value" class="profile-edit-textarea"><?= e($value) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="value" value="<?= e($value) ?>" class="profile-edit-input">
                            <?php endif; ?>

                            <div class="profile-edit-actions">
                                <button type="button" class="profile-cancel-btn">בטל</button>
                                <button type="submit" class="profile-save-btn">שמור</button>
                            </div>

                            <div class="profile-inline-status"></div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasLeftContent): ?>
                <div class="profile-left-card">
                    <div class="profile-left-card-head">
                        <h3>אין עדיין מידע נוסף</h3>
                    </div>
                    <div class="profile-left-view is-empty">
                        הפרופיל הזה עדיין לא מולא במלואו.
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- RIGHT SIDE -->
        <aside class="profile-right-col">
            <div class="profile-right-card">

                <div class="profile-main-image-wrap">
                    <img src="<?= e($profileImage) ?>" alt="<?= e($name) ?>" class="profile-main-image">
                </div>

                <h2 class="profile-main-title"><?= e($mainTitle) ?></h2>

                <button
                    type="button"
                    class="profile-main-btn <?= $isOwner ? 'profile-send-btn-disabled' : 'open-chat-btn' ?>"
                    <?= $isOwner ? '' : ' data-user-id="' . (int)$user['Id'] . '"' ?>>
                    שליחה <span class="profile-main-btn-icon">✉</span>
                </button>

                <?php if ($isOwner): ?>
                    <button class="profile-right-edit-link profile-right-toggle-btn">
                        <span class="edit-icon">✎</span>
                        <span>ערוך פרטים נוספים</span>
                    </button>
                <?php endif; ?>

                <div class="profile-right-facts profile-right-view-mode">
                    <?php foreach ($rightRows as $row): ?>
                        <div class="profile-right-row">
                            <span class="profile-right-label"><?= e($row['label']) ?>:</span>
                            <span class="profile-right-value"><?= e($row['value']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($isOwner): ?>
                    <form class="profile-right-edit-form" style="display:none;">
                        <?php foreach ($rightFields as $field => $cfg): ?>
                            <?php
                            if (!empty($cfg['read_only'])) {
                                continue;
                            }

                            $label = $cfg['label'] ?? $field;
                            $type = $cfg['type'] ?? 'input';
                            $rawValue = profile_value($user, $field);
                            $isPlaceholder = ($rawValue === '');
                            ?>
                            <div class="profile-right-edit-row">
                                <label class="profile-right-edit-label"><?= e($label) ?>:</label>

                                <div class="profile-right-edit-control">
                                    <?php if ($type === 'select'): ?>
                                        <?php $options = get_options($pdo, $cfg); ?>
                                        <select name="<?= e($field) ?>" class="profile-right-select" <?= $isPlaceholder ? 'required' : '' ?>>
                                            <option value="" disabled <?= $isPlaceholder ? 'selected' : '' ?> hidden>בחר</option>
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?= e($option) ?>" <?= ($rawValue === (string)$option ? 'selected' : '') ?>>
                                                    <?= e($option) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input
                                            type="text"
                                            name="<?= e($field) ?>"
                                            value="<?= e($rawValue) ?>"
                                            class="profile-right-input">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="profile-right-edit-actions">
                            <button type="button" class="profile-right-cancel-btn">בטל</button>
                            <button type="button" class="profile-right-save-btn">שמור</button>
                        </div>

                        <div class="profile-right-edit-status"></div>
                    </form>
                <?php endif; ?>

            </div>
        </aside>

    </div>
</div>

<?php if ($isOwner): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* LEFT SIDE INLINE EDIT */
            document.querySelectorAll('.profile-left-card').forEach(function(card) {
                const editBtn = card.querySelector('.profile-inline-edit-btn');
                const viewBox = card.querySelector('.profile-left-view');
                const editBox = card.querySelector('.profile-left-edit');
                const cancelBtn = card.querySelector('.profile-cancel-btn');
                const form = card.querySelector('.profile-left-edit');
                const statusBox = card.querySelector('.profile-inline-status');

                if (editBtn && viewBox && editBox) {
                    editBtn.addEventListener('click', function() {
                        viewBox.style.display = 'none';
                        editBox.style.display = 'block';
                        if (statusBox) statusBox.textContent = '';
                    });
                }

                if (cancelBtn && viewBox && editBox) {
                    cancelBtn.addEventListener('click', function() {
                        editBox.style.display = 'none';
                        viewBox.style.display = 'block';
                        if (statusBox) statusBox.textContent = '';
                    });
                }

                if (form) {
                    form.addEventListener('submit', function(ev) {
                        ev.preventDefault();

                        const formData = new FormData(form);

                        fetch('/save_profile_field.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(function(res) {
                                return res.json();
                            })
                            .then(function(data) {
                                if (!data.ok) {
                                    if (statusBox) {
                                        statusBox.textContent = data.message || 'שמירה נכשלה';
                                    }
                                    return;
                                }

                                const newValue = (data.value || '').trim();
                                if (newValue !== '') {
                                    viewBox.innerHTML = newValue.replace(/\n/g, '<br>');
                                    viewBox.classList.remove('is-empty');
                                } else {
                                    viewBox.textContent = 'עדיין לא מולא.';
                                    viewBox.classList.add('is-empty');
                                }

                                editBox.style.display = 'none';
                                viewBox.style.display = 'block';
                                if (statusBox) statusBox.textContent = '';
                            })
                            .catch(function() {
                                if (statusBox) {
                                    statusBox.textContent = 'שגיאת תקשורת';
                                }
                            });
                    });
                }
            });

            /* RIGHT SIDE INLINE EDIT */
            const rightCard = document.querySelector('.profile-right-card');
            if (rightCard) {
                const toggleBtn = rightCard.querySelector('.profile-right-toggle-btn');
                const viewMode = rightCard.querySelector('.profile-right-view-mode');
                const editForm = rightCard.querySelector('.profile-right-edit-form');
                const cancelBtn = rightCard.querySelector('.profile-right-cancel-btn');
                const saveBtn = rightCard.querySelector('.profile-right-save-btn');
                const statusBox = rightCard.querySelector('.profile-right-edit-status');

                if (toggleBtn && viewMode && editForm) {
                    toggleBtn.addEventListener('click', function() {
                        viewMode.style.display = 'none';
                        editForm.style.display = 'block';
                        if (statusBox) statusBox.textContent = '';
                    });
                }

                if (cancelBtn && viewMode && editForm) {
                    cancelBtn.addEventListener('click', function() {
                        editForm.style.display = 'none';
                        viewMode.style.display = 'block';
                        if (statusBox) statusBox.textContent = '';
                    });
                }

                if (saveBtn && viewMode && editForm) {
                    saveBtn.addEventListener('click', function() {
                        const controls = editForm.querySelectorAll('input[name], select[name]');
                        const userId = <?= (int)$user['Id'] ?>;
                        const requests = [];

                        if (statusBox) statusBox.textContent = 'שומר...';

                        controls.forEach(function(control) {
                            const formData = new FormData();
                            formData.append('id', userId);
                            formData.append('field', control.name);
                            formData.append('value', control.value);

                            requests.push(
                                fetch('/save_profile_field.php', {
                                    method: 'POST',
                                    body: formData
                                }).then(function(res) {
                                    return res.json();
                                })
                            );
                        });

                        Promise.all(requests)
                            .then(function(results) {
                                const failed = results.find(function(item) {
                                    return !item.ok;
                                });

                                if (failed) {
                                    if (statusBox) {
                                        statusBox.textContent = failed.message || 'שמירה נכשלה';
                                    }
                                    return;
                                }

                                window.location.reload();
                            })
                            .catch(function() {
                                if (statusBox) {
                                    statusBox.textContent = 'שגיאת תקשורת';
                                }
                            });
                    });
                }
            }
        });
    </script>
<?php endif; ?>

<?php if (!$isOwner && $viewerId > 0): ?>
    <?php if (!$isOwner && $viewerId > 0): ?>
        <script>
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.open-chat-btn');
                if (!btn) return;

                e.preventDefault();

                const userId = Number(btn.getAttribute('data-user-id'));
                if (!userId) return;

                if (typeof openMessageModal !== 'function') {
                    console.error('openMessageModal is not loaded');
                    return;
                }

                const userName = <?= json_encode($name !== '' ? $name : 'משתמש', JSON_UNESCAPED_UNICODE) ?>;
                const userImage = <?= json_encode($profileImage, JSON_UNESCAPED_UNICODE) ?>;

                openMessageModal(
                    userId,
                    userName,
                    userImage,
                    window.chatViewer ? window.chatViewer.name : 'אני',
                    window.chatViewer ? window.chatViewer.image : '/images/no_photo.jpg'
                );
            });
        </script>
    <?php endif; ?>
<?php endif; ?>