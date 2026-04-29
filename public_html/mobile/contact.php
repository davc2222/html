<?php
// ===== FILE: mobile/contact.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'אורח')));
$userEmail = trim((string)($_SESSION['user_email'] ?? ''));

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    /*
     * אם המשתמש לא מחובר ואין אימייל בסשן,
     * לוקחים את האימייל מהשדה החדש בטופס.
     */
    $postedEmail = trim((string)($_POST['email'] ?? ''));

    if ($userEmail === '' && $postedEmail !== '') {
        $userEmail = $postedEmail;
    }

    if ($message === '') {
        $error = 'נא למלא הודעה';
    } elseif ($userEmail === '') {
        $error = 'נא למלא אימייל';
    } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'נא להזין אימייל תקין';
    } else {
        $mailConfig = require __DIR__ . '/../config/mail_config.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];
            $mail->Port       = (int)$mailConfig['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->CharSet    = 'UTF-8';

            if (($mailConfig['secure'] ?? '') === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (($mailConfig['secure'] ?? '') === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->setFrom(
                $mailConfig['from_email'] ?? 'lovematch@lovematch.co.il',
                $mailConfig['from_name'] ?? 'LoveMatch'
            );

            $mail->addAddress('lovematch@lovematch.co.il');
            $mail->addBCC('davc22@gmail.com');

            if ($userEmail !== '') {
                $mail->addReplyTo($userEmail, $userName);
            }

            $mail->isHTML(false);
            $mail->Subject = "פנייה מהאתר | {$userName} | ID: {$userId}";

            $mail->Body  = "שם משתמש: {$userName}\n";
            $mail->Body .= "ID: {$userId}\n";
            $mail->Body .= "אימייל משתמש: {$userEmail}\n\n";
            $mail->Body .= "הודעה:\n{$message}\n";

            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $error = 'שגיאה בשליחת ההודעה: ' . $mail->ErrorInfo;
        }
    }
}
?>

<div class="contact-page">

    <h2 class="contact-title">צור קשר</h2>

    <p class="contact-desc">
        יש לך שאלה, תקלה, הצעה לשיפור או בקשה בנושא החשבון?
        שלח לנו הודעה ונחזור אליך בהקדם.
    </p>

    <?php if ($success): ?>
        <div class="contact-success">
            ההודעה נשלחה בהצלחה ✔
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="contact-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="contact-form">

        <?php if ($userEmail === ''): ?>
            <input
                type="email"
                name="email"
                placeholder="הכנס אימייל..."
                value="<?= e($_POST['email'] ?? '') ?>"
                required>
        <?php else: ?>
            <input
                type="email"
                name="email"
                value="<?= e($userEmail) ?>"
                readonly>
        <?php endif; ?>

        <textarea
            name="message"
            placeholder="כתוב כאן את ההודעה שלך..."
            rows="6"
            required><?= (!$success && !empty($_POST['message'])) ? e($_POST['message']) : '' ?></textarea>

        <button type="submit" class="contact-btn">שלח הודעה</button>
    </form>

</div>

<style>
    .contact-page {
        padding: 16px;
    }

    .contact-title {
        text-align: center;
        font-size: 21px;
        margin-bottom: 10px;
        color: #d81b60;
    }

    .contact-desc {
        text-align: center;
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 14px;
    }

    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .contact-form input,
    .contact-form textarea {
        padding: 12px;
        border-radius: 14px;
        border: 1px solid #ddd;
        font-family: inherit;
        font-size: 14px;
        box-sizing: border-box;
        width: 100%;
    }

    .contact-form input[readonly] {
        background: #f5f5f5;
        color: #666;
        cursor: not-allowed;
    }

    .contact-form textarea {
        resize: vertical;
        min-height: 130px;
    }

    .contact-btn {
        background: #d81b60;
        color: #fff;
        border: none;
        padding: 13px;
        border-radius: 14px;
        font-weight: bold;
        cursor: pointer;
    }

    .contact-success,
    .contact-error {
        padding: 11px;
        border-radius: 12px;
        margin-bottom: 12px;
        text-align: center;
        font-size: 14px;
    }

    .contact-success {
        background: #d4edda;
        color: #155724;
    }

    .contact-error {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var successBox = document.querySelector('.contact-success');

        if (successBox) {
            setTimeout(function() {
                successBox.style.display = 'none';
            }, 2500);
        }
    });
</script>