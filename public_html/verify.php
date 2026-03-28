<?php
require_once __DIR__ . '/config/config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    header('Location: /?page=verify_notice&status=bad_token');
    exit;
}

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

if ((int)$user['email_verified'] === 1) {
    header('Location: /?page=verify_notice&status=already_verified');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE users_profile
    SET email_verified = 1,
        Email_Validation = 1,
        verification_token = NULL
    WHERE Id = :id
");

$stmt->execute([':id' => $user['Id']]);

header('Location: /?page=verify_notice&status=verified');
exit;