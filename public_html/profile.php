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

if ($id <= 0) {
    $id = $viewerId;
}

if ($id <= 0) {
    echo "אין מזהה משתמש";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE Id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "משתמש לא נמצא";
    exit;
}

$isOwner = ($viewerId === (int)$user['Id']);

/* חישוב גיל מ-DOB */
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
$genderValue = trim((string)($user['Gender_Str'] ?? ''));
$isFemale = ($genderValue === 'אישה');

$defaultProfileImage = $isFemale
    ? '/images/default_female.svg'
    : '/images/default_male.svg';
$profileImage = $defaultProfileImage;

/* ניסיון להביא תמונה אמיתית */
$stmt = $pdo->prepare("
    SELECT Pic_Name 
    FROM user_pics 
    WHERE Id = :id AND Main_Pic = 1 
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$pic = $stmt->fetchColumn();

if (!$pic) {
    $stmt = $pdo->prepare("
        SELECT Pic_Name 
        FROM user_pics 
        WHERE Id = :id 
        ORDER BY Pic_Num ASC 
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $pic = $stmt->fetchColumn();
}

if ($pic) {
    $profileImage = '/uploads/' . ltrim((string)$pic, '/');
}

/* גלריה */
$stmt = $pdo->prepare("
    SELECT Pic_Num, Pic_Name, Main_Pic
    FROM user_pics
    WHERE Id = :id
    ORDER BY Main_Pic DESC, Pic_Num
");
$stmt->execute([':id' => $id]);
$pics = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* פיצול שדות */
$right = [];
$left = [];

foreach ($profileFields as $k => $cfg) {
    if (($cfg['side'] ?? '') === 'right') {
        $right[$k] = $cfg;
    }
    if (($cfg['side'] ?? '') === 'left') {
        $left[$k] = $cfg;
    }
}

/* שליפת אפשרויות קומבו מהמסד עבור שדות צד ימין */
$rightSelectOptions = [];

foreach ($right as $field => $cfg) {
    if (($cfg['type'] ?? '') !== 'select') {
        continue;
    }

    $table  = $cfg['table']  ?? '';
    $column = $cfg['column'] ?? '';

    if ($table === '' || $column === '') {
        $rightSelectOptions[$field] = [];
        continue;
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        $rightSelectOptions[$field] = [];
        continue;
    }

    try {
        $sql = "
            SELECT DISTINCT `$column`
            FROM `$table`
            WHERE `$column` IS NOT NULL
              AND TRIM(`$column`) <> ''
            ORDER BY `$column`
        ";

        $stmt = $pdo->query($sql);
        $options = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($cfg['zero_as_none'])) {
            $options = array_map(function ($v) {
                return trim((string)$v) === '0' ? 'ללא' : $v;
            }, $options);
        }

        $rightSelectOptions[$field] = array_values(array_unique(array_map('strval', $options)));
    } catch (Throwable $e) {
        $rightSelectOptions[$field] = [];
    }
}

$rightSelectOptionsJson = json_encode($rightSelectOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">

<div class="page-shell profile-shell">
    <div class="profile-layout">

        <!-- RIGHT -->
        <div class="profile-right-col">
            <div class="profile-right-card">

                <div class="profile-main-image-wrap">
                    <a
                        href="<?= e($profileImage) ?>"
                        id="profileMainImageLink"
                        class="profile-main-image-link"
                        data-lightbox="profile-gallery"
                        data-title="תמונה ראשית">

                        <img src="<?= e($profileImage) ?>" class="profile-main-image" id="profileMainImage" alt="">

                        <span class="profile-main-zoom">⌕</span>
                    </a>
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
                        <?php
                        if (($cfg['type'] ?? '') === 'computed' && $field === 'Age_Computed') {
                            $val = $age !== null ? (string)$age : '';
                        } else {
                            $val = trim((string)($user[$field] ?? ''));
                            if (!empty($cfg['zero_as_none']) && $val === '0') {
                                $val = 'ללא';
                            }
                        }
                        ?>

                        <div class="profile-right-row" data-field="<?= e($field) ?>" data-label="<?= e($cfg['label']) ?>">
                            <span class="profile-right-label"><?= e($cfg['label']) ?>:</span>
                            <span class="profile-right-value"><?= $val !== '' ? e($val) : 'לא מולא' ?></span>
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

            <div class="profile-left-card">
                <div class="profile-gallery-block">
                    <div class="profile-gallery-grid">

                        <?php if ($isOwner): ?>
                            <form action="/upload_photo.php" method="POST" enctype="multipart/form-data" id="profileUploadForm" class="profile-upload-form">
                                <label class="profile-gallery-upload-tile">
                                    <span class="profile-gallery-upload-icon">＋</span>
                                    <span class="profile-gallery-upload-text">הוספת תמונה</span>
                                    <input type="file" name="photo" accept="image/*" hidden onchange="document.getElementById('profileUploadForm').submit();">
                                </label>
                            </form>
                        <?php endif; ?>

                        <?php foreach ($pics as $index => $pic): ?>
                            <?php
                            $picNum = (int)$pic['Pic_Num'];
                            $picUrl = '/uploads/' . ltrim((string)$pic['Pic_Name'], '/');
                            $isMainPic = !empty($pic['Main_Pic']);
                            $imgNo = $index + 1;
                            ?>

                            <div class="profile-gallery-item">
                                <a
                                    href="<?= e($picUrl) ?>"
                                    data-lightbox="profile-gallery"
                                    data-title="תמונה <?= $imgNo ?>"
                                    class="profile-gallery-link js-gallery-link"
                                    data-full="<?= e($picUrl) ?>">
                                    <img src="<?= e($picUrl) ?>" alt="תמונה <?= $imgNo ?>" class="profile-gallery-thumb">
                                </a>

                                <?php if ($isMainPic): ?>
                                    <div class="profile-photo-main-badge">ראשית</div>
                                <?php endif; ?>

                                <?php if ($isOwner): ?>
                                    <div class="profile-gallery-actions">
                                        <button
                                            type="button"
                                            class="profile-photo-number-btn"
                                            onclick="togglePhotoMenu(<?= $picNum ?>)"
                                            aria-label="אפשרויות תמונה <?= $imgNo ?>">
                                            <?= $imgNo ?>
                                        </button>

                                        <div class="profile-photo-menu" id="photo-menu-<?= $picNum ?>">

                                            <?php if (!$isMainPic): ?>
                                                <form action="/set_main_photo.php" method="POST" class="profile-photo-menu-form">
                                                    <input type="hidden" name="pic_num" value="<?= $picNum ?>">
                                                    <button type="submit" class="profile-photo-menu-btn">קבע כראשית</button>
                                                </form>
                                            <?php endif; ?>

                                            <form action="/delete_photo.php" method="POST" class="profile-photo-menu-form" onsubmit="return confirm('למחוק את התמונה?');">
                                                <input type="hidden" name="pic_num" value="<?= $picNum ?>">
                                                <button type="submit" class="profile-photo-menu-btn profile-photo-menu-btn-delete">מחק</button>
                                            </form>

                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script>
    const profileRightSelectOptions = <?= $rightSelectOptionsJson ?>;
    let rightEditMode = false;
    let rightOriginalValues = {};
    let profileButtonsBound = false;

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function buildRightFieldControl(field, value) {
        const options = profileRightSelectOptions[field];

        if (Array.isArray(options) && options.length > 0) {
            let html = `<select class="profile-right-select js-right-input" data-field="${escapeHtml(field)}">`;
            html += `<option value="">בחר</option>`;

            options.forEach(function(opt) {
                const selected = String(opt) === String(value) ? ' selected' : '';
                html += `<option value="${escapeHtml(opt)}"${selected}>${escapeHtml(opt)}</option>`;
            });

            html += `</select>`;
            return html;
        }

        return `<input type="text" class="profile-right-input js-right-input" data-field="${escapeHtml(field)}" value="${escapeHtml(value)}">`;
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
        if (actions) {
            actions.remove();
        }

        rightEditMode = false;
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
                <span class="profile-right-edit-control">
                    ${buildRightFieldControl(field, rightOriginalValues[field])}
                </span>
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
    }

    function togglePhotoMenu(picNum) {
        document.querySelectorAll('.profile-photo-menu').forEach(function(menu) {
            if (menu.id !== 'photo-menu-' + picNum) {
                menu.style.display = 'none';
            }
        });

        const currentMenu = document.getElementById('photo-menu-' + picNum);
        if (!currentMenu) return;

        currentMenu.style.display =
            currentMenu.style.display === 'block' ? 'none' : 'block';
    }

    function bindProfileButtons() {
        if (profileButtonsBound) return;
        profileButtonsBound = true;

        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                e.preventDefault();
                const field = editBtn.getAttribute('data-field');
                if (field) {
                    openLeftEditor(field);
                }
                return;
            }

            const inlineCancelBtn = e.target.closest('.js-inline-cancel');
            if (inlineCancelBtn) {
                e.preventDefault();

                const field = inlineCancelBtn.getAttribute('data-field');
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
                return;
            }

            const inlineSaveBtn = e.target.closest('.js-inline-save');
            if (inlineSaveBtn) {
                e.preventDefault();

                const field = inlineSaveBtn.getAttribute('data-field');
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
                    })
                    .catch(function() {
                        alert('שגיאה בשמירה');
                    });
                return;
            }

            const rightEditBtn = e.target.closest('#profileRightEditBtn');
            if (rightEditBtn) {
                e.preventDefault();
                openRightEditor();
                return;
            }

            const cancelRightBtn = e.target.closest('#cancelRightFieldsBtn');
            if (cancelRightBtn) {
                e.preventDefault();
                restoreRightFields();
                return;
            }

            const saveRightBtn = e.target.closest('#saveRightFieldsBtn');
            if (saveRightBtn) {
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
                return;
            }

            const chatBtn = e.target.closest('.open-chat-btn');
            if (chatBtn) {
                e.preventDefault();

                const userId = Number(chatBtn.getAttribute('data-user-id'));
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
                return;
            }

            if (!e.target.closest('.profile-photo-number-btn') &&
                !e.target.closest('.profile-photo-menu')) {
                document.querySelectorAll('.profile-photo-menu').forEach(function(menu) {
                    menu.style.display = 'none';
                });
            }
        });

        document.querySelectorAll('.js-gallery-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const full = this.getAttribute('data-full');
                const mainImg = document.getElementById('profileMainImage');
                if (full && mainImg) {
                    mainImg.src = full;
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindProfileButtons();
    });
</script>