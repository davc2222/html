<?php
require_once __DIR__ . '/../config/config.php';

$email = $_POST['email'] ?? '';

$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Email = :email LIMIT 1");
$stmt->execute([':email' => $email]);

echo json_encode([
    'exists' => $stmt->fetch() ? true : false
]);