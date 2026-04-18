<?php
// ======================
// config.php
// ======================

$config = [
    'db_host'    => 'localhost',
    'db_name'    => 'dating',
    'db_user'    => 'davc22',
    'db_pass'    => '!Y+c|!rxZ-3x%T:E',
    'db_charset' => 'utf8mb4',
    'app_url'    => 'https://lovematch.co.il',
];

/*
|--------------------------------------------------------------------------
| Local override
|--------------------------------------------------------------------------
| אם קיים config.local.php, הוא דורס את ברירות המחדל.
| בלוקאל הוא אמור להיות קיים.
| בשרת חי בדרך כלל הוא לא אמור להיות קיים בכלל.
*/
$localConfigFile = __DIR__ . '/config.local.php';

if (file_exists($localConfigFile)) {
    $localConfig = require $localConfigFile;

    if (is_array($localConfig)) {
        $config = array_merge($config, $localConfig);
    }
}

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/
define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);
define('DB_CHARSET', $config['db_charset']);
define('APP_URL', rtrim($config['app_url'], '/'));

/*
|--------------------------------------------------------------------------
| PDO
|--------------------------------------------------------------------------
*/
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("חיבור למסד נכשל: " . $e->getMessage());
}
