<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$id    = $_POST['id'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowedFields = [
    'Who_Am_I',
    'I_Looking_For',
    'Ideal_Relation_Is',
    'Hobbies',
    'Spending',
    'Favorite_Movies',
    'Favorite_TV',
    'Favorite_Books',
    'Favorite_Music'
];

if (!is_numeric($id) || (int)$id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'מזהה לא תקין']);
    exit;
}

if (!in_array($field, $allowedFields, true)) {
    echo json_encode(['ok' => false, 'message' => 'שדה לא מורשה']);
    exit;
}

$sql = "UPDATE users_profile SET {$field} = :value WHERE Id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([
    ':value' => $value,
    ':id'    => (int)$id
]);

echo json_encode([
    'ok' => $ok,
    'value' => $value
]);