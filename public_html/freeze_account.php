<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'המשתמש לא מחובר'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE users_profile
        SET Is_Frozen = 1
        WHERE Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();

    echo json_encode([
        'ok' => true,
        'redirect' => '/'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'שגיאה בהקפאת הכרטיס'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}