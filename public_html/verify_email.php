<?php
// ===== FILE: verify.php =====

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
    SELECT Id, email_verified
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
   אם כבר אומת
========================= */
if ((int)$user['email_verified'] === 1) {
    header('Location: /?page=login&verified=1');
    exit;
}

/* =========================
   עדכון אימות אימייל
========================= */
$stmt = $pdo->prepare("
    UPDATE users_profile
    SET email_verified = 1,
        Email_Validation = 1,
        verification_token = NULL
    WHERE Id = :id
");

$stmt->execute([':id' => $user['Id']]);

/* =========================
   מעבר לעמוד התחברות
========================= */
header('Location: /?page=login&verified=1');
exit;