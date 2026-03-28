<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 1 
    FROM users_profile 
    WHERE Name = :name 
    LIMIT 1
");

$stmt->execute([':name' => $name]);

echo json_encode([
    'exists' => (bool)$stmt->fetchColumn()
]);