<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=login');
    exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: /?page=login&error=missing');
    exit;
}

/* =========================
   שליפת משתמש
========================= */
$stmt = $pdo->prepare("
    SELECT Id, Name, Pass
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

/* =========================
   בדיקת סיסמה
========================= */
if (!password_verify($pass, $user['Pass'])) {
    header('Location: /?page=login&error=invalid');
    exit;
}

/* =========================
   SESSION חשוב!
========================= */
$_SESSION['user_id']   = (int)$user['Id'];
$_SESSION['user_name'] = $user['Name'];

/* =========================
   מעבר לפרופיל
========================= */
header("Location: /?page=profile&id=" . (int)$user['Id']);
exit;