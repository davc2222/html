<?php
// ===== FILE: messages.php =====

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    try {
        $stmtPresence = $pdo->prepare("
            UPDATE users_profile
            SET last_seen = NOW()
            WHERE Id = :id
            LIMIT 1
        ");
        $stmtPresence->execute([':id' => (int)$_SESSION['user_id']]);
    } catch (Throwable $e) {
        // לא להפיל דף בגלל נוכחות
    }
}

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$me = (int)$_SESSION['user_id'];
$session_user_id = $me;

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ===== שליפה ===== */
$stmt = $pdo->prepare("
    SELECT 
        up.*,
        MAX(m.Date_Sent) AS last_msg_date,
        SUM(
            CASE 
                WHEN m.Id = :me 
                 AND m.`New` = 1
                 AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL)
                THEN 1 
                ELSE 0 
            END
        ) AS unread_count,
        CASE 
            WHEN m.ById = :me THEN m.Id
            ELSE m.ById
        END AS other_user_id
    FROM messages m
    JOIN users_profile up 
        ON up.Id = CASE 
            WHEN m.ById = :me THEN m.Id
            ELSE m.ById
        END
    WHERE (m.Id = :me OR m.ById = :me)
      AND (
            (m.Id = :me AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL))
         OR (m.ById = :me AND (m.Deleted_By_ById = 0 OR m.Deleted_By_ById IS NULL))
      )
      AND NOT EXISTS (
            SELECT 1
            FROM blocked_users bu
            WHERE (bu.Id = up.Id AND bu.Blocked_ById = :me)
               OR (bu.Id = :me AND bu.Blocked_ById = up.Id)
      )
    GROUP BY other_user_id
    ORDER BY last_msg_date DESC
");

$stmt->execute([':me' => $me]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="page-shell messages-page">
    <section class="search-container">

        <h2 class="views-page-title">הודעות</h2>

        <?php if (!$results): ?>
            <div class="no-results">אין הודעות</div>
        <?php else: ?>

            <div class="results">

                <?php foreach ($results as $row): ?>
                    <?php
                    $user = $row;

                    $otherUserId = (int)($row['other_user_id'] ?? 0);
                    $user['Id'] = $otherUserId;

                    $user['Age'] = '';
                    if (!empty($user['DOB'])) {
                        try {
                            $user['Age'] = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                        } catch (Throwable $e) {
                            $user['Age'] = '';
                        }
                    }

                    $unread = (int)($row['unread_count'] ?? 0);
                    $name   = trim((string)($user['Name'] ?? ''));
                    $img    = getMainProfileImage($pdo, $otherUserId);

                    $cardId = '';
                    $cardTopBadge = $unread > 0 ? '💬 ' . $unread . ' חדשות' : '';
                    $cardSubline = '';
                    $cardShowOnline = true;

                    /* חשוב: לא מגדירים $cardIconsHtml כאן
                       הקארד עצמו בונה את האייקונים מתוך 4 השאילתות שלו */

                    $cardActionsHtml =
                        '<a href="#" class="view-card-profile-link" onclick="openMessageModal(' . $otherUserId . ', \''
                        . h($name) . '\', \''
                        . h($img) . '\'); return false;">פתח צ\'אט</a>
                         <span>|</span>
                         <a href="/?page=profile&id=' . $otherUserId . '" class="view-card-profile-link">פתח פרופיל</a>';

                    $user['Image'] = $img;
                    $user['is_online'] = is_user_online($pdo, $otherUserId);

                    include __DIR__ . '/includes/view_card.php';
                    ?>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>
</main>