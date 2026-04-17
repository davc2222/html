<?php
// ===== FILE: send_message.php =====

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
// אם אצלך הנתיב שונה, החלף לנתיב הנכון ל-autoload.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me   = (int)($_SESSION['user_id'] ?? 0);
$to   = (int)($_POST['to'] ?? 0);
$text = trim((string)($_POST['text'] ?? ''));

if ($me <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'המשתמש לא מחובר'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($to <= 0 || $to === $me) {
    echo json_encode([
        'ok' => false,
        'message' => 'נמען לא תקין'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($text === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'אי אפשר לשלוח הודעה ריקה'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // בדיקה שהנמען קיים + שליפת פרטי נמען
    $recipientStmt = $pdo->prepare("
        SELECT Id, Name, Email
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $recipientStmt->execute([':id' => $to]);
    $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        echo json_encode([
            'ok' => false,
            'message' => 'המשתמש לא קיים'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // שליפת שם השולח
    $senderStmt = $pdo->prepare("
        SELECT Id, Name
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $senderStmt->execute([':id' => $me]);
    $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);

    $senderName     = trim((string)($sender['Name'] ?? 'משתמש באתר'));
    $recipientName  = trim((string)($recipient['Name'] ?? ''));
    $recipientEmail = trim((string)($recipient['Email'] ?? ''));

    // שמירת ההודעה במסד
    $stmt = $pdo->prepare("
        INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`, Deleted_By_Id, Deleted_By_ById)
        VALUES (:to, :me, NOW(), :txt, 1, 0, 0)
    ");

    $stmt->execute([
        ':to'  => $to,
        ':me'  => $me,
        ':txt' => $text
    ]);

    // מחיקת typing אם קיים
    $deleteTypingStmt = $pdo->prepare("
        DELETE FROM message_typing
        WHERE user_id = :user_id
          AND target_id = :target_id
    ");
    $deleteTypingStmt->execute([
        ':user_id'  => $me,
        ':target_id' => $to
    ]);

    // שליחת מייל - לא מפילים את הבקשה אם המייל נכשל
    if ($recipientEmail !== '' && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $mailConfig = require __DIR__ . '/config/mail_config.php';

            $messagePreview = mb_substr($text, 0, 120, 'UTF-8');
            if (mb_strlen($text, 'UTF-8') > 120) {
                $messagePreview .= '...';
            }

            $messagesUrl = 'https://lovematch.co.il/?page=messages';

            $safeRecipientName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
            $safeSenderName    = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
            $safePreview       = nl2br(htmlspecialchars($messagePreview, ENT_QUOTES, 'UTF-8'));

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];
            $mail->Port       = (int)$mailConfig['port'];
            $mail->CharSet    = 'UTF-8';

            // אם יש username נשתמש ב-SMTPAuth
            $mail->SMTPAuth = !empty($mailConfig['username']);

            if (!empty($mailConfig['username'])) {
                $mail->Username = $mailConfig['username'];
            }

            if (!empty($mailConfig['password'])) {
                $mail->Password = $mailConfig['password'];
            }

            if (!empty($mailConfig['secure'])) {
                $mail->SMTPSecure = $mailConfig['secure'];
            }

            $mail->SMTPDebug = 0;

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($recipientEmail, $recipientName !== '' ? $recipientName : 'User');

            $mail->isHTML(true);
            $mail->Subject = 'יש לך הודעה חדשה ב-LoveMatch ❤️';

            $mail->Body = '
            <div dir="rtl" style="margin:0; padding:30px 15px; background:#f4f6fb; font-family:Arial,Helvetica,sans-serif;">
                <div style="max-width:620px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.08);">

                    <div style="background:linear-gradient(135deg,#ff4d6d 0%,#ff758f 100%); padding:28px 24px; text-align:center;">
                        <div style="font-size:30px; font-weight:bold; color:#ffffff;">LoveMatch ❤️</div>
                        <div style="margin-top:8px; font-size:15px; color:#ffecef;">קיבלת הודעה חדשה באתר</div>
                    </div>

                    <div style="padding:32px 24px;">
                        <h2 style="margin:0 0 16px; color:#222222; font-size:24px;">
                            שלום ' . ($safeRecipientName !== '' ? $safeRecipientName : 'לך') . ',
                        </h2>

                        <p style="margin:0 0 14px; color:#444; font-size:16px; line-height:1.8;">
                            קיבלת הודעה חדשה מ־<strong>' . $safeSenderName . '</strong>.
                        </p>

                        <div style="margin:22px 0; padding:18px; background:#fff5f7; border:1px solid #ffd6df; border-radius:12px;">
                            <div style="font-size:14px; color:#a04b5a; margin-bottom:8px;">תצוגה מקדימה:</div>
                            <div style="font-size:17px; color:#222; line-height:1.8;">' . $safePreview . '</div>
                        </div>

                        <div style="text-align:center; margin:30px 0 18px;">
                            <a href="' . $messagesUrl . '" style="display:inline-block; background:#ff4d6d; color:#ffffff; text-decoration:none; padding:14px 30px; border-radius:999px; font-size:16px; font-weight:bold;">
                                לצפייה בהודעה
                            </a>
                        </div>

                        <p style="margin:0; color:#666; font-size:13px; line-height:1.8; text-align:center;">
                            אם אינך מזהה את הפעילות הזו, ניתן פשוט להתעלם מהמייל.
                        </p>
                    </div>

                    <div style="background:#fafafa; border-top:1px solid #eeeeee; padding:16px 20px; text-align:center; color:#888; font-size:12px;">
                        נשלח אוטומטית מאתר LoveMatch
                    </div>
                </div>
            </div>';

            $mail->AltBody =
                "יש לך הודעה חדשה ב-LoveMatch\n\n" .
                "שולח: {$senderName}\n" .
                "הודעה: {$messagePreview}\n\n" .
                "לצפייה בהודעות: {$messagesUrl}";

            $mail->send();
        } catch (Throwable $mailError) {
            error_log('Mail send failed in send_message.php: ' . $mailError->getMessage());
        }
    }

    echo json_encode([
        'ok' => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('send_message.php error: ' . $e->getMessage());

    echo json_encode([
        'ok' => false,
        'message' => 'שגיאת שרת'
    ], JSON_UNESCAPED_UNICODE);
}
