 <!-- register_action.php-->

<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* חיבור למסד */
require_once __DIR__ . '/config/config.php';

/* רק POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=register');
    exit;
}

/* קבלת נתונים */
$name   = trim($_POST['Name'] ?? '');
$email  = trim($_POST['Email'] ?? '');
$pass   = $_POST['Pass'] ?? '';
$dob    = $_POST['DOB'] ?? '';
$gender = $_POST['Gender_Id'] ?? '';
$look   = $_POST['Look_Gender'] ?? '';
$zone   = $_POST['Zone_Id'] ?? '';
$place  = $_POST['Place_Id'] ?? '';
$open   = $_POST['Open_Date'] ?? date('Y-m-d');

/* בדיקות בסיסיות */
if (!$name || !$email || !$pass || !$dob || !$gender || !$look || !$zone || !$place) {
    header('Location: /?page=register&error=missing');
    exit;
}

/* בדיקת אימייל קיים */
$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    header('Location: /?page=register&error=doubleEmail');
    exit;
}

/* בדיקת שם משתמש קיים */
$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Name = :name LIMIT 1");
$stmt->execute([':name' => $name]);
if ($stmt->fetch()) {
    header('Location: /?page=register&error=doubleName');
    exit;
}

/* הצפנת סיסמה */
$hash = password_hash($pass, PASSWORD_DEFAULT);

/* שליפת מחרוזות (לא חובה אבל אצלך יש) */
$genderStr = '';
$zoneStr   = '';
$placeStr  = '';

try {
    $stmt = $pdo->prepare("SELECT Gender_Str FROM gender WHERE Gender_id = :id");
    $stmt->execute([':id' => $gender]);
    $genderStr = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare("SELECT Zone_Str FROM zone WHERE Zone_id = :id");
    $stmt->execute([':id' => $zone]);
    $zoneStr = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare("SELECT Place_Str FROM place WHERE Place_id = :id");
    $stmt->execute([':id' => $place]);
    $placeStr = $stmt->fetchColumn() ?: '';
} catch (Exception $e) {
    // לא קריטי – נמשיך גם בלי
}

/* חישוב מזל */
function getZodiac($date) {
    [$y,$m,$d] = explode('-', $date);

    if (($m==1 && $d>19)||($m==2 && $d<19)) return "דלי";
    if (($m==2 && $d>18)||($m==3 && $d<21)) return "דגים";
    if (($m==3 && $d>20)||($m==4 && $d<21)) return "טלה";
    if (($m==4 && $d>20)||($m==5 && $d<22)) return "שור";
    if (($m==5 && $d>21)||($m==6 && $d<22)) return "תאומים";
    if (($m==6 && $d>21)||($m==7 && $d<24)) return "סרטן";
    if (($m==7 && $d>23)||($m==8 && $d<24)) return "אריה";
    if (($m==8 && $d>23)||($m==9 && $d<24)) return "בתולה";
    if (($m==9 && $d>23)||($m==10 && $d<24)) return "מאזנים";
    if (($m==10 && $d>23)||($m==11 && $d<23)) return "עקרב";
    if (($m==11 && $d>22)||($m==12 && $d<23)) return "קשת";
    return "גדי";
}

$zodiac = getZodiac($dob);

/* הכנסת משתמש */
$sql = "
INSERT INTO users_profile
(Gender_Id, Look_Gender, Gender_Str, DOB, Name, Pass, Email,
 Zone_Id, Zone_Str, Place_Id, Place_Str, Open_Date, Email_Validation, zodiac)
VALUES
(:gender, :look, :gstr, :dob, :name, :pass, :email,
 :zone, :zstr, :place, :pstr, :open, 0, :zodiac)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':gender' => $gender,
    ':look'   => $look,
    ':gstr'   => $genderStr,
    ':dob'    => $dob,
    ':name'   => $name,
    ':pass'   => $hash,
    ':email'  => $email,
    ':zone'   => $zone,
    ':zstr'   => $zoneStr,
    ':place'  => $place,
    ':pstr'   => $placeStr,
    ':open'   => $open,
    ':zodiac' => $zodiac
]);

/* התחברות אוטומטית */
$_SESSION['user_logged_in'] = true;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;

/* מעבר */
header('Location: /?page=home');
exit;
