<?php
// ===== FILE: mail.php =====
error_reporting(E_ALL);
ini_set('display_errors', 1);



require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body, $toName = '') {
    $config = require __DIR__ . '/config/mail_config.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->Port       = $config['port'];
        $mail->Timeout    = 20;

        if ($config['secure'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($config['secure'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom($config['from_email'], $config['from_name']);

        if ($toName !== '') {
            $mail->addAddress($to, $toName);
        } else {
            $mail->addAddress($to);
        }
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo ?: $e->getMessage();
    }

    


    
}
