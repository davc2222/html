<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/profile_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$session_user_id = $userId;

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function pref_json_to_array($value): array {
    if ($value === null || $value === '') {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode((string)$value, true);
    return is_array($decoded) ? array_map('strval', $decoded) : [];
}

function isChecked(array $saved, $value): string {
    return in_array((string)$value, array_map('strval', $saved), true) ? 'checked' : '';
}

/* =========================
   current user gender
========================= */
$currentGenderId = null;
if ($userId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT Gender_Id
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
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
        $stmt = $pdo->prepare("
            SELECT *
            FROM user_search_preferences
            WHERE user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $prefs['age_min']       = isset($row['age_min']) ? (int)$row['age_min'] : 25;
            $prefs['age_max']       = isset($row['age_max']) ? (int)$row['age_max'] : 65;
            $prefs['height_min']    = isset($row['height_min']) ? (int)$row['height_min'] : 140;
            $prefs['height_max']    = isset($row['height_max']) ? (int)$row['height_max'] : 220;
            $prefs['children']      = (string)($row['children'] ?? '');

            foreach (['zone', 'religion', 'religion_ref', 'smoking', 'drinking', 'family_status', 'body_type', 'vegitrain'] as $jsonField) {
                $prefs[$jsonField] = pref_json_to_array($row[$jsonField] ?? null);
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
   dynamic options from tables
========================= */
function fetchOptionsByKey(PDO $pdo, string $key): array {
    $map = [
        'zone' => [
            'sql' => "SELECT Zone_Id AS id, Zone_Str AS text FROM zone WHERE Zone_Str IS NOT NULL AND Zone_Str <> '' ORDER BY Zone_Str"
        ],
        'religion' => [
            'sql' => "SELECT Religion_Id AS id, Religion_Str AS text FROM religion WHERE Religion_Str IS NOT NULL AND Religion_Str <> '' ORDER BY Religion_Str"
        ],
        'religion_ref' => [
            'sql' => "SELECT Religion_Ref_Id AS id, Religion_Ref_Str AS text FROM religion_ref WHERE Religion_Ref_Str IS NOT NULL AND Religion_Ref_Str <> '' ORDER BY Religion_Ref_Str"
        ],
        'smoking' => [
            'sql' => "SELECT Smoking_Habbit_Id AS id, Smoking_Habbit_Str AS text FROM smoking_habbit WHERE Smoking_Habbit_Str IS NOT NULL AND Smoking_Habbit_Str <> '' ORDER BY Smoking_Habbit_Str"
        ],
        'drinking' => [
            'sql' => "SELECT Drinking_Habbit_Id AS id, Drinking_Habbit_Str AS text FROM drinking_habbit WHERE Drinking_Habbit_Str IS NOT NULL AND Drinking_Habbit_Str <> '' ORDER BY Drinking_Habbit_Str"
        ],
        'family_status' => [
            'sql' => "SELECT Family_Status_Id AS id, Family_Status_Str AS text FROM family_status WHERE Family_Status_Str IS NOT NULL AND Family_Status_Str <> '' ORDER BY Family_Status_Str"
        ],
        'body_type' => [
            'sql' => "SELECT Body_Type_Id AS id, Body_Type_Str AS text FROM body_type WHERE Body_Type_Str IS NOT NULL AND Body_Type_Str <> '' ORDER BY Body_Type_Str"
        ],
        'vegitrain' => [
            'sql' => "SELECT Vegitrain_Id AS id, Vegitrain_Str AS text FROM vegitrain WHERE Vegitrain_Str IS NOT NULL AND Vegitrain_Str <> '' ORDER BY Vegitrain_Str"
        ],
    ];

    if (!isset($map[$key])) {
        return [];
    }

    try {
        $stmt = $pdo->query($map[$key]['sql']);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $out = [];

        foreach ($rows as $row) {
            $id = trim((string)($row['id'] ?? ''));
            $text = trim((string)($row['text'] ?? ''));

            if ($id !== '' && $text !== '') {
                $out[] = [
                    'id'   => $id,
                    'text' => $text,
                ];
            }
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

$zones         = fetchOptionsByKey($pdo, 'zone');
$religions     = fetchOptionsByKey($pdo, 'religion');
$religionRefs  = fetchOptionsByKey($pdo, 'religion_ref');
$smokings      = fetchOptionsByKey($pdo, 'smoking');
$drinkings     = fetchOptionsByKey($pdo, 'drinking');
$familyOptions = fetchOptionsByKey($pdo, 'family_status');
$bodyTypes     = fetchOptionsByKey($pdo, 'body_type');
$vegitrains    = fetchOptionsByKey($pdo, 'vegitrain');

/* =========================
   results by saved prefs
========================= */
$results = [];

try {
    $sql = "
        SELECT u.*
        FROM users_profile u
        WHERE u.Id <> :me
          AND u.Is_Frozen = 0
    ";

    $params = [
        ':me' => $userId,
    ];

    if ($wantedGenderId !== null) {
        $sql .= " AND u.Gender_Id = :wanted_gender";
        $params[':wanted_gender'] = $wantedGenderId;
    }

    $sql .= "
        AND NOT EXISTS (
            SELECT 1
            FROM blocked_users bu
            WHERE (bu.Id = u.Id AND bu.Blocked_ById = :me)
               OR (bu.Id = :me AND bu.Blocked_ById = u.Id)
        )
    ";

    $sql .= " AND TIMESTAMPDIFF(YEAR, u.DOB, CURDATE()) BETWEEN :age_min AND :age_max";
    $params[':age_min'] = (int)$prefs['age_min'];
    $params[':age_max'] = (int)$prefs['age_max'];

    if (!empty($prefs['zone'])) {
        $placeholders = [];
        foreach (array_values($prefs['zone']) as $i => $val) {
            $ph = ':zone_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Zone_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['religion'])) {
        $placeholders = [];
        foreach (array_values($prefs['religion']) as $i => $val) {
            $ph = ':religion_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Religion_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['religion_ref'])) {
        $placeholders = [];
        foreach (array_values($prefs['religion_ref']) as $i => $val) {
            $ph = ':religion_ref_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Religion_Ref_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['smoking'])) {
        $placeholders = [];
        foreach (array_values($prefs['smoking']) as $i => $val) {
            $ph = ':smoking_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Smoking_Habbit_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['drinking'])) {
        $placeholders = [];
        foreach (array_values($prefs['drinking']) as $i => $val) {
            $ph = ':drinking_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Drinking_Habbit_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['family_status'])) {
        $placeholders = [];
        foreach (array_values($prefs['family_status']) as $i => $val) {
            $ph = ':family_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Family_Status_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['body_type'])) {
        $placeholders = [];
        foreach (array_values($prefs['body_type']) as $i => $val) {
            $ph = ':body_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Body_Type_Id IN (" . implode(',', $placeholders) . ")";
    }

    if (!empty($prefs['vegitrain'])) {
        $placeholders = [];
        foreach (array_values($prefs['vegitrain']) as $i => $val) {
            $ph = ':veg_' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }
        $sql .= " AND u.Vegitrain_Id IN (" . implode(',', $placeholders) . ")";
    }

    if ($prefs['children'] === 'yes') {
        $sql .= " AND COALESCE(u.Childs_Num_Str, '') <> '' AND u.Childs_Num_Str <> '0'";
    } elseif ($prefs['children'] === 'no') {
        $sql .= " AND (u.Childs_Num_Str = '0' OR u.Childs_Num_Str = '' OR u.Childs_Num_Str IS NULL)";
    }

    $sql .= " ORDER BY u.Id DESC LIMIT 40";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$user) {
        $user['Image'] = getMainProfileImage($pdo, (int)$user['Id']);
        $user['is_online'] = is_user_online($pdo, (int)$user['Id']);

        if (!empty($user['DOB'])) {
            try {
                $user['Age'] = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
            } catch (Throwable $e) {
                $user['Age'] = '';
            }
        } else {
            $user['Age'] = '';
        }
    }
    unset($user);
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
        font-weight: 600;
    }

    .advanced-search-panel {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.45);
        padding: 12px;
        overflow-y: auto;
    }

    .advanced-search-panel.is-open {
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }

    .advanced-search-modal {
        width: 100%;
        max-width: 560px;
        max-height: calc(100vh - 24px);
        overflow-y: auto;
        background: #fafafa;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
    }

    .advanced-search-title {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 18px 0;
        text-align: right;
        color: #111827;
    }

    .advanced-search-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .advanced-search-section {
        min-width: 0;
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 16px;
        padding: 14px;
    }

    .advanced-search-section-title {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 12px 0;
        color: #111827;
    }

    .adv-check-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 14px;
    }

    .adv-check-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #374151;
    }

    .adv-select {
        width: 100%;
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #fff;
        padding: 0 10px;
        font-size: 14px;
    }

    .adv-range-wrap {
        width: 100%;
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
        border-radius: 50%;
        background: #e11d48;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #e11d48;
        pointer-events: auto;
        cursor: pointer;
        margin-top: -7px;
    }

    .adv-range::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #e11d48;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #e11d48;
        pointer-events: auto;
        cursor: pointer;
    }

    .adv-actions {
        display: flex;
        flex-direction: row;
        gap: 10px;
        margin-top: 18px;
    }


    .adv-save-btn,
    .adv-cancel-btn {
        flex: 1;
        height: 42px;
        border-radius: 10px;
        font-size: 14px;
        cursor: pointer;
    }

    .adv-save-btn {
        border: 0;
        background: #e11d48;
        color: #fff;
        font-weight: 700;
    }


    .adv-cancel-btn {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .no-results {
        padding: 18px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #ececec;
    }
</style>

<main class="page-shell">
    <section class="search-container">

        <div class="advanced-search-toggle-wrap">
            <button type="button" class="advanced-search-toggle-btn" id="openAdvancedSearchBtn">
                טבלת העדפות
            </button>
        </div>

        <div class="advanced-search-panel" id="advancedSearchPanel">
            <div class="advanced-search-modal">
                <h2 class="advanced-search-title">טבלת התאמות</h2>

                <form method="POST" action="/mobile/save_search_preferences.php">
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
                            <h3 class="advanced-search-section-title">ילדים</h3>
                            <select class="adv-select" name="children">
                                <option value="" <?= $prefs['children'] === '' ? 'selected' : '' ?>>לא משנה</option>
                                <option value="yes" <?= $prefs['children'] === 'yes' ? 'selected' : '' ?>>יש</option>
                                <option value="no" <?= $prefs['children'] === 'no' ? 'selected' : '' ?>>אין</option>
                            </select>
                        </div>

                        <div class="advanced-search-section">
                            <h3 class="advanced-search-section-title">אזור</h3>
                            <div class="adv-check-grid">
                                <?php foreach ($zones as $item): ?>
                                    <label class="adv-check-item">
                                        <input type="checkbox" name="zone[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['zone'], $item['id']) ?>>
                                        <span><?= e($item['text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="advanced-search-section">
                            <h3 class="advanced-search-section-title">דתיות</h3>
                            <div class="adv-check-grid">
                                <?php foreach ($religions as $item): ?>
                                    <label class="adv-check-item">
                                        <input type="checkbox" name="religion[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['religion'], $item['id']) ?>>
                                        <span><?= e($item['text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
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
                            <h3 class="advanced-search-section-title">שתייה</h3>
                            <div class="adv-check-grid">
                                <?php foreach ($drinkings as $item): ?>
                                    <label class="adv-check-item">
                                        <input type="checkbox" name="drinking[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['drinking'], $item['id']) ?>>
                                        <span><?= e($item['text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="advanced-search-section">
                            <h3 class="advanced-search-section-title">מצב משפחתי</h3>
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
                            <h3 class="advanced-search-section-title">מבנה גוף</h3>
                            <div class="adv-check-grid">
                                <?php foreach ($bodyTypes as $item): ?>
                                    <label class="adv-check-item">
                                        <input type="checkbox" name="body_type[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['body_type'], $item['id']) ?>>
                                        <span><?= e($item['text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="advanced-search-section">
                            <h3 class="advanced-search-section-title">צמחונות / טבעונות</h3>
                            <div class="adv-check-grid">
                                <?php foreach ($vegitrains as $item): ?>
                                    <label class="adv-check-item">
                                        <input type="checkbox" name="vegitrain[]" value="<?= e($item['id']) ?>" <?= isChecked($prefs['vegitrain'], $item['id']) ?>>
                                        <span><?= e($item['text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <div class="adv-actions">
                        <button type="submit" class="adv-save-btn">שמור</button>
                        <button type="button" class="adv-cancel-btn" id="cancelAdvancedSearchBtn">בטל</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="results">
            <?php if (!$results): ?>
                <div class="no-results">לא נמצאו תוצאות</div>
            <?php else: ?>
                <?php foreach ($results as $user): ?>
                    <?php
                    $cardId = '';
                    $cardMode = 'search';
                    $cardTopBadge = '';
                    $cardSubline = '';
                    $cardShowOnline = true;
                    $cardActionsHtml = '<a href="/mobile/?page=profile&id=' . (int)$user['Id'] . '" class="view-card-profile-link">צפייה בפרופיל</a>';

                    $user['Image'] = getMainProfileImage($pdo, (int)$user['Id']);
                    $user['is_online'] = is_user_online($pdo, (int)$user['Id']);

                    include __DIR__ . '/../includes/view_card.php';
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const panel = document.getElementById('advancedSearchPanel');
        const openBtn = document.getElementById('openAdvancedSearchBtn');
        const cancelBtn = document.getElementById('cancelAdvancedSearchBtn');

        if (openBtn && panel) {
            openBtn.addEventListener('click', function() {
                panel.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            });
        }

        function closePanel() {
            if (!panel) return;
            panel.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closePanel);
        }

        if (panel) {
            panel.addEventListener('click', function(e) {
                if (e.target === panel) {
                    closePanel();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
                closePanel();
            }
        });

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