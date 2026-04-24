<?php
// ===== FILE: send_message.php =====

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';


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
    echo json_encode(['ok' => false, 'message' => 'המשתמש לא מחובר']);
    exit;
}

if ($to <= 0 || $to === $me) {
    echo json_encode(['ok' => false, 'message' => 'נמען לא תקין']);
    exit;
}

if ($text === '') {
    echo json_encode(['ok' => false, 'message' => 'אי אפשר לשלוח הודעה ריקה']);
    exit;
}

try {

    // ===== בדיקת נמען =====
    $recipientStmt = $pdo->prepare("
        SELECT Id, Name, Email
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $recipientStmt->execute([':id' => $to]);
    $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        echo json_encode(['ok' => false, 'message' => 'המשתמש לא קיים']);
        exit;
    }

    // ===== שולח =====
    $senderStmt = $pdo->prepare("
        SELECT Id, Name
        FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $senderStmt->execute([':id' => $me]);
    $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);

    $senderName     = trim($sender['Name'] ?? 'משתמש');
    $recipientName  = trim($recipient['Name'] ?? '');
    $recipientEmail = trim($recipient['Email'] ?? '');

    // ===== שמירה במסד =====
    $stmt = $pdo->prepare("
        INSERT INTO messages (Id, ById, Date_Sent, Msg_Txt, `New`, Deleted_By_Id, Deleted_By_ById)
        VALUES (:to, :me, NOW(), :txt, 1, 0, 0)
    ");

    $stmt->execute([
        ':to'  => $to,
        ':me'  => $me,
        ':txt' => $text
    ]);

    // ===== מחיקת typing (לא קריטי אם נכשל) =====
    try {
        $pdo->prepare("
            DELETE FROM message_typing
            WHERE user_id = :user_id
              AND target_id = :target_id
        ")->execute([
            ':user_id' => $me,
            ':target_id' => $to
        ]);
    } catch (Throwable $e) {
        error_log("typing delete failed: " . $e->getMessage());
    }

    // ===== שליחת מייל (לא חוסם) =====
    if ($recipientEmail && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        try {

            $mailConfig = require __DIR__ . '/config/mail_config.php';

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->Port = (int)$mailConfig['port'];
            $mail->CharSet = 'UTF-8';

            // 🔥 תיקון התקיעות
            $mail->Timeout = 5;
            $mail->SMTPKeepAlive = false;

            // 🔒 חשוב ללוקאל
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->SMTPAuth = !empty($mailConfig['username']);

            if (!empty($mailConfig['username'])) {
                $mail->Username = $mailConfig['username'];
                $mail->Password = $mailConfig['password'];
            }

            if (!empty($mailConfig['secure'])) {
                $mail->SMTPSecure = $mailConfig['secure'];
            }

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($recipientEmail, $recipientName ?: 'User');

            $mail->isHTML(true);
            $mail->Subject = 'יש לך הודעה חדשה 💌';

            $mail->Body = "
<div style='font-family:Arial,sans-serif;direction:rtl;text-align:right;max-width:600px;margin:auto;background:#ffffff;border:1px solid #eeeeee;border-radius:12px;padding:24px;'>

    <h2 style='margin-top:0;color:#e91e63;'>יש לך הודעה חדשה 💌</h2>

    <p style='font-size:15px;color:#333;line-height:1.7;'>
        <strong>{$senderName}</strong> שלח לך הודעה באתר <b>LoveMatch</b>.
    </p>

    <p style='font-size:14px;color:#666;line-height:1.6;'>
        מטעמי פרטיות, תוכן ההודעה אינו מופיע במייל.
    </p>

    <div style='text-align:center;margin:30px 0;'>
        <a href='https://lovematch.co.il/?page=messages'
           style='display:inline-block;background:#e91e63;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;'>
            כניסה לצ'אט
        </a>
    </div>

    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>

    <p style='font-size:12px;color:#999;margin-bottom:0;text-align:center;'>
        הודעה זו נשלחה אוטומטית ממערכת LoveMatch
    </p>

</div>
";

            $mail->send();
        } catch (Throwable $mailError) {
            // לא מפיל את הצ'אט!
            error_log("MAIL ERROR: " . $mailError->getMessage());
        }
    }

    // ===== הצלחה =====
    echo json_encode(['ok' => true]);
    exit;
} catch (Throwable $e) {

    error_log("SEND MSG ERROR: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
