<?php
// ===== FILE: advanced_search.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);

function e($v): string {
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

        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Pic_Status = 1
            ORDER BY Main_Pic DESC, Pic_Num ASC
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
        $currentGenderId = ($currentGenderId !== false) ? (int)$currentGenderId : null;
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

function isChecked(array $saved, $value): string {
    return in_array((string)$value, array_map('strval', $saved), true) ? 'checked' : '';
}

$zones = fetchOptions($pdo, "SELECT Zone_Id, Zone_Str FROM zone ORDER BY Zone_Id", 'Zone_Id', 'Zone_Str');
$religions = fetchOptions($pdo, "SELECT Religion_Id, Religion_Str FROM religion ORDER BY Religion_Id", 'Religion_Id', 'Religion_Str');
$religionRefs = fetchOptions($pdo, "SELECT Religion_Ref_Id, Religion_Ref_Str FROM religion_ref ORDER BY Religion_Ref_Id", 'Religion_Ref_Id', 'Religion_Ref_Str');
$smokings = fetchOptions($pdo, "SELECT Smoking_Habbit_Id, Smoking_Habbit_Str FROM smoking_habbit ORDER BY Smoking_Habbit_Id", 'Smoking_Habbit_Id', 'Smoking_Habbit_Str');
$familyOptions = fetchOptions($pdo, "SELECT Family_Status_Id, Family_Status_Str FROM family_status ORDER BY Family_Status_Id", 'Family_Status_Id', 'Family_Status_Str');
$bodyTypes = fetchOptions($pdo, "SELECT Body_Type_Id, Body_Type_Str FROM body_type ORDER BY Body_Type_Id", 'Body_Type_Id', 'Body_Type_Str');
$vegitrains = fetchOptions($pdo, "SELECT Vegitrain_Id, Vegitrain_Str FROM vegitrain ORDER BY Vegitrain_Id", 'Vegitrain_Id', 'Vegitrain_Str');

$drinkings = [];
try {
    $drinkings = fetchOptions($pdo, "SELECT Drinking_Id, Drinking_Str FROM drinking ORDER BY Drinking_Id", 'Drinking_Id', 'Drinking_Str');
} catch (Throwable $e) {
    $drinkings = [];
}
if (!$drinkings) {
    $drinkings = $vegitrains;
}

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
?>

<style>
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
        font-weight: 700;
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

    .adv-check-list,
    .adv-radio-list {
        display: grid;
        gap: 10px;
    }

    .adv-radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        color: #374151;
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
        flex-wrap: wrap;
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

    .no-results {
        padding: 18px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #ececec;
    }

    .views-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .view-card {
        display: flex;
        align-items: center;
        gap: 34px;
        background: #fff;
        border-radius: 22px;
        padding: 20px 26px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
    }

    .view-card-media {
        flex: 0 0 220px;
        display: flex;
        justify-content: center;
    }

    .view-card-image-wrap {
        position: relative;
        width: 220px;
        height: 220px;
    }

    .view-card-image {
        width: 220px;
        height: 220px;
        object-fit: cover;
        border-radius: 22px;
        display: block;
        background: #f3f4f6;
    }

    .online-badge {
        position: absolute;
        right: 10px;
        bottom: 10px;
        width: 14px;
        height: 14px;
        background: #22c55e;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.20);
    }

    .view-card-content {
        flex: 1;
        min-width: 0;
        text-align: right;
    }

    .view-card-name {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 14px;
    }

    .view-card-fields {
        display: grid;
        gap: 6px;
        margin-bottom: 16px;
    }

    .view-card-field {
        font-size: 15px;
        line-height: 1.6;
        color: #374151;
    }

    .view-card-link {
        display: inline-block;
        color: #19a7c7;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
    }

    .view-card-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 900px) {
        .advanced-search-grid {
            grid-template-columns: 1fr;
        }

        .view-card {
            flex-direction: column;
            align-items: stretch;
            gap: 18px;
        }

        .view-card-media {
            flex: 0 0 auto;
        }

        .view-card-image-wrap,
        .view-card-image {
            width: 100%;
            max-width: 280px;
            height: 280px;
            margin: 0 auto;
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
        <form action="/save_search_preferences.php" method="POST">
            <h2 class="advanced-search-title">הגדר התאמות</h2>

            <div class="advanced-search-grid">

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">טווח גיל</div>
                    <div class="adv-range-wrap">
                        <div class="adv-range-values">
                            <span id="ageMinValue"><?= (int)$prefs['age_min'] ?></span>
                            <span id="ageMaxValue"><?= (int)$prefs['age_max'] ?></span>
                        </div>
                        <div class="adv-range-row">
                            <input type="range" class="adv-range" id="ageMin" name="age_min" min="18" max="99" value="<?= (int)$prefs['age_min'] ?>">
                            <input type="range" class="adv-range" id="ageMax" name="age_max" min="18" max="99" value="<?= (int)$prefs['age_max'] ?>">
                        </div>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">טווח גובה</div>
                    <div class="adv-range-wrap">
                        <div class="adv-range-values">
                            <span id="heightMinValue"><?= (int)$prefs['height_min'] ?></span>
                            <span id="heightMaxValue"><?= (int)$prefs['height_max'] ?></span>
                        </div>
                        <div class="adv-range-row">
                            <input type="range" class="adv-range" id="heightMin" name="height_min" min="120" max="220" value="<?= (int)$prefs['height_min'] ?>">
                            <input type="range" class="adv-range" id="heightMax" name="height_max" min="120" max="220" value="<?= (int)$prefs['height_max'] ?>">
                        </div>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">ילדים</div>
                    <div class="adv-radio-list">
                        <?php
                        $childrenOptions = [
                            '' => 'לא משנה',
                            'יש' => 'יש',
                            'אין' => 'אין'
                        ];
                        foreach ($childrenOptions as $value => $label):
                        ?>
                            <label class="adv-radio-item">
                                <input type="radio" name="children" value="<?= e($value) ?>" <?= ((string)$prefs['children'] === (string)$value) ? 'checked' : '' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-divider"></div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">אזור</div>
                    <div class="adv-check-list">
                        <?php foreach ($zones as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="zone[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['zone'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">דת</div>
                    <div class="adv-check-list">
                        <?php foreach ($religions as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="religion[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['religion'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">רמת דתיות</div>
                    <div class="adv-check-list">
                        <?php foreach ($religionRefs as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="religion_ref[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['religion_ref'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">עישון</div>
                    <div class="adv-check-list">
                        <?php foreach ($smokings as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="smoking[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['smoking'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">שתייה</div>
                    <div class="adv-check-list">
                        <?php foreach ($drinkings as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="drinking[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['drinking'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">מצב משפחתי</div>
                    <div class="adv-check-list">
                        <?php foreach ($familyOptions as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="family_status[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['family_status'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">מבנה גוף</div>
                    <div class="adv-check-list">
                        <?php foreach ($bodyTypes as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="body_type[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['body_type'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="advanced-search-section">
                    <div class="advanced-search-section-title">צמחונות / טבעונות</div>
                    <div class="adv-check-list">
                        <?php foreach ($vegitrains as $item): ?>
                            <label class="adv-radio-item">
                                <input type="checkbox" name="vegitrain[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['vegitrain'], $item['id']) ?>>
                                <span><?= e($item['text']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="adv-actions">
                <button type="submit" class="adv-save-btn">שמור העדפות</button>
                <button type="button" class="adv-cancel-btn" id="closeAdvancedSearchBtn">סגור</button>
            </div>
        </form>
    </div>

    <?php if (!$results): ?>
        <div class="no-results">לא נמצאו תוצאות</div>
    <?php else: ?>
        <div class="views-list">
            <?php foreach ($results as $user): ?>
                <?php
                $id   = (int)$user['Id'];
                $name = trim((string)($user['Name'] ?? 'ללא שם'));

                $age = '';
                if (!empty($user['DOB'])) {
                    try {
                        $age = date_diff(date_create($user['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                        $age = '';
                    }
                }

                $img = getMainProfileImage($pdo, $id);

                $family   = trim((string)($user['Family_Status_Str'] ?? ''));
                $children = trim((string)($user['Childs_Num_Str'] ?? ''));
                $zone     = trim((string)($user['Zone_Str'] ?? ''));
                $city     = trim((string)($user['Place_Str'] ?? $user['City'] ?? ''));
                $height   = trim((string)($user['Height_Str'] ?? ''));
                $smoking  = trim((string)($user['Smoking_Habbit_Str'] ?? ''));
                ?>

                <div class="view-card">

                    <div class="view-card-media">
                        <img class="view-card-image"
                            src="<?= e($img) ?>"
                            alt="<?= e($name) ?>">
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= e($name) ?><?= $age !== '' ? ', ' . (int)$age : '' ?>
                        </div>

                        <div class="view-card-fields">

                            <?php if ($family !== ''): ?>
                                <div class="view-card-field">
                                    מצב משפחתי: <?= e($family) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($children !== ''): ?>
                                <div class="view-card-field">
                                    ילדים: <?= e($children) ?>
                                </div>
                            <?php endif; ?>

                            <!-- ✅ זה התיקון שלך -->
                            <?php if ($zone !== '' || $city !== ''): ?>
                                <div class="view-card-field">
                                    <?php
                                    $text = '';

                                    if ($zone !== '') {
                                        $text .= 'אזור: ' . e($zone);
                                    }

                                    if ($zone !== '' && $city !== '') {
                                        $text .= ' | ';
                                    }

                                    if ($city !== '') {
                                        $text .= 'מקום: ' . e($city);
                                    }

                                    echo $text;
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($height !== ''): ?>
                                <div class="view-card-field">
                                    גובה: <?= e($height) ?> ס"מ
                                </div>
                            <?php endif; ?>

                            <?php if ($smoking !== ''): ?>
                                <div class="view-card-field">
                                    עישון: <?= e($smoking) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <a class="view-card-link"
                            href="/?page=profile&id=<?= $id ?>">
                            צפייה בפרופיל
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('openAdvancedSearchBtn');
        const closeBtn = document.getElementById('closeAdvancedSearchBtn');
        const panel = document.getElementById('advancedSearchPanel');

        if (btn && panel) {
            btn.addEventListener('click', function() {
                panel.classList.toggle('is-open');
            });
        }

        if (closeBtn && panel) {
            closeBtn.addEventListener('click', function() {
                panel.classList.remove('is-open');
            });
        }

        function bindDualRange(minId, maxId, minValueId, maxValueId) {
            const minEl = document.getElementById(minId);
            const maxEl = document.getElementById(maxId);
            const minText = document.getElementById(minValueId);
            const maxText = document.getElementById(maxValueId);

            if (!minEl || !maxEl || !minText || !maxText) return;

            function sync() {
                let minVal = parseInt(minEl.value, 10);
                let maxVal = parseInt(maxEl.value, 10);

                if (minVal > maxVal) {
                    if (document.activeElement === minEl) {
                        maxVal = minVal;
                        maxEl.value = String(maxVal);
                    } else {
                        minVal = maxVal;
                        minEl.value = String(minVal);
                    }
                }

                minText.textContent = minVal;
                maxText.textContent = maxVal;
            }

            minEl.addEventListener('input', sync);
            maxEl.addEventListener('input', sync);
            sync();
        }

        bindDualRange('ageMin', 'ageMax', 'ageMinValue', 'ageMaxValue');
        bindDualRange('heightMin', 'heightMax', 'heightMinValue', 'heightMaxValue');
    });
</script>