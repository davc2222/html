<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // הגדרות SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';       // שרת SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davc22@gmail.com';       // כתובת מייל שלך
    $mail->Password   = 'gutg mpls btsq putx';    // App Password אם יש 2FA
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // הגדרות המייל
    $mail->setFrom('davc22@gmail.com', 'MySite');
    $mail->addAddress('davc22@gmail.com', 'Recipient'); // למי לשלוח
    $mail->Subject = 'Test Email from PHPMailer';
    $mail->Body    = 'Hello! This is a test email from mysite.';

    $mail->send();
    echo 'Mail sent successfully!';
} catch (Exception $e) {
    echo "Mail could not be sent. PHPMailer Error: {$mail->ErrorInfo}";
}