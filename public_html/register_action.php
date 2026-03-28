<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

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

/* בדיקות */
if (!$name || !$email || !$pass || !$dob || !$gender || !$look || !$zone || !$place) {
    header('Location: /?page=register&error=missing');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /?page=register&error=badEmail');
    exit;
}

/* בדיקות כפילות */
/* מאפשר שימוש חוזר רק באימייל פיתוח */
if ($email !== 'davc22@gmail.com') {
    $stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        header('Location: /?page=register&error=doubleEmail');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Name = :name LIMIT 1");
$stmt->execute([':name' => $name]);

if ($stmt->fetch()) {
    header('Location: /?page=register&error=doubleName');
    exit;
}

/* הצפנת סיסמה */
$hash = password_hash($pass, PASSWORD_DEFAULT);

/* טוקן אימות */
$verificationToken = bin2hex(random_bytes(32));

/* שליפת מחרוזות */
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
    /* לא עוצרים רישום על זה */
}

/* מזל */
function getZodiac($date) {
    [$y, $m, $d] = explode('-', $date);

    if (($m == 1 && $d > 19) || ($m == 2 && $d < 19)) return "דלי";
    if (($m == 2 && $d > 18) || ($m == 3 && $d < 21)) return "דגים";
    if (($m == 3 && $d > 20) || ($m == 4 && $d < 20)) return "טלה";
    if (($m == 4 && $d > 19) || ($m == 5 && $d < 21)) return "שור";
    if (($m == 5 && $d > 20) || ($m == 6 && $d < 22)) return "תאומים";
    if (($m == 6 && $d > 21) || ($m == 7 && $d < 23)) return "סרטן";
    if (($m == 7 && $d > 22) || ($m == 8 && $d < 23)) return "אריה";
    if (($m == 8 && $d > 22) || ($m == 9 && $d < 23)) return "בתולה";
    if (($m == 9 && $d > 22) || ($m == 10 && $d < 24)) return "מאזניים";
    if (($m == 10 && $d > 23) || ($m == 11 && $d < 23)) return "עקרב";
    if (($m == 11 && $d > 22) || ($m == 12 && $d < 22)) return "קשת";
    return "גדי";
}

/* חישוב גיל לפי DOB */
function calculateAge($dob) {
    if (empty($dob)) {
        return null;
    }

    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    } catch (Exception $e) {
        return null;
    }
}

$zodiac = getZodiac($dob);
$age    = calculateAge($dob);

/* הכנסת משתמש */
$sql = "
INSERT INTO users_profile
(
    Name,
    Email,
    Pass,
    DOB,
    Age,
    Gender_Id,
    Gender_Str,
    Zone_Id,
    Zone_Str,
    Place_Id,
    Place_Str,
    Open_Date,
    zodiac,
    email_verified,
    Email_Validation,
    verification_token,
    verification_sent_at
)
VALUES
(
    :name,
    :email,
    :pass,
    :dob,
    :age,
    :gender,
    :gstr,
    :zone,
    :zstr,
    :place,
    :pstr,
    :open,
    :zodiac,
    0,
    0,
    :token,
    NOW()
)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':name'   => $name,
    ':email'  => $email,
    ':pass'   => $hash,
    ':dob'    => $dob,
    ':age'    => $age,
    ':gender' => $gender,
    ':gstr'   => $genderStr,
    ':zone'   => $zone,
    ':zstr'   => $zoneStr,
    ':place'  => $place,
    ':pstr'   => $placeStr,
    ':open'   => $open,
    ':zodiac' => $zodiac,
    ':token'  => $verificationToken
]);

$userId = $pdo->lastInsertId();

/* קישור אימות */
/* תעדכן ל-IP/דומיין האמיתי שלך */
$verifyLink = "http://172.29.121.157/verify.php?token=" . $verificationToken;

/* שליחת מייל */
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davc22@gmail.com';
    $mail->Password   = 'gutg mpls btsq putx';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('davc22@gmail.com', 'LoveMatch');

    /* DEV ONLY */
    $mail->addAddress('davc22@gmail.com', $name);

    /* בפרודקשן:
    $mail->addAddress($email, $name);
    */

    $mail->isHTML(true);
    $mail->Subject = 'אימות אימייל';
    $mail->Body = "
        שלום {$name}<br><br>
        לחץ לאימות:<br>
        <a href='{$verifyLink}'>{$verifyLink}</a>
    ";

    $mail->send();
} catch (Exception $e) {
    /* לא מפילים הרשמה אם מייל נפל */
}

$_SESSION['pending_user_id'] = $userId;

header('Location: /?page=verify_notice&status=mail_sent');
exit;