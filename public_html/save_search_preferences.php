<?php
// ===== FILE: save_search_preferences.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: /?page=login');
    exit;
}

function postArrayJson(string $key): ?string {
    $value = $_POST[$key] ?? [];
    if (!is_array($value) || empty($value)) {
        return null;
    }

    $clean = [];
    foreach ($value as $item) {
        $item = trim((string)$item);
        if ($item !== '') {
            $clean[] = $item;
        }
    }

    if (empty($clean)) {
        return null;
    }

    return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE);
}

$ageMin = isset($_POST['age_min']) ? (int)$_POST['age_min'] : 18;
$ageMax = isset($_POST['age_max']) ? (int)$_POST['age_max'] : 80;
$heightMin = isset($_POST['height_min']) ? (int)$_POST['height_min'] : 140;
$heightMax = isset($_POST['height_max']) ? (int)$_POST['height_max'] : 220;
$children = trim((string)($_POST['children'] ?? ''));

if ($ageMin < 18) $ageMin = 18;
if ($ageMax > 80) $ageMax = 80;
if ($ageMin > $ageMax) $ageMin = $ageMax;

if ($heightMin < 140) $heightMin = 140;
if ($heightMax > 220) $heightMax = 220;
if ($heightMin > $heightMax) $heightMin = $heightMax;

if (!in_array($children, ['', 'yes', 'no'], true)) {
    $children = '';
}

$data = [
    ':user_id'       => $userId,
    ':age_min'       => $ageMin,
    ':age_max'       => $ageMax,
    ':height_min'    => $heightMin,
    ':height_max'    => $heightMax,
    ':children'      => $children,
    ':zone'          => postArrayJson('zone'),
    ':religion'      => postArrayJson('religion'),
    ':religion_ref'  => postArrayJson('religion_ref'),
    ':smoking'       => postArrayJson('smoking'),
    ':drinking'      => postArrayJson('drinking'),
    ':family_status' => postArrayJson('family_status'),
    ':body_type'     => postArrayJson('body_type'),
    ':vegitrain'     => postArrayJson('vegitrain'),
];

$sql = "
INSERT INTO user_search_preferences
(
    user_id,
    age_min,
    age_max,
    height_min,
    height_max,
    children,
    zone,
    religion,
    religion_ref,
    smoking,
    drinking,
    family_status,
    body_type,
    vegitrain
)
VALUES
(
    :user_id,
    :age_min,
    :age_max,
    :height_min,
    :height_max,
    :children,
    :zone,
    :religion,
    :religion_ref,
    :smoking,
    :drinking,
    :family_status,
    :body_type,
    :vegitrain
)
ON DUPLICATE KEY UPDATE
    age_min = VALUES(age_min),
    age_max = VALUES(age_max),
    height_min = VALUES(height_min),
    height_max = VALUES(height_max),
    children = VALUES(children),
    zone = VALUES(zone),
    religion = VALUES(religion),
    religion_ref = VALUES(religion_ref),
    smoking = VALUES(smoking),
    drinking = VALUES(drinking),
    family_status = VALUES(family_status),
    body_type = VALUES(body_type),
    vegitrain = VALUES(vegitrain)
";

$stmt = $pdo->prepare($sql);
$stmt->execute($data);

header('Location: /?page=advanced_search');
exit;
