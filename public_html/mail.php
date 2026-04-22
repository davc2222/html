<?php
// ===== FILE: mail.php =====

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * שליחת מייל דרך SMTP
 *
 * @param string $to
 * @param string $subject
 * @param string $bodyHtml
 * @param string $toName
 * @return true|string
 */
function sendMail($to, $subject, $bodyHtml, $toName = '') {
    $config = require __DIR__ . '/config/mail_config.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'] ?? '';
        $mail->Port       = (int)($config['port'] ?? 587);
        $mail->SMTPAuth   = !empty($config['username']);
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->Timeout    = 20;
        $mail->SMTPKeepAlive = false;

        if (!empty($config['username'])) {
            $mail->Username = $config['username'];
            $mail->Password = $config['password'] ?? '';
        }

        if (($config['secure'] ?? '') === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (($config['secure'] ?? '') === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure  = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom(
            $config['from_email'] ?? '',
            $config['from_name'] ?? 'LoveMatch'
        );

        if ($toName !== '') {
            $mail->addAddress($to, $toName);
        } else {
            $mail->addAddress($to);
        }
        $mail->addCC('lovematch@lovematch.co.il'); // 👈 כאן
        $mail->addCC('davc22@gmail.com'); // 👈 כאן

        // חשוב: HTML אמיתי
        $mail->isHTML(true);
        $mail->ContentType = 'text/html';
        $mail->Subject = $subject;

        // חשוב יותר מ-Body פשוט
        $mail->msgHTML($bodyHtml);

        $altBody = str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml);
        $altBody = strip_tags($altBody);
        $altBody = html_entity_decode($altBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->AltBody = trim($altBody);

        // לדיבוג זמני בלבד:
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo ?: $e->getMessage();
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}
