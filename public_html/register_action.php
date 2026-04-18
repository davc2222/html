<?php
// ===== FILE: register_action.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/mail.php';

/* =========================
   רק POST
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

/* =========================
   קבלת נתונים מהטופס
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
   ולידציה בסיסית
========================= */
if ($name === '' || $email === '' || $pass === '') {
    die('חסרים נתונים');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('אימייל לא תקין');
}

if (strlen($pass) < 6) {
    die('הסיסמה חייבת להיות לפחות 6 תווים');
}

/* =========================
   בדיקת אימייל קיים
========================= */
$stmt = $pdo->prepare("
    SELECT Id
    FROM users_profile
    WHERE Email = :email
    LIMIT 1
");
$stmt->execute([':email' => $email]);

if ($stmt->fetch()) {
    die('האימייל כבר קיים');
}

/* =========================
   שליפת STR מטבלאות עזר
========================= */
$genderStr = null;
if ($genderId > 0) {
    $stmt = $pdo->prepare("
        SELECT Gender_Str
        FROM gender
        WHERE Gender_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $genderId]);
    $genderStr = $stmt->fetchColumn() ?: null;
}

$zoneStr = null;
if ($zoneId > 0) {
    $stmt = $pdo->prepare("
        SELECT Zone_Str
        FROM zone
        WHERE Zone_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $zoneId]);
    $zoneStr = $stmt->fetchColumn() ?: null;
}

$placeStr = null;
if ($placeId > 0) {
    $stmt = $pdo->prepare("
        SELECT Place_Str
        FROM place
        WHERE Place_Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $placeId]);
    $placeStr = $stmt->fetchColumn() ?: null;
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
    ':gender_id'   => $genderId > 0 ? $genderId : null,
    ':gender_str'  => $genderStr,
    ':dob'         => $dob ?: null,
    ':name'        => $name,
    ':pass'        => $hashedPass,
    ':email'       => $email,
    ':zone_id'     => $zoneId > 0 ? $zoneId : null,
    ':zone_str'    => $zoneStr,
    ':place_id'    => $placeId > 0 ? $placeId : null,
    ':place_str'   => $placeStr,
    ':look_gender' => $lookGender > 0 ? $lookGender : null
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

/* =========================
   בניית לינק לפי APP_URL
========================= */
$verifyLink = APP_URL . '/verify_email.php?token=' . urlencode($verifyToken);

/* =========================
   HTML מלא למייל (חשוב!)
========================= */
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
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
    error_log('MAIL ERROR: ' . $mailResult);
}

/* =========================
   מעבר לדף הודעה
========================= */
header('Location: /?page=verify_notice');
exit;
