<?php
/* =========================
   login_action.php
   ========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* -----------------------------
   רק POST
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=login');
    exit;
}

/* -----------------------------
   קבלת נתונים
----------------------------- */
$email = trim($_POST['Email'] ?? '');
$pass  = $_POST['Pass'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: /?page=login&error=missing');
    exit;
}

/* -----------------------------
   שליפת משתמש
----------------------------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM users_profile
    WHERE Email = :email
    LIMIT 1
");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /?page=login&error=invalid');
    exit;
}

/* -----------------------------
   בדיקת סיסמה
----------------------------- */
if (!password_verify($pass, $user['Pass'])) {
    header('Location: /?page=login&error=invalid');
    exit;
}

/* -----------------------------
   בדיקת אימות אימייל
----------------------------- */
if ((int)($user['email_verified'] ?? 0) !== 1) {
    header('Location: /?page=login&error=notVerified');
    exit;
}

/* -----------------------------
   שמירת סשן
----------------------------- */
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = (int)$user['Id'];
$_SESSION['user_name'] = $user['Name'] ?? '';
$_SESSION['user_email'] = $user['Email'] ?? '';

/* -----------------------------
   תמונת משתמש ראשית לסשן
----------------------------- */
$picStmt = $pdo->prepare("
    SELECT Pic_Name
    FROM user_pics
    WHERE Id = :id
      AND Main_Pic = 1
      AND Pic_Status = 1
    LIMIT 1
");
$picStmt->execute([':id' => (int)$user['Id']]);
$picRow = $picStmt->fetch(PDO::FETCH_ASSOC);

if ($picRow && !empty($picRow['Pic_Name'])) {
    $_SESSION['user_main_pic'] = '/upload/' . $picRow['Pic_Name'];
} else {
    unset($_SESSION['user_main_pic']);
}

/* -----------------------------
   עדכון זמן התחברות
----------------------------- */
$updateStmt = $pdo->prepare("
    UPDATE users_profile
    SET Login_Date = CURDATE(),
        Login_Time = CURTIME()
    WHERE Id = :id
");
$updateStmt->execute([':id' => (int)$user['Id']]);

header('Location: /?page=home');
exit;