<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


require __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer();

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'davc22@gmail.com';
$mail->Password = 'gutg mpls btsq putx';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('davc22@gmail.com', 'Test');
$mail->addAddress('davc22@gmail.com');

$mail->Subject = 'Test Mail';
$mail->Body    = 'Hello from WSL!';

if ($mail->send()) {
    echo "Message sent!";
} else {
    echo "Mailer Error: " . $mail->ErrorInfo;
}