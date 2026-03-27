<?php
require_once __DIR__ . '/../config/config.php';

$name = $_POST['name'] ?? '';

$stmt = $pdo->prepare("SELECT 1 FROM users_profile WHERE Name = :name LIMIT 1");
$stmt->execute([':name' => $name]);

echo json_encode([
    'exists' => $stmt->fetch() ? true : false
]);