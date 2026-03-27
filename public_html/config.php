<?php
// ======================
// config.php
// ======================

// הגדרות חיבור למסד הנתונים
define('DB_HOST', 'localhost');
define('DB_NAME', 'dating');
define('DB_USER', 'webuser');
define('DB_PASS', 'MyPass123');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // משתנה $pdo יהיה זמין לכל קובץ שמכיל את config.php
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("חיבור למסד נכשל: " . $e->getMessage());
}