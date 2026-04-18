<?php
// ===== FILE: resend_verify.php =====

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/mail.php';

header('Content-Type: text/html; charset=UTF-8');

/**
 * יציאה עם הודעה מעוצבת
 */
function exit_with_message(string $title, string $message, bool $success = false): void {
    $color = $success ? '#2e7d32' : '#c62828';
    $buttonColor = $success ? '#e91e63' : '#555';

    echo "
    <!doctype html>
    <html lang='he' dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$title}</title>
    </head>
    <body style='margin:0;padding:0;background:#f7f7f7;font-family:Arial,sans-serif;'>
        <div style='max-width:620px;margin:60px auto;background:#fff;border:1px solid #eee;border-radius:16px;padding:32px;box-shadow:0 8px 30px rgba(0,0,0,0.06);'>
            <h2 style='margin-top:0;color:{$color};text-align:center;'>{$title}</h2>
            <div style='font-size:15px;line-height:1.8;color:#333;text-align:right;'>
                {$message}
            </div>
            <div style='text-align:center;margin-top:28px;'>
                <a href='/?page=login'
                   style='display:inline-block;background:{$buttonColor};color:#fff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:bold;'>
                    חזרה ללוגין
                </a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

try {
    // ===== בדיקה שיש אימייל בסשן =====
    $email = trim((string)($_SESSION['resend_email'] ?? ''));

    if ($email === '') {
        exit_with_message(
            'לא ניתן לשלוח שוב',
            'לא נמצאה כתובת אימייל לשליחה חוזרת. נסה להתחבר שוב ואז לחץ על שליחה חוזרת.'
        );
    }

    // ===== שליפת המשתמש =====
    $stmt = $pdo->prepare("
        SELECT Id, Name, Email, email_verified, verify_token
        FROM users_profile
        WHERE Email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        exit_with_message(
            'המשתמש לא נמצא',
            'לא נמצא חשבון מתאים לכתובת האימייל הזו.'
        );
    }

    // ===== אם כבר אומת =====
    if ((int)$user['email_verified'] === 1) {
        unset($_SESSION['resend_email']);

        exit_with_message(
            'האימייל כבר אומת',
            '
            <p>החשבון הזה כבר אומת בעבר.</p>
            <p>אפשר להתחבר כרגיל.</p>
            ',
            true
        );
    }

    // ===== אם אין טוקן, נייצר חדש =====
    $verifyToken = trim((string)($user['verify_token'] ?? ''));

    if ($verifyToken === '') {
        $verifyToken = bin2hex(random_bytes(32));

        $updateTokenStmt = $pdo->prepare("
            UPDATE users_profile
            SET verify_token = :token
            WHERE Id = :id
            LIMIT 1
        ");
        $updateTokenStmt->execute([
            ':token' => $verifyToken,
            ':id'    => $user['Id']
        ]);
    }

    // ===== בניית לינק =====
    $verifyLink = 'https://lovematch.co.il/verify_email.php?token=' . urlencode($verifyToken);

    $safeName  = htmlspecialchars((string)($user['Name'] ?? 'המשתמש'), ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars((string)($user['Email'] ?? ''), ENT_QUOTES, 'UTF-8');

    // ===== גוף המייל =====
    $mailBody = "
    <div style='font-family:Arial,sans-serif;direction:rtl;text-align:right;max-width:640px;margin:auto;background:#ffffff;border:1px solid #eeeeee;border-radius:14px;padding:28px;'>

        <h2 style='margin-top:0;color:#e91e63;'>אימות כתובת אימייל - LoveMatch ❤️</h2>

        <p style='font-size:15px;color:#333;line-height:1.8;'>
            שלום <strong>{$safeName}</strong>,
        </p>

        <p style='font-size:15px;color:#333;line-height:1.8;'>
            ביקשת שנשלח שוב את קישור האימות לחשבון שלך.
            כדי להפעיל את החשבון, לחץ על הכפתור הבא:
        </p>

        <div style='text-align:center;margin:30px 0;'>
            <a href='{$verifyLink}'
               style='display:inline-block;background:#e91e63;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:bold;font-size:16px;'>
                לאימות האימייל
            </a>
        </div>

        <p style='font-size:14px;color:#666;line-height:1.7;word-break:break-word;'>
            אם הכפתור לא עובד, אפשר להעתיק את הקישור הבא לדפדפן:
            <br>
            <a href='{$verifyLink}' style='color:#e91e63;'>{$verifyLink}</a>
        </p>

        <p style='font-size:14px;color:#666;line-height:1.7;'>
            כתובת המייל:
            <br>
            <strong>{$safeEmail}</strong>
        </p>

        <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>

        <p style='font-size:12px;color:#999;margin-bottom:0;text-align:center;'>
            אם לא ביקשת את המייל הזה, אפשר להתעלם ממנו.
        </p>

    </div>
    ";

    // ===== שליחה =====
    $mailResult = sendMail(
        $user['Email'],
        'שליחה חוזרת - אימות כתובת אימייל - LoveMatch',
        $mailBody,
        $user['Name'] ?? ''
    );

    if ($mailResult === true) {
        exit_with_message(
            'המייל נשלח בהצלחה',
            "
            <p>שלחנו שוב מייל אימות לכתובת:</p>
            <p><strong>{$safeEmail}</strong></p>
            <p>יש לבדוק גם ספאם / קידומי מכירות אם הוא לא מופיע בתיבה הראשית.</p>
            ",
            true
        );
    }

    exit_with_message(
        'שליחת המייל נכשלה',
        "
        <p>לא הצלחנו לשלוח כרגע את מייל האימות.</p>
        <p>נסה שוב בעוד רגע.</p>
        <p style='color:#999;font-size:12px;'>פרטי תקלה: " . htmlspecialchars((string)$mailResult, ENT_QUOTES, 'UTF-8') . "</p>
        "
    );
} catch (Throwable $e) {
    error_log('RESEND VERIFY ERROR: ' . $e->getMessage());

    exit_with_message(
        'שגיאה',
        'אירעה שגיאה בעת שליחת מייל האימות מחדש: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}
