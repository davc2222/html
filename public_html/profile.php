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
$viewerName = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'אני')));
$viewerImage = '/images/no_photo.jpg';

if (!empty($_SESSION['user_main_pic'])) {
    $viewerImage = (string)$_SESSION['user_main_pic'];
} elseif (!empty($_SESSION['user_image'])) {
    $viewerImage = '/images/' . $_SESSION['user_image'];
}

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
