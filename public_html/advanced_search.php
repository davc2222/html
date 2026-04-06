<?php
// ===== FILE: advanced_search.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getMainProfileImage(PDO $pdo, int $id): string {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $pic = $stmt->fetchColumn();

        if ($pic) {
            return '/uploads/' . ltrim((string)$pic, '/');
        }
    } catch (Throwable $e) {
    }

    return '/images/no_photo.jpg';
}

/* =========================
   current user gender
========================= */
$currentGenderId = null;
if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT Gender_Id FROM users_profile WHERE Id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $currentGenderId = $stmt->fetchColumn();
        if ($currentGenderId !== false) {
            $currentGenderId = (int)$currentGenderId;
        } else {
            $currentGenderId = null;
        }
    } catch (Throwable $e) {
        $currentGenderId = null;
    }
}

/* =========================
   saved preferences
========================= */
$prefs = [
    'age_min'       => 25,
    'age_max'       => 65,
    'height_min'    => 140,
    'height_max'    => 220,
    'children'      => '',
    'zone'          => [],
    'religion'      => [],
    'religion_ref'  => [],
    'smoking'       => [],
    'drinking'      => [],
    'family_status' => [],
    'body_type'     => [],
    'vegitrain'     => [],
];

if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_search_preferences WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $prefs['age_min']    = isset($row['age_min']) ? (int)$row['age_min'] : 25;
            $prefs['age_max']    = isset($row['age_max']) ? (int)$row['age_max'] : 65;
            $prefs['height_min'] = isset($row['height_min']) ? (int)$row['height_min'] : 140;
            $prefs['height_max'] = isset($row['height_max']) ? (int)$row['height_max'] : 220;
            $prefs['children']   = (string)($row['children'] ?? '');

            foreach (['zone', 'religion', 'religion_ref', 'smoking', 'drinking', 'family_status', 'body_type', 'vegitrain'] as $jsonField) {
                $decoded = json_decode((string)($row[$jsonField] ?? '[]'), true);
                $prefs[$jsonField] = is_array($decoded) ? array_map('strval', $decoded) : [];
            }
        }
    } catch (Throwable $e) {
    }
}

/* =========================
   opposite gender default
========================= */
$wantedGenderId = null;
if ($currentGenderId === 1) {
    $wantedGenderId = 2;
} elseif ($currentGenderId === 2) {
    $wantedGenderId = 1;
}

/* =========================
   helper: load options
========================= */
function fetchOptions(PDO $pdo, string $sql, string $idField, string $textField): array {
    try {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];

        foreach ($rows as $r) {
            $out[] = [
                'id'   => (string)$r[$idField],
                'text' => (string)$r[$textField],
            ];
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

$zones = fetchOptions($pdo, "SELECT Zone_Id, Zone_Str FROM zone ORDER BY Zone_Id", 'Zone_Id', 'Zone_Str');

/* אם יש אצלך טבלאות כאלה – הן ייטענו; אם לא, פשוט יוצג ריק */
$religions     = fetchOptions($pdo, "SELECT Religion_Id, Religion_Str FROM religion ORDER BY Religion_Id", 'Religion_Id', 'Religion_Str');
$religionRefs  = fetchOptions($pdo, "SELECT Religion_Ref_Id, Religion_Ref_Str FROM religion_ref ORDER BY Religion_Ref_Id", 'Religion_Ref_Id', 'Religion_Ref_Str');
$smokings      = fetchOptions($pdo, "SELECT Smoking_Habbit_Id, Smoking_Habbit_Str FROM smoking_habbit ORDER BY Smoking_Habbit_Id", 'Smoking_Habbit_Id', 'Smoking_Habbit_Str');
$drinkings     = fetchOptions($pdo, "SELECT Vegitrain_Id, Vegitrain_Str FROM vegitrain ORDER BY Vegitrain_Id", 'Vegitrain_Id', 'Vegitrain_Str'); // אם אין אצלך drinking נפרדת
$familyOptions = fetchOptions($pdo, "SELECT Family_Status_Id, Family_Status_Str FROM family_status ORDER BY Family_Status_Id", 'Family_Status_Id', 'Family_Status_Str');
$bodyTypes     = fetchOptions($pdo, "SELECT Body_Type_Id, Body_Type_Str FROM body_type ORDER BY Body_Type_Id", 'Body_Type_Id', 'Body_Type_Str');
$vegitrains    = fetchOptions($pdo, "SELECT Vegitrain_Id, Vegitrain_Str FROM vegitrain ORDER BY Vegitrain_Id", 'Vegitrain_Id', 'Vegitrain_Str');

/* =========================
   results by saved prefs
========================= */
$results = [];

try {
    $sql = "
        SELECT *
        FROM users_profile
        WHERE Id <> :me
    ";

    $params = [
        ':me' => $userId
    ];

    if ($wantedGenderId !== null) {
        $sql .= " AND Gender_Id = :wanted_gender";
        $params[':wanted_gender'] = $wantedGenderId;
    }

    $sql .= " AND TIMESTAMPDIFF(YEAR, DOB, CURDATE()) BETWEEN :age_min AND :age_max";
    $params[':age_min'] = (int)$prefs['age_min'];
    $params[':age_max'] = (int)$prefs['age_max'];

    if (!empty($prefs['zone'])) {
        $zoneIds = array_map('intval', $prefs['zone']);
        $placeholders = [];
        foreach ($zoneIds as $i => $zid) {
            $ph = ':zone_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = $zid;
        }
        $sql .= " AND Zone_Id IN (" . implode(',', $placeholders) . ")";
    }

    $sql .= " ORDER BY Id DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $results = [];
}

function isChecked(array $saved, $value): string {
    return in_array((string)$value, array_map('strval', $saved), true) ? 'checked' : '';
}
?>

<style>
    /* ===== advanced search ===== */
    .advanced-search-toggle-wrap {
        margin-bottom: 16px;
        text-align: right;
    }

    .advanced-search-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border: 1px solid #d8dce3;
        border-radius: 12px;
        background: #fff;
        color: #1f2a37;
        cursor: pointer;
        font-size: 15px;
    }

    .advanced-search-panel {
        display: none;
        background: #fafafa;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 22px;
        margin-bottom: 22px;
    }

    .advanced-search-panel.is-open {
        display: block;
    }

    .advanced-search-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(220px, 1fr));
        gap: 22px 36px;
    }

    .advanced-search-section {
        min-width: 0;
    }

    .advanced-search-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 20px 0;
        text-align: right;
    }

    .advanced-search-section-title {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 12px 0;
        color: #111827;
    }

    .advanced-search-divider {
        grid-column: 1 / -1;
        height: 1px;
        background: #e5e7eb;
    }

    .adv-check-list {
        display: grid;
        gap: 10px;
    }

    .adv-check-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(90px, 1fr));
        gap: 10px 18px;
    }

    .adv-check-item,
    .adv-radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        color: #374151;
    }

    .adv-radio-list {
        display: grid;
        gap: 10px;
    }

    .adv-select {
        width: 100%;
        max-width: 180px;
        height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        padding: 0 10px;
        font-size: 15px;
    }

    .adv-range-wrap {
        max-width: 250px;
    }

    .adv-range-values {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
        color: #374151;
    }

    .adv-range-row {
        position: relative;
        height: 30px;
    }

    .adv-range {
        -webkit-appearance: none;
        appearance: none;
        position: absolute;
        left: 0;
        top: 8px;
        width: 100%;
        height: 4px;
        background: #d1d5db;
        pointer-events: none;
    }

    .adv-range::-webkit-slider-runnable-track {
        height: 4px;
        background: transparent;
    }

    .adv-range::-moz-range-track {
        height: 4px;
        background: transparent;
    }

    .adv-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 3px;
        background: #2ec5ce;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #2ec5ce;
        pointer-events: auto;
        cursor: pointer;
        margin-top: -7px;
    }

    .adv-range::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 3px;
        background: #2ec5ce;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #2ec5ce;
        pointer-events: auto;
        cursor: pointer;
    }

    .adv-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 22px;
    }

    .adv-save-btn {
        border: 0;
        background: #ff2f7d;
        color: #fff;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
    }

    .adv-cancel-btn {
        border: 0;
        background: transparent;
        color: #20b7c7;
        font-size: 15px;
        cursor: pointer;
    }

    .search-card-compact {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 12px;
    }

    .search-card-image {
        width: 82px;
        height: 82px;
        object-fit: cover;
        border-radius: 12px;
        background: #f3f4f6;
    }

    .search-card-content a {
        color: #111827;
        text-decoration: none;
        font-size: 18px;
        font-weight: 700;
    }

    .no-results {
        padding: 18px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #ececec;
    }

    @media (max-width: 900px) {
        .advanced-search-grid {
            grid-template-columns: 1fr;
        }

        .adv-check-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="page-shell">

    <div class="advanced-search-toggle-wrap">
        <button type="button" class="advanced-search-toggle-btn" id="openAdvancedSearchBtn">
            הגדר התאמות
        </button>
    </div>

    <div class="advanced-search-panel" id="advancedSearchPanel">
        <h2 class="advanced-search-title">התאמות</h2>

        <form method="POST" action="/save_search_preferences.php">
            <div class="advanced-search-grid">

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">טווח גילאים</h3>
                    <div class="adv-range-wrap">
                        <div class="adv-range-values">
                            <span id="ageMinValue"><?= (int)$prefs['age_min'] ?></span>
                            <span id="ageMaxValue"><?= (int)$prefs['age_max'] ?></span>
                        </div>
                        <div class="adv-range-row">
                            <input class="adv-range" type="range" id="age_min" name="age_min" min="18" max="80" value="<?= (int)$prefs['age_min'] ?>">
                            <input class="adv-range" type="range" id="age_max" name="age_max" min="18" max="80" value="<?= (int)$prefs['age_max'] ?>">
                        </div>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">אזור</h3>
                    <div class="adv-check-grid">
                        <?php foreach ($zones as $z): ?>
                            <label class="adv-check-item">
                                <input type="checkbox" name="zone[]" value="<?= e($z['id']) ?>" <?= isChecked($prefs['zone'], $z['id']) ?>>
                                <span><?= e($z['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">סדר לפי</h3>
                    <div class="adv-radio-list">
                        <label class="adv-radio-item">
                            <input type="radio" checked>
                            <span>כל ההתאמות שלי</span>
                        </label>
                        <label class="adv-radio-item">
                            <input type="radio">
                            <span>התאמות שלא צפיתי קודם</span>
                        </label>
                    </div>
                </div>

                <div class="advanced-search-divider"></div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">גובה</h3>
                    <div class="adv-range-wrap">
                        <div class="adv-range-values">
                            <span id="heightMinValue"><?= (int)$prefs['height_min'] ?></span>
                            <span id="heightMaxValue"><?= (int)$prefs['height_max'] ?></span>
                        </div>
                        <div class="adv-range-row">
                            <input class="adv-range" type="range" id="height_min" name="height_min" min="140" max="220" value="<?= (int)$prefs['height_min'] ?>">
                            <input class="adv-range" type="range" id="height_max" name="height_max" min="140" max="220" value="<?= (int)$prefs['height_max'] ?>">
                        </div>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">רקע דתי</h3>
                    <div class="adv-check-grid">
                        <?php foreach ($religionRefs as $item): ?>
                            <label class="adv-check-item">
                                <input type="checkbox" name="religion_ref[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['religion_ref'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">הצג</h3>
                    <div class="adv-radio-list">
                        <label class="adv-radio-item">
                            <input type="radio" checked>
                            <span>הצג את כל ההתאמות</span>
                        </label>
                        <label class="adv-radio-item">
                            <input type="radio">
                            <span>רק התאמות עם תמונה חשופה</span>
                        </label>
                    </div>
                </div>

                <div class="advanced-search-divider"></div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">הרגלי עישון</h3>
                    <div class="adv-check-grid">
                        <?php foreach ($smokings as $item): ?>
                            <label class="adv-check-item">
                                <input type="checkbox" name="smoking[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['smoking'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">סטטוס</h3>
                    <div class="adv-check-grid">
                        <?php foreach ($familyOptions as $item): ?>
                            <label class="adv-check-item">
                                <input type="checkbox" name="family_status[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['family_status'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <h3 class="advanced-search-section-title">ילדים</h3>
                    <select class="adv-select" name="children">
                        <option value="" <?= $prefs['children'] === '' ? 'selected' : '' ?>>לא משנה</option>
                        <option value="yes" <?= $prefs['children'] === 'yes' ? 'selected' : '' ?>>יש</option>
                        <option value="no" <?= $prefs['children'] === 'no' ? 'selected' : '' ?>>אין</option>
                    </select>
                </div>

            </div>

            <div class="adv-actions">
                <button type="submit" class="adv-save-btn">הצג התאמות</button>
                <button type="button" class="adv-cancel-btn" id="cancelAdvancedSearchBtn">ביטול</button>
            </div>
        </form>
    </div>

    <div class="views-list">

        <?php if (!$results): ?>
            <div class="no-results">לא נמצאו תוצאות</div>
        <?php else: ?>

            <?php foreach ($results as $user): ?>
                <?php
                $id   = (int)($user['Id'] ?? 0);
                $name = trim((string)($user['Name'] ?? ''));

                $age = '';
                if (!empty($user['DOB'])) {
                    try {
                        $age = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                    }
                }

                $img = getMainProfileImage($pdo, $id);

                $family   = $user['Family_Status_Str'] ?? '';
                $children = $user['Childs_Num_Str'] ?? '';
                $zone     = $user['Zone_Str'] ?? '';
                $height   = $user['Height_Str'] ?? '';
                $smoking  = $user['Smoking_Habbit_Str'] ?? '';
                ?>

                <div class="view-card">

                    <div class="view-card-media">
                        <img class="view-card-image"
                            src="<?= e($img) ?>"
                            alt="<?= e($name) ?>">
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= e($name) ?><?= $age !== '' ? ', ' . e((string)$age) : '' ?>
                        </div>

                        <div class="view-card-divider"></div>

                        <div class="view-card-details">

                            <?php if ($family !== ''): ?>
                                <div>מצב משפחתי: <?= e($family) ?></div>
                            <?php endif; ?>

                            <div>
                                ילדים:
                                <?= ($children === '' || $children === '0') ? 'ללא' : e($children) . '+' ?>
                            </div>

                            <?php if ($zone !== ''): ?>
                                <div>אזור: <?= e($zone) ?></div>
                            <?php endif; ?>

                            <?php if ($height !== ''): ?>
                                <div>גובה: <?= e($height) ?></div>
                            <?php endif; ?>

                            <?php if ($smoking !== ''): ?>
                                <div>עישון: <?= e($smoking) ?></div>
                            <?php endif; ?>

                        </div>

                        <a class="view-card-link"
                            href="/?page=profile&id=<?= $id ?>">
                            צפייה בפרופיל
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const panel = document.getElementById('advancedSearchPanel');
            const openBtn = document.getElementById('openAdvancedSearchBtn');
            const cancelBtn = document.getElementById('cancelAdvancedSearchBtn');

            if (openBtn && panel) {
                openBtn.addEventListener('click', function() {
                    panel.classList.toggle('is-open');
                });
            }

            if (cancelBtn && panel) {
                cancelBtn.addEventListener('click', function() {
                    panel.classList.remove('is-open');
                });
            }

            function bindDualRange(minId, maxId, minOutId, maxOutId) {
                const minEl = document.getElementById(minId);
                const maxEl = document.getElementById(maxId);
                const minOut = document.getElementById(minOutId);
                const maxOut = document.getElementById(maxOutId);

                if (!minEl || !maxEl || !minOut || !maxOut) {
                    return;
                }

                function sync() {
                    let minVal = parseInt(minEl.value, 10);
                    let maxVal = parseInt(maxEl.value, 10);

                    if (minVal > maxVal) {
                        if (document.activeElement === minEl) {
                            maxEl.value = minVal;
                            maxVal = minVal;
                        } else {
                            minEl.value = maxVal;
                            minVal = maxVal;
                        }
                    }

                    minOut.textContent = minVal;
                    maxOut.textContent = maxVal;
                }

                minEl.addEventListener('input', sync);
                maxEl.addEventListener('input', sync);
                sync();
            }

            bindDualRange('age_min', 'age_max', 'ageMinValue', 'ageMaxValue');
            bindDualRange('height_min', 'height_max', 'heightMinValue', 'heightMaxValue');
        });
    </script>