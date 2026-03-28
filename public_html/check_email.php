<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode(['exists' => false]);
    exit;
}

/* חריג למייל הפיתוח שלך */
if (strcasecmp($email, 'davc22@gmail.com') === 0) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Email = :email LIMIT 1");
$stmt->execute([':email' => $email]);

echo json_encode([
    'exists' => (bool)$stmt->fetchColumn()
]);
exit;