<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'המשתמש לא מחובר'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];

/**
 * מבצע DELETE וממשיך גם אם הטבלה/עמודה לא קיימת.
 */
function safeDelete(PDO $pdo, string $sql, array $params = []): void
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } catch (Throwable $e) {
        // בכוונה שותקים כדי לא להפיל מחיקה מלאה אם טבלה מסוימת לא קיימת
    }
}

try {
    $pdo->beginTransaction();

    /*
     * מחיקת קבצי תמונות מהשרת - אם יש לך טבלת user_photos ועמודת נתיב מוכרת.
     * הקוד מנסה כמה שמות עמודות נפוצים ולא נופל אם אין.
     */
    $possiblePhotoQueries = [
        "SELECT Photo_Path AS path FROM user_photos WHERE user_id = :id",
        "SELECT Image_Path AS path FROM user_photos WHERE user_id = :id",
        "SELECT File_Path AS path FROM user_photos WHERE user_id = :id",
        "SELECT photo_path AS path FROM user_photos WHERE user_id = :id",
        "SELECT image_path AS path FROM user_photos WHERE user_id = :id",
        "SELECT file_path AS path FROM user_photos WHERE user_id = :id",
    ];

    foreach ($possiblePhotoQueries as $photoSql) {
        try {
            $stmtPhotos = $pdo->prepare($photoSql);
            $stmtPhotos->execute([':id' => $userId]);
            $paths = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);

            if ($paths) {
                foreach ($paths as $path) {
                    $path = (string)$path;
                    if ($path === '') {
                        continue;
                    }

                    $fullPath = __DIR__ . '/' . ltrim($path, '/');
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                break;
            }
        } catch (Throwable $e) {
            // מנסים את השאילתה הבאה
        }
    }

    /*
     * מחיקות מכל הטבלאות הנפוצות במערכת
     * אפשר להוסיף פה עוד שורות לפי טבלאות שיש אצלך בפועל.
     */

    // חסימות
    safeDelete($pdo, "DELETE FROM blocked_users WHERE Id = :id OR Blocked_ById = :id", [':id' => $userId]);

    // צפיות - כמה וריאציות אפשריות
    safeDelete($pdo, "DELETE FROM profile_views WHERE Viewer_Id = :id OR Viewed_Id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM profile_views WHERE viewer_id = :id OR viewed_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM views WHERE Viewer_Id = :id OR Viewed_Id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM views WHERE viewer_id = :id OR viewed_id = :id", [':id' => $userId]);

    // הודעות - כמה וריאציות אפשריות
    safeDelete($pdo, "DELETE FROM messages WHERE Id = :id OR ToId = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM messages WHERE FromId = :id OR ToId = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM messages WHERE from_user_id = :id OR to_user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM inbox_messages WHERE from_user_id = :id OR to_user_id = :id", [':id' => $userId]);

    // שיחות
    safeDelete($pdo, "DELETE FROM conversations WHERE user1_id = :id OR user2_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM inbox_conversations WHERE user1_id = :id OR user2_id = :id", [':id' => $userId]);

    // תמונות
    safeDelete($pdo, "DELETE FROM user_photos WHERE user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM user_images WHERE user_id = :id", [':id' => $userId]);

    // לייקים / התאמות
    safeDelete($pdo, "DELETE FROM likes WHERE From_UserId = :id OR To_UserId = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM likes WHERE from_user_id = :id OR to_user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM matches WHERE User1_Id = :id OR User2_Id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM matches WHERE user1_id = :id OR user2_id = :id", [':id' => $userId]);

    // דיווחים
    safeDelete($pdo, "DELETE FROM reports WHERE reporter_id = :id OR reported_user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM user_reports WHERE reporter_id = :id OR reported_user_id = :id", [':id' => $userId]);

    // התראות
    safeDelete($pdo, "DELETE FROM notifications WHERE user_id = :id", [':id' => $userId]);

    // אימות מייל / טוקנים
    safeDelete($pdo, "DELETE FROM email_verifications WHERE user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM remember_tokens WHERE user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM password_resets WHERE user_id = :id", [':id' => $userId]);

    // פעילויות / לוגים אם יש
    safeDelete($pdo, "DELETE FROM login_logs WHERE user_id = :id", [':id' => $userId]);
    safeDelete($pdo, "DELETE FROM activity_log WHERE user_id = :id", [':id' => $userId]);

    // בסוף - מחיקת הפרופיל הראשי
    $stmt = $pdo->prepare("
        DELETE FROM users_profile
        WHERE Id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);

    $pdo->commit();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();

    echo json_encode([
        'ok' => true,
        'redirect' => '/'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'error' => 'שגיאה במחיקת החשבון'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}