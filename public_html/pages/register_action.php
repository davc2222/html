<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

function getZodiacSign(string $date): string
{
    [$year, $month, $day] = explode('-', $date);

    if (($month == 1 && $day > 19) || ($month == 2 && $day < 19)) return "דלי";
    if (($month == 2 && $day > 18) || ($month == 3 && $day < 21)) return "דגים";
    if (($month == 3 && $day > 20) || ($month == 4 && $day < 21)) return "טלה";
    if (($month == 4 && $day > 20) || ($month == 5 && $day < 22)) return "שור";
    if (($month == 5 && $day > 21) || ($month == 6 && $day < 22)) return "תאומים";
    if (($month == 6 && $day > 21) || ($month == 7 && $day < 24)) return "סרטן";
    if (($month == 7 && $day > 23) || ($month == 8 && $day < 24)) return "אריה";
    if (($month == 8 && $day > 23) || ($month == 9 && $day < 24)) return "בתולה";
    if (($month == 9 && $day > 23) || ($month == 10 && $day < 24)) return "מאזנים";
    if (($month == 10 && $day > 23) || ($month == 11 && $day < 23)) return "עקרב";
    if (($month == 11 && $day > 22) || ($month == 12 && $day < 23)) return "קשת";
    return "גדי";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('גישה לא חוקית');
}

/* קבלת נתונים */
$name        = trim($_POST['Name'] ?? '');
$email       = trim($_POST['Email'] ?? '');
$pass        = $_POST['Pass'] ?? '';
$dob         = $_POST['DOB'] ?? '';
$genderId    = (int)($_POST['Gender_Id'] ?? 0);
$lookGender  = (int)($_POST['Look_Gender'] ?? 0);
$zoneId      = (int)($_POST['Zone_Id'] ?? 0);
$placeId     = (int)($_POST['Place_Id'] ?? 0);
$openDate    = $_POST['Open_Date'] ?? date('Y-m-d');

/* ולידציה בסיסית */
if (
    $name === '' ||
    $email === '' ||
    $pass === '' ||
    $dob === '' ||
    $genderId <= 0 ||
    $lookGender <= 0 ||
    $zoneId <= 0 ||
    $placeId <= 0
) {
    die('יש למלא את כל השדות');
}

/* בדיקת אימייל כפול */
$stmt = $pdo->prepare("SELECT Email FROM users_profile WHERE Email = :email");
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    die('האימייל כבר קיים במערכת');
}

/* בדיקת שם כפול */
$stmt = $pdo->prepare("SELECT Name FROM users_profile WHERE Name = :name");
$stmt->execute([':name' => $name]);
if ($stmt->fetch()) {
    die('שם המשתמש כבר קיים במערכת');
}

/* שליפת Gender_Str */
$stmt = $pdo->prepare("SELECT Gender_Str FROM gender WHERE Gender_id = :id");
$stmt->execute([':id' => $genderId]);
$genderRow = $stmt->fetch();
if (!$genderRow) {
    die('מגדר לא תקין');
}
$genderStr = $genderRow['Gender_Str'];

/* שליפת Zone_Str */
$stmt = $pdo->prepare("SELECT Zone_Str FROM zone WHERE Zone_id = :id");
$stmt->execute([':id' => $zoneId]);
$zoneRow = $stmt->fetch();
if (!$zoneRow) {
    die('אזור לא תקין');
}
$zoneStr = $zoneRow['Zone_Str'];

/* שליפת Place_Str */
$stmt = $pdo->prepare("SELECT Place_Str FROM place WHERE Place_id = :id");
$stmt->execute([':id' => $placeId]);
$placeRow = $stmt->fetch();
if (!$placeRow) {
    die('מקום לא תקין');
}
$placeStr = $placeRow['Place_Str'];

/* מזל */
$zodiacSign = getZodiacSign($dob);

/* הצפנת סיסמה */
$hashedPass = password_hash($pass, PASSWORD_DEFAULT);

/* שמירה */
$sql = "
    INSERT INTO users_profile
    (
        Gender_id,
        Look_Gender,
        Gender_Str,
        DOB,
        Name,
        Pass,
        Email,
        Zone_id,
        Zone_Str,
        Place_id,
        Place_Str,
        Open_Date,
        Email_Validation,
        zodiac
    )
    VALUES
    (
        :gender_id,
        :look_gender,
        :gender_str,
        :dob,
        :name,
        :pass,
        :email,
        :zone_id,
        :zone_str,
        :place_id,
        :place_str,
        :open_date,
        0,
        :zodiac
    )
";

$stmt = $pdo->prepare($sql);

$ok = $stmt->execute([
    ':gender_id'   => $genderId,
    ':look_gender' => $lookGender,
    ':gender_str'  => $genderStr,
    ':dob'         => $dob,
    ':name'        => $name,
    ':pass'        => $hashedPass,
    ':email'       => $email,
    ':zone_id'     => $zoneId,
    ':zone_str'    => $zoneStr,
    ':place_id'    => $placeId,
    ':place_str'   => $placeStr,
    ':open_date'   => $openDate,
    ':zodiac'      => $zodiacSign
]);

if ($ok) {
    echo "ההרשמה בוצעה בהצלחה";
} else {
    echo "אירעה שגיאה בשמירת הנתונים";
}