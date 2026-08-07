<?php
// ======================
// config.local.php
// ======================

return [
    'db_host'    => 'localhost',
    'db_name'    => 'dating',
    'db_user'    => 'lovematch',
    'db_pass'    => 'MyPass123',
    'db_charset' => 'utf8mb4',
    
];

$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}";

try {
    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('שגיאה בחיבור למסד הנתונים: ' . $e->getMessage());
}