<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileFields = require __DIR__ . '/profile_fields.php';

header('Content-Type: application/json; charset=utf-8');

$id    = $_POST['id'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'אין הרשאה']);
    exit;
}

if (!is_numeric($id) || (int)$id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'מזהה לא תקין']);
    exit;
}

if ((int)$_SESSION['user_id'] !== (int)$id) {
    echo json_encode(['ok' => false, 'message' => 'אין הרשאה לערוך פרופיל זה']);
    exit;
}

$allowedFields = array_keys($profileFields);

if (!in_array($field, $allowedFields, true)) {
    echo json_encode(['ok' => false, 'message' => 'שדה לא מורשה']);
    exit;
}

if (!empty($profileFields[$field]['read_only'])) {
    echo json_encode(['ok' => false, 'message' => 'שדה לא ניתן לעריכה']);
    exit;
}

$value = trim((string)$value);

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