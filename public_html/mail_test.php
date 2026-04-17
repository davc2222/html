<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/mail.php';

echo 'START<br>';

$result = sendMail(
    'davc22@gmail.com',
    'Test from LoveMatch',
    'Hello test'
);


