<?php
// ===== FILE: mail.php =====

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * שליחת מייל דרך GoDaddy (localhost SMTP)
 */
function sendMail($to, $subject, $body, $toName = '') {
    $mail = new PHPMailer(true);

    try {
        // =========================
        // SMTP (GoDaddy - עובד!)
        // =========================
        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lovematch@lovematch.co.il';
        $mail->Password   = '!Y+c|!rxZ-3x%T:E';
        $mail->Port       = 25;

        // חשוב מאוד!
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;

        // =========================
        // Encoding (קריטי לעברית!)
        // =========================
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // =========================
        // FROM
        // =========================
        $mail->setFrom('lovematch@lovematch.co.il', 'LoveMatch');

        // =========================
        // TO
        // =========================
        if ($toName !== '') {
            $mail->addAddress($to, $toName);
        } else {
            $mail->addAddress($to);
        }

        // =========================
        // CONTENT
        // =========================
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // =========================
        // SEND
        // =========================
        $mail->send();

        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
