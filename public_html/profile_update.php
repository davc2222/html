<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'לא מחובר'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'error' => 'נתונים לא תקינים'
    ]);
    exit;
}

$sql = "
    UPDATE users_profile
    SET
        Name = :Name,
        DOB = :DOB,
        Gender_Str = :Gender_Str,
        Place_Str = :Place_Str,
        Zone_Str = :Zone_Str,
        Family_Status_Str = :Family_Status_Str,
        Childs_Num_Str = :Childs_Num_Str,
        Childs_Pos_Str = :Childs_Pos_Str,
        Religion_Str = :Religion_Str,
        Religion_Ref_Str = :Religion_Ref_Str,
        Height_Str = :Height_Str,
        Hair_Color_Str = :Hair_Color_Str,
        Body_Type_Str = :Body_Type_Str,
        Who_Am_I = :Who_Am_I,
        I_Looking_For = :I_Looking_For,
        Ideal_Relation_Is = :Ideal_Relation_Is
    WHERE Id = :Id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':Name' => trim($data['Name'] ?? ''),
    ':DOB' => trim($data['DOB'] ?? ''),
    ':Gender_Str' => trim($data['Gender_Str'] ?? ''),
    ':Place_Str' => trim($data['Place_Str'] ?? ''),
    ':Zone_Str' => trim($data['Zone_Str'] ?? ''),
    ':Family_Status_Str' => trim($data['Family_Status_Str'] ?? ''),
    ':Childs_Num_Str' => trim($data['Childs_Num_Str'] ?? ''),
    ':Childs_Pos_Str' => trim($data['Childs_Pos_Str'] ?? ''),
    ':Religion_Str' => trim($data['Religion_Str'] ?? ''),
    ':Religion_Ref_Str' => trim($data['Religion_Ref_Str'] ?? ''),
    ':Height_Str' => trim($data['Height_Str'] ?? ''),
    ':Hair_Color_Str' => trim($data['Hair_Color_Str'] ?? ''),
    ':Body_Type_Str' => trim($data['Body_Type_Str'] ?? ''),
    ':Who_Am_I' => trim($data['Who_Am_I'] ?? ''),
    ':I_Looking_For' => trim($data['I_Looking_For'] ?? ''),
    ':Ideal_Relation_Is' => trim($data['Ideal_Relation_Is'] ?? ''),
    ':Id' => $userId
]);

$_SESSION['user_name'] = trim($data['Name'] ?? '');

echo json_encode([
    'success' => true
]);