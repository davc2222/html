<?php
// =======================
// FILE: login_action.php
// =======================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=login');
    exit;
}

$email = trim($_POST['Email'] ?? '');
$password = trim($_POST['Pass'] ?? '');

if ($email === '' || $password === '') {
    header('Location: /?page=login&error=missing');
    exit;
}

$stmt = $pdo->prepare("
    SELECT Id, Name, Email, Pass, email_verified
    FROM users_profile
    WHERE Email = :email
    LIMIT 1
");

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /?page=login&error=badLogin');
    exit;
}

if ((int)$user['email_verified'] !== 1) {
    header('Location: /?page=login&error=notVerified');
    exit;
}

if (!password_verify($password, $user['Pass'])) {
    header('Location: /?page=login&error=badLogin');
    exit;
}

$picStmt = $pdo->prepare("
    SELECT Pic_Name
    FROM user_pics
    WHERE Id = :id
      AND Main_Pic = 1
      AND Pic_Status = 1
    LIMIT 1
");

$picStmt->execute([
    ':id' => $user['Id']
]);

$picRow = $picStmt->fetch(PDO::FETCH_ASSOC);

$mainPic = '/images/no_photo.jpg';

if ($picRow && !empty($picRow['Pic_Name'])) {
    $mainPic = '/upload/' . $picRow['Pic_Name'];
}

$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = (int)$user['Id'];
$_SESSION['user_name'] = $user['Name'];
$_SESSION['user_main_pic'] = $mainPic;

$_SESSION['user'] = [
    'id' => (int)$user['Id'],
    'name' => $user['Name'],
    'main_pic' => $mainPic
];

session_regenerate_id(true);
session_write_close();

header('Location: /?page=home');
exit;