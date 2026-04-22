<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode(['exists' => false]);
    exit;
}

/* בדיקת פורמט (לא חובה אבל מומלץ) */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 1 
    FROM users_profile 
    WHERE Email = :email 
    LIMIT 1
");

$stmt->execute([':email' => $email]);

echo json_encode([
    'exists' => (bool)$stmt->fetchColumn()
]);

exit;
