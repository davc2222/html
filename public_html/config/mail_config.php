<?php
// ===== FILE: config/mail_config.php =====

// זיהוי סביבה
$host       = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

$isLocal =
    in_array($remoteAddr, ['127.0.0.1', '::1'], true) ||
    stripos($host, 'localhost') !== false ||
    stripos($serverName, 'localhost') !== false ||
    stripos($host, '127.0.0.1') !== false ||
    stripos($serverName, '127.0.0.1') !== false ||
    stripos($host, 'wsl.localhost') !== false ||
    preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host);

// =========================
// לוקאל (Gmail)
// =========================
if ($isLocal) {
    return [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'username'   => 'davc22@gmail.com',
        'password'   => 'gutg mpls btsq putx', // 👈 כאן להכניס
        'secure'     => 'tls',
        'from_email' => 'davc22@gmail.com',
        'from_name'  => 'LoveMatch Local',
    ];
}

// =========================
// GoDaddy (Production)
// =========================
return [
    'host'       => 'localhost',
    'port'       => 25,
    'username'   => 'lovematch@lovematch.co.il',
    'password'   => '!Y+c|!rxZ-3x%T:E', // 👈 כאן להכניס
    'secure'     => false,
    'from_email' => 'lovematch@lovematch.co.il',
    'from_name'  => 'LoveMatch',
];