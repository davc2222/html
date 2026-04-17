<?php
// ===== FILE: mail.php =====

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * שליחת מייל דרך GoDaddy SMTP
 */
function sendMail($to, $subject, $body, $toName = '') {
    $mail = new PHPMailer(true);

    try {
        // =========================
        // SMTP SETTINGS (GoDaddy)
        // =========================
        $mail->isSMTP();
        $mail->Host       = 'localhost'; // אפשר גם mail.lovematch.co.il
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lovematch@lovematch.co.il';
        $mail->Password   = '!Y+c|!rxZ-3x%T:E';
        $mail->SMTPSecure = false;
        $mail->Port       =25;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';
        // =========================
        // FROM
        // =========================
        $mail->setFrom('lovematch@lovematch.co.il', 'LoveMatch');

        // =========================
        // TO
        // =========================
        if ($toName) {
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
