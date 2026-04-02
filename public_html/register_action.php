<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

/* =========================
   קבלת נתונים
========================= */
$name        = trim($_POST['Name'] ?? '');
$email       = trim($_POST['Email'] ?? '');
$pass        = $_POST['Pass'] ?? '';
$dob         = $_POST['DOB'] ?? null;
$genderId    = (int)($_POST['Gender_Id'] ?? 0);
$lookGender  = (int)($_POST['Look_Gender'] ?? 0);
$zoneId      = (int)($_POST['Zone_Id'] ?? 0);
$placeId     = (int)($_POST['Place_Id'] ?? 0);
$openDate    = $_POST['Open_Date'] ?? date('Y-m-d');

/* =========================
   בדיקות
========================= */
if ($name === '' || $email === '' || $pass === '') {
    die('חסרים נתונים');
}

/* בדיקת אימייל */
$stmt = $pdo->prepare("SELECT Id FROM users_profile WHERE Email = :email LIMIT 1");
$stmt->execute([':email' => $email]);

if ($stmt->fetch()) {
    die('האימייל כבר קיים');
}

/* =========================
   שליפת STR מהטבלאות
========================= */

/* Gender */
$genderStr = null;
if ($genderId) {
    $stmt = $pdo->prepare("SELECT Gender_Str FROM gender WHERE Gender_Id = :id LIMIT 1");
    $stmt->execute([':id' => $genderId]);
    $genderStr = $stmt->fetchColumn();
}

/* Zone */
$zoneStr = null;
if ($zoneId) {
    $stmt = $pdo->prepare("SELECT Zone_Str FROM zone WHERE Zone_Id = :id LIMIT 1");
    $stmt->execute([':id' => $zoneId]);
    $zoneStr = $stmt->fetchColumn();
}

/* Place */
$placeStr = null;
if ($placeId) {
    $stmt = $pdo->prepare("SELECT Place_Str FROM place WHERE Place_Id = :id LIMIT 1");
    $stmt->execute([':id' => $placeId]);
    $placeStr = $stmt->fetchColumn();
}

/* =========================
   הצפנה
========================= */
$hashedPass = password_hash($pass, PASSWORD_DEFAULT);

/* =========================
   INSERT
========================= */
$sql = "INSERT INTO users_profile (
    Open_Date,
    Gender_Id,
    Gender_Str,
    DOB,
    Name,
    Pass,
    Email,
    Zone_Id,
    Zone_Str,
    Place_Id,
    Place_Str,
    look_gender
) VALUES (
    :open_date,
    :gender_id,
    :gender_str,
    :dob,
    :name,
    :pass,
    :email,
    :zone_id,
    :zone_str,
    :place_id,
    :place_str,
    :look_gender
)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':open_date'   => $openDate,
    ':gender_id'   => $genderId,
    ':gender_str'  => $genderStr,
    ':dob'         => $dob,
    ':name'        => $name,
    ':pass'        => $hashedPass,
    ':email'       => $email,
    ':zone_id'     => $zoneId,
    ':zone_str'    => $zoneStr,
    ':place_id'    => $placeId,
    ':place_str'   => $placeStr,
    ':look_gender' => $lookGender
]);

$userId = $pdo->lastInsertId();

/* התחברות */
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;

/* מעבר */
header("Location: /?page=profile&id=" . $userId);
exit;