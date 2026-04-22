<?php
// ===== FILE: /mobile/register_action.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../mail.php';

function redirectToRegister(string $errorCode, array $old = []): void {
    $_SESSION['register_old'] = $old;
    header('Location: /mobile/?page=register&error=' . urlencode($errorCode));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /mobile/');
    exit;
}

/* =========================
   קבלת נתונים מהטופס
========================= */
$name       = trim($_POST['Name'] ?? '');
$email      = strtolower(trim($_POST['Email'] ?? ''));
$pass       = $_POST['Pass'] ?? '';
$dob        = trim($_POST['DOB'] ?? '');
$genderId   = (int)($_POST['Gender_Id'] ?? 0);
$lookGender = (int)($_POST['Look_Gender'] ?? 0);
$zoneId     = (int)($_POST['Zone_Id'] ?? 0);
$placeId    = (int)($_POST['Place_Id'] ?? 0);
$openDate   = trim($_POST['Open_Date'] ?? date('Y-m-d'));
$termsAgree = $_POST['terms_agree'] ?? '';

$old = [
    'Name'        => $name,
    'Email'       => $email,
    'DOB'         => $dob,
    'Gender_Id'   => (string)$genderId,
    'Look_Gender' => (string)$lookGender,
    'Zone_Id'     => (string)$zoneId,
    'Place_Id'    => (string)$placeId,
    'Open_Date'   => $openDate,
    'terms_agree' => $termsAgree === '1' ? '1' : ''
];

/* =========================
   ולידציה בסיסית
========================= */
if (
    $name === '' ||
    $email === '' ||
    $pass === '' ||
    $dob === '' ||
    $genderId <= 0 ||
    $lookGender <= 0 ||
    $zoneId <= 0 ||
    $placeId <= 0 ||
    $openDate === ''
) {
    redirectToRegister('missing', $old);
}

if ($termsAgree !== '1') {
    redirectToRegister('terms', $old);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectToRegister('email', $old);
}

if (!preg_match('/^[A-Za-z0-9]{1,15}$/', $name)) {
    redirectToRegister('name', $old);
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{4,}$/', $pass)) {
    redirectToRegister('pass', $old);
}

$dobDate = DateTime::createFromFormat('Y-m-d', $dob);
$today   = new DateTime('today');

if (!$dobDate || $dobDate->format('Y-m-d') !== $dob) {
    redirectToRegister('dob', $old);
}

if ($dobDate > $today) {
    redirectToRegister('dob', $old);
}

$age = $today->diff($dobDate)->y;
if ($age < 18 || $age > 100) {
    redirectToRegister('dob', $old);
}

$openDateObj = DateTime::createFromFormat('Y-m-d', $openDate);
if (!$openDateObj || $openDateObj->format('Y-m-d') !== $openDate) {
    $openDate = date('Y-m-d');
    $old['Open_Date'] = $openDate;
}

/* =========================
   בדיקות כפילות
========================= */
try {
    $stmt = $pdo->prepare("
        SELECT Id
        FROM users_profile
        WHERE Email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        redirectToRegister('doubleEmail', $old);
    }

    $stmt = $pdo->prepare("
        SELECT Id
        FROM users_profile
        WHERE Name = :name
        LIMIT 1
    ");
    $stmt->execute([':name' => $name]);

    if ($stmt->fetch()) {
        redirectToRegister('doubleName', $old);
    }

    /* =========================
       שליפת STR מטבלאות עזר
    ========================= */
    $stmt = $pdo->prepare("
        SELECT Gender_Str
        FROM gender
        WHERE Gender_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $genderId]);
    $genderStr = $stmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare("
        SELECT Zone_Str
        FROM zone
        WHERE Zone_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $zoneId]);
    $zoneStr = $stmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare("
        SELECT Place_Str
        FROM place
        WHERE Place_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $placeId]);
    $placeStr = $stmt->fetchColumn() ?: null;

    if ($genderStr === null || $zoneStr === null || $placeStr === null) {
        redirectToRegister('missing', $old);
    }

    /* =========================
       הצפנת סיסמה
    ========================= */
    $hashedPass = password_hash($pass, PASSWORD_DEFAULT);

    /* =========================
       הכנסת משתמש למסד
    ========================= */
    $sql = "
        INSERT INTO users_profile (
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
            look_gender,
            email_verified
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
            :look_gender,
            0
        )
    ";

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

    $userId = (int)$pdo->lastInsertId();

    /* =========================
       יצירת טוקן אימות
    ========================= */
    $verifyToken = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        UPDATE users_profile
        SET
            verification_token = :token,
            verification_sent_at = NOW(),
            email_verified = 0
        WHERE Id = :id
    ");
    $stmt->execute([
        ':token' => $verifyToken,
        ':id'    => $userId
    ]);
} catch (Throwable $e) {
    error_log('MOBILE REGISTER ERROR: ' . $e->getMessage());
    redirectToRegister('db', $old);
}

/* =========================
   בניית לינק לפי APP_URL
========================= */
$verifyLink = APP_URL . '/mobile/verify_email.php?token=' . urlencode($verifyToken);

/* =========================
   HTML מלא למייל
========================= */
$safeName     = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeLinkText = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

$mailBody = <<<HTML
<!doctype html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>אימות חשבון LoveMatch</title>
</head>
<body style="margin:0;padding:0;background:#f7f7f7;font-family:Arial,sans-serif;">
<div style="max-width:640px;margin:40px auto;background:#ffffff;border-radius:14px;border:1px solid #eee;padding:30px;">

<h2 style="color:#e91e63;margin-top:0;">שלום {$safeName}</h2>

<p>תודה שנרשמת ל-LoveMatch ❤️</p>

<p>כדי להפעיל את החשבון שלך לחץ על הכפתור:</p>

<div style="text-align:center;margin:30px 0;">
<a href="{$verifyLink}" style="background:#e91e63;color:#fff;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:bold;">
אימות חשבון
</a>
</div>

<p style="font-size:13px;color:#666;">
אם הכפתור לא עובד:
<br>
<a href="{$verifyLink}">{$safeLinkText}</a>
</p>

</div>
</body>
</html>
HTML;

/* =========================
   שליחת מייל
========================= */
$mailResult = sendMail(
    $email,
    'אימות חשבון LoveMatch',
    $mailBody,
    $name
);

if ($mailResult !== true) {
    error_log('MOBILE MAIL ERROR: ' . $mailResult);
}

/* =========================
   ניקוי נתונים ישנים ומעבר לדף הודעה
========================= */
unset($_SESSION['register_old']);

header('Location: /mobile/?page=verify_notice');
exit;
