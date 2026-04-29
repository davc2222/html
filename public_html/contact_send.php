<?php
// ===== FILE: contact_send.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function json_out(bool $ok, string $error = ''): void {
    echo json_encode([
        'ok'    => $ok,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'שיטת בקשה לא תקינה');
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($email === '' || $message === '') {
    json_out(false, 'נא למלא את כל השדות');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(false, 'כתובת אימייל לא תקינה');
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davc22@gmail.com';
    $mail->Password   = 'gutg mpls btsq putx';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->CharSet = 'UTF-8';

    $mail->setFrom('davc22@gmail.com', 'LoveMatch');
    $mail->addAddress('davc22@gmail.com', 'LoveMatch');
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'פנייה חדשה מצור קשר - LoveMatch';

    $safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $mail->Body = "
        <div style='font-family:Arial,sans-serif;direction:rtl;text-align:right;line-height:1.7'>
            <h2 style='margin:0 0 16px 0;color:#e11d48;'>פנייה חדשה מצור קשר</h2>

            <p><strong>שם:</strong> {$safeName}</p>
            <p><strong>אימייל:</strong> {$safeEmail}</p>

            <div style='margin-top:18px;padding:14px;border:1px solid #eee;border-radius:12px;background:#fafafa;'>
                <strong>הודעה:</strong><br><br>
                {$safeMessage}
            </div>
        </div>
    ";

    $mail->AltBody =
        "פנייה חדשה מצור קשר - LoveMatch\n\n"
        . "שם: {$name}\n"
        . "אימייל: {$email}\n\n"
        . "הודעה:\n{$message}\n";

    $mail->send();

    json_out(true);
} catch (Exception $e) {
    json_out(false, 'שליחת המייל נכשלה: ' . $mail->ErrorInfo);
}
