
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
    function setMainPic(id) {
        fetch('/set_main_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'pic_num=' + encodeURIComponent(id)
        }).then(() => location.reload());
    }

    let rightEditMode = false;
    let rightOriginalValues = {};

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function restoreRightFields() {
        const rows = document.querySelectorAll('#profileRightFacts .profile-right-row');

        rows.forEach(function(row) {
            const field = row.getAttribute('data-field');
            const label = row.getAttribute('data-label') || '';
            const value = rightOriginalValues[field] || '';

            row.innerHTML = `
            <span class="profile-right-label">${escapeHtml(label)}:</span>
            <span class="profile-right-value">${value === '' ? 'לא מולא' : escapeHtml(value)}</span>
        `;
        });

        const actions = document.querySelector('#profileRightFacts .profile-right-edit-actions');
        if (actions) actions.remove();

        rightEditMode = false;
        bindProfileButtons();
    }

    function openLeftEditor(field) {
        const view = document.querySelector('.profile-left-view[data-field="' + field + '"]');
        if (!view) return;
        if (view.dataset.editing === '1') return;

        const currentText = view.innerText.trim() === 'לא מולא' ? '' : view.innerText.trim();

        view.dataset.editing = '1';
        view.dataset.original = currentText;

        view.innerHTML = `
        <textarea class="profile-edit-textarea js-inline-textarea">${escapeHtml(currentText)}</textarea>
        <div class="profile-edit-actions">
            <button type="button" class="profile-save-btn js-inline-save" data-field="${field}">שמור</button>
            <button type="button" class="profile-cancel-btn js-inline-cancel" data-field="${field}">ביטול</button>
        </div>
    `;

        bindProfileButtons();
    }

    function openRightEditor() {
        if (rightEditMode) return;

        const rows = document.querySelectorAll('#profileRightFacts .profile-right-row');
        if (!rows.length) return;

        rightOriginalValues = {};
        rightEditMode = true;

        rows.forEach(function(row) {
            const field = row.getAttribute('data-field');
            const label = row.getAttribute('data-label') || '';
            const valueEl = row.querySelector('.profile-right-value');
            const currentValue = valueEl ? valueEl.textContent.trim() : '';

            rightOriginalValues[field] = currentValue === 'לא מולא' ? '' : currentValue;

            row.innerHTML = `
            <span class="profile-right-label">${escapeHtml(label)}:</span>
            <input type="text" class="profile-right-input js-right-input" data-field="${escapeHtml(field)}" value="${escapeHtml(rightOriginalValues[field])}">
        `;
        });

        const factsBox = document.getElementById('profileRightFacts');
        if (factsBox && !factsBox.querySelector('.profile-right-edit-actions')) {
            factsBox.insertAdjacentHTML('beforeend', `
            <div class="profile-right-edit-actions">
                <button type="button" class="profile-right-save-btn" id="saveRightFieldsBtn">שמור</button>
                <button type="button" class="profile-right-cancel-btn" id="cancelRightFieldsBtn">ביטול</button>
            </div>
        `);
        }

        bindProfileButtons();
    }

    function bindProfileButtons() {
        document.querySelectorAll('.edit-btn').forEach(function(btn) {
            btn.onclick = function(e) {
                e.preventDefault();
                const field = btn.getAttribute('data-field');
                if (field) openLeftEditor(field);
            };
        });

        document.querySelectorAll('.js-inline-cancel').forEach(function(btn) {
            btn.onclick = function(e) {
                e.preventDefault();
                const field = btn.getAttribute('data-field');
                const view = document.querySelector('.profile-left-view[data-field="' + field + '"]');
                if (!view) return;

                const original = view.dataset.original || '';
                view.dataset.editing = '0';

                if (original === '') {
                    view.classList.add('is-empty');
                    view.innerHTML = 'לא מולא';
                } else {
                    view.classList.remove('is-empty');
                    view.innerHTML = escapeHtml(original).replace(/\n/g, '<br>');
                }

                bindProfileButtons();
            };
        });

        document.querySelectorAll('.js-inline-save').forEach(function(btn) {
            btn.onclick = function(e) {
                e.preventDefault();

                const field = btn.getAttribute('data-field');
                const view = document.querySelector('.profile-left-view[data-field="' + field + '"]');
                if (!view) return;

                const textarea = view.querySelector('.js-inline-textarea');
                if (!textarea) return;

                const newValue = textarea.value.trim();

                fetch('/save_profile_field.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(newValue)
                    })
                    .then(function() {
                        view.dataset.editing = '0';

                        if (newValue === '') {
                            view.classList.add('is-empty');
                            view.innerHTML = 'לא מולא';
                        } else {
                            view.classList.remove('is-empty');
                            view.innerHTML = escapeHtml(newValue).replace(/\n/g, '<br>');
                        }

                        bindProfileButtons();
                    })
                    .catch(function() {
                        alert('שגיאה בשמירה');
                    });
            };
        });

        const rightEditBtn = document.getElementById('profileRightEditBtn');
        if (rightEditBtn) {
            rightEditBtn.onclick = function(e) {
                e.preventDefault();
                openRightEditor();
            };
        }

        const cancelRightBtn = document.getElementById('cancelRightFieldsBtn');
        if (cancelRightBtn) {
            cancelRightBtn.onclick = function(e) {
                e.preventDefault();
                restoreRightFields();
            };
        }

        const saveRightBtn = document.getElementById('saveRightFieldsBtn');
        if (saveRightBtn) {
            saveRightBtn.onclick = function(e) {
                e.preventDefault();

                const inputs = document.querySelectorAll('.js-right-input');
                const requests = [];

                inputs.forEach(function(input) {
                    const field = input.getAttribute('data-field');
                    const value = input.value.trim();

                    requests.push(
                        fetch('/save_profile_field.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value)
                        })
                    );

                    rightOriginalValues[field] = value;
                });

                Promise.all(requests)
                    .then(function() {
                        restoreRightFields();
                    })
                    .catch(function() {
                        alert('שגיאה בשמירה');
                    });
            };
        }

        document.querySelectorAll('.open-chat-btn').forEach(function(btn) {
            btn.onclick = function(e) {
                e.preventDefault();

                const userId = Number(btn.getAttribute('data-user-id'));
                if (!userId) return;

                const nameEl = document.querySelector('.profile-main-title');
                const imgEl = document.querySelector('.profile-main-image');

                const userName = nameEl ? nameEl.textContent.trim() : 'משתמש';
                const userImage = imgEl ? imgEl.getAttribute('src') : '/images/no_photo.jpg';

                if (typeof openMessageModal !== 'function') {
                    window.location.href = '/?page=messages&id=' + userId;
                    return;
                }

                openMessageModal(userId, userName, userImage);
            };
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindProfileButtons();
    });
</script>