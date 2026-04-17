<?php
// ===== FILE: register_action.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/mail.php';

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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('אימייל לא תקין');
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
$stmt->execute([
    ':email' => $email
]);

if ($stmt->fetch()) {
    die('האימייל כבר קיים');
}

/* =========================
   שליפת STR מהטבלאות
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
   הכנסת משתמש חדש
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
   קישור אימות דינמי
========================= */
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
);

$scheme = $isHttps ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

$verifyLink = $scheme . '://' . $host . '/?page=verify_email&token=' . urlencode($verifyToken);

/* =========================
   בניית תוכן המייל
========================= */
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeLink = htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8');

$mailBody = "
    <div style='font-family:Arial,sans-serif;direction:rtl;text-align:right'>
        <h2>שלום {$safeName},</h2>
        <p>ברוך/ה הבא/ה ל-LoveMatch.</p>
        <p>כדי לאמת את החשבון שלך, לחץ/י על הכפתור:</p>
        <p>
            <a href='{$safeLink}'
               style='display:inline-block;padding:12px 20px;background:#ff4d6d;color:#fff;text-decoration:none;border-radius:10px;font-weight:bold;'>
                לאימות החשבון
            </a>
        </p>
        <p>אם הכפתור לא עובד, אפשר להעתיק את הקישור הזה:</p>
        <p>{$safeLink}</p>
    </div>
";

/* =========================
   שליחת מייל דרך sendMail()
========================= */
$mailResult = sendMail(
    $email,
    'אימות חשבון LoveMatch',
    $mailBody,
    $name
);

/* =========================
   אם המייל נכשל - לא להפיל את כל ההרשמה
========================= */
if ($mailResult !== true) {
    error_log('MAIL ERROR [register_action]: ' . $mailResult);
}

/* =========================
   מעבר לדף הודעת אימות
========================= */
header('Location: /?page=verify_notice');
exit;
