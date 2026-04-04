<?php
// ===== FILE: login_action.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* =========================
   Allow only POST
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=login');
    exit;
}

/* =========================
   Get form data
========================= */
$email = trim($_POST['Email'] ?? '');
$pass  = $_POST['Pass'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: /?page=login&error=missing');
    exit;
}

/* =========================
   Fetch user by email
========================= */
$stmt = $pdo->prepare("
    SELECT Id, Name, Email, Pass, email_verified
    FROM users_profile
    WHERE Email = :email
    LIMIT 1
");
$stmt->execute([':email' => $email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /?page=login&error=badLogin');
    exit;
}

/* =========================
   Verify password
========================= */
if (!password_verify($pass, $user['Pass'])) {
    header('Location: /?page=login&error=badLogin');
    exit;
}

/* =========================
   Verify email was confirmed
========================= */
if ((int)$user['email_verified'] !== 1) {
    header('Location: /?page=login&error=notVerified');
    exit;
}

/* =========================
   RESET SESSION  🔥 חשוב!
========================= */
$_SESSION = [];
session_regenerate_id(true);
/* =========================
   Session values
========================= */
$_SESSION['user_id']    = (int)$user['Id'];
$_SESSION['user_name']  = $user['Name'];
$_SESSION['user_email'] = $user['Email'];

/* =========================
   Regenerate session ID
========================= */


/* =========================
   Redirect after login
========================= */
//header('Location: /?page=profile&id=' . (int)$user['Id']);

header('Location: /?page=search');
exit;