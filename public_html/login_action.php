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

$email = trim($_POST['Email'] ?? '');
$pass  = $_POST['Pass'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: /?page=login&error=missing');
    exit;
}

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

if (!password_verify($pass, $user['Pass'])) {
    header('Location: /?page=login&error=badLogin');
    exit;
}

if ((int)$user['email_verified'] !== 1) {
    header('Location: /?page=login&error=notVerified');
    exit;
}

session_regenerate_id(true);

$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = $user['Id'];
$_SESSION['user_name'] = $user['Name'];
$_SESSION['user_email'] = $user['Email'];

$stmt = $pdo->prepare("
    UPDATE users_profile
    SET Login_Date = CURDATE(),
        Login_Time = CURTIME()
    WHERE Id = :id
");
$stmt->execute([':id' => $user['Id']]);

header('Location: /?page=home');
exit;