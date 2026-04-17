<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . '/config/mail_config.php';

echo '<pre>';
echo 'HTTP_HOST: ' . ($_SERVER['HTTP_HOST'] ?? 'NONE') . PHP_EOL;
echo 'SERVER_NAME: ' . ($_SERVER['SERVER_NAME'] ?? 'NONE') . PHP_EOL;
echo 'REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR'] ?? 'NONE') . PHP_EOL;
echo PHP_EOL;
print_r($config);
echo '</pre>';
exit;
