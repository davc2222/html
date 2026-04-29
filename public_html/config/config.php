<?php
// ======================
// LOADER CONFIG
// ======================
ini_set('session.gc_maxlifetime', 86400); // 24 שעות
session_set_cookie_params(86400);
// עדיפות ללוקאל
if (file_exists(__DIR__ . '/config.local.php')) {
    $config = require __DIR__ . '/config.local.php';
} else {
    $config = require __DIR__ . '/config.remote.php';
}

// ======================
// תאימות לאחור (constants)
// ======================
if (!defined('APP_URL') && isset($config['app_url'])) {
    define('APP_URL', $config['app_url']);
}

// אם תרצה גם DB constants (לא חובה)
if (!defined('DB_HOST') && isset($config['db_host'])) {
    define('DB_HOST', $config['db_host']);
}
if (!defined('DB_NAME') && isset($config['db_name'])) {
    define('DB_NAME', $config['db_name']);
}
if (!defined('DB_USER') && isset($config['db_user'])) {
    define('DB_USER', $config['db_user']);
}
if (!defined('DB_PASS') && isset($config['db_pass'])) {
    define('DB_PASS', $config['db_pass']);
}

// ======================
// חיבור למסד
// ======================
$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}";

try {
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("חיבור למסד נכשל: " . $e->getMessage());
}

return $config;
