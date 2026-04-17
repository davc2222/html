<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    echo 'START<br>';

    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davc22@gmail.com';
    $mail->Password   = 'APP_PASSWORD_HERE';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 20;

    echo 'SMTP SET<br>';

    $mail->setFrom('davc22@gmail.com', 'Test');
    $mail->addAddress('davc22@gmail.com');

    $mail->Subject = 'Test Mail';
    $mail->Body    = 'Hello from GoDaddy!';

    echo 'BEFORE SEND<br>';

    $mail->send();

    echo 'MESSAGE SENT';
} catch (Exception $e) {
    echo '<br>MAILER ERROR: ' . htmlspecialchars($mail->ErrorInfo);
    echo '<br>EXCEPTION: ' . htmlspecialchars($e->getMessage());
}
