<?php
// ===== FILE: verify_email.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    header('Location: /?page=verify_notice&status=bad_token');
    exit;
}

/* =========================
   חיפוש משתמש לפי טוקן
========================= */
$stmt = $pdo->prepare("
    SELECT Id, Name, Email, email_verified
    FROM users_profile
    WHERE verification_token = :t
    LIMIT 1
");
$stmt->execute([':t' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /?page=verify_notice&status=bad_token');
    exit;
}

/* =========================
   כבר אומת
========================= */
if ((int)$user['email_verified'] === 1) {
    header('Location: /?page=verify_notice&status=already_verified');
    exit;
}

/* =========================
   אימות החשבון
========================= */
$stmt = $pdo->prepare("
    UPDATE users_profile
    SET
        email_verified = 1,
        Email_Validation = 1,
        verification_token = NULL
    WHERE Id = :id
");
$stmt->execute([
    ':id' => $user['Id']
]);

unset($_SESSION['resend_email']);

header('Location: /?page=verify_notice&status=verified');
exit;
