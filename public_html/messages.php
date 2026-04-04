<?php
// ===== FILE: messages.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function age_from_dob($dob): string {
    $dob = trim((string)$dob);

    if ($dob === '') {
        return '';
    }

    try {
        $birth = new DateTime($dob);
        $today = new DateTime('today');
        return (string)$birth->diff($today)->y;
    } catch (Throwable $e) {
        return '';
    }
}

$viewerId = (int)($_SESSION['user_id'] ?? 0);

if ($viewerId <= 0) {
    echo "<div class='page-shell'><div class='no-views-box'>יש להתחבר כדי לצפות בהודעות.</div></div>";
    return;
}

/*
| כרטיס אחד לכל משתמש אחר
| last_date = זמן הודעה אחרונה בשיחה
| unread_count = כמה הודעות לא נקראו מהמשתמש האחר אליי
*/
$stmt = $pdo->prepare("
    SELECT
        u.Id,
        u.Name,
        u.DOB,
        u.Place_Str,
        u.Zone_Str,
        conv.last_date,
        conv.last_msg_num,
        COALESCE(unread.unread_count, 0) AS unread_count
    FROM
    (
        SELECT
            CASE
                WHEN m.ById = :me1 THEN m.Id
                ELSE m.ById
            END AS other_id,
            MAX(m.Date_Sent) AS last_date,
            MAX(m.Msg_Num) AS last_msg_num
        FROM messages m
        WHERE
            (
                m.ById = :me2
                AND (m.Deleted_By_ById = 0 OR m.Deleted_By_ById IS NULL)
            )
            OR
            (
                m.Id = :me3
                AND (m.Deleted_By_Id = 0 OR m.Deleted_By_Id IS NULL)
            )
        GROUP BY
            CASE
                WHEN m.ById = :me4 THEN m.Id
                ELSE m.ById
            END
    ) AS conv
    INNER JOIN users_profile u
        ON u.Id = conv.other_id
    LEFT JOIN
    (
        SELECT
            ById AS other_id,
            COUNT(*) AS unread_count
        FROM messages
        WHERE
            Id = :me5
            AND `New` = 1
            AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
        GROUP BY ById
    ) AS unread
        ON unread.other_id = conv.other_id
    ORDER BY conv.last_date DESC, conv.last_msg_num DESC
");

$stmt->execute([
    ':me1' => $viewerId,
    ':me2' => $viewerId,
    ':me3' => $viewerId,
    ':me4' => $viewerId,
    ':me5' => $viewerId
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* תמונות ראשיות */
$imagesByUser = [];
$userIds = array_map(static fn($row) => (int)$row['Id'], $rows);

if (!empty($userIds)) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));

    $picStmt = $pdo->prepare("
        SELECT p1.Id, p1.Pic_Name
        FROM user_pics p1
        INNER JOIN
        (
            SELECT Id, MAX(Pic_Num) AS max_pic_num
            FROM user_pics
            WHERE
                Main_Pic = 1
                AND Pic_Status = 1
                AND Id IN ($placeholders)
            GROUP BY Id
        ) p2
            ON p1.Id = p2.Id
           AND p1.Pic_Num = p2.max_pic_num
    ");
    $picStmt->execute($userIds);

    foreach ($picStmt->fetchAll(PDO::FETCH_ASSOC) as $picRow) {
        $imagesByUser[(int)$picRow['Id']] = '/uploads/' . ltrim((string)$picRow['Pic_Name'], '/');
    }
}
?>

<div class="page-shell">
    <h1 class="views-page-title">הודעות</h1>

    <?php if (empty($rows)): ?>
        <div class="no-views-box">עדיין אין לך שיחות.</div>
    <?php else: ?>
        <div class="views-list messages-list">
            <?php foreach ($rows as $row): ?>
                <?php
                $otherId = (int)$row['Id'];
                $name = trim((string)($row['Name'] ?? 'משתמש'));
                $age = age_from_dob($row['DOB'] ?? '');
                $place = trim((string)($row['Place_Str'] ?? ''));
                $zone = trim((string)($row['Zone_Str'] ?? ''));
                $lastDate = '';

                if (!empty($row['last_date'])) {
                    $ts = strtotime((string)$row['last_date']);
                    if ($ts) {
                        $lastDate = date('d/m/Y H:i', $ts);
                    }
                }

                $unreadCount = (int)($row['unread_count'] ?? 0);
                $image = $imagesByUser[$otherId] ?? '/images/no_photo.jpg';
                ?>

                <div class="view-card message-list-card">
                    <div class="view-card-media">
                        <img src="<?= e($image) ?>" alt="<?= e($name) ?>" class="view-card-image">

                        <?php if ($lastDate !== ''): ?>
                            <div class="view-card-time"><?= e($lastDate) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="view-card-content">
                        <div class="message-list-head">
                            <div class="view-card-name">
                                <?= e($name) ?><?= $age !== '' ? ', ' . e($age) : '' ?>
                            </div>

                            <?php if ($unreadCount > 0): ?>
                                <div class="message-unread-badge"><?= $unreadCount ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="view-card-divider"></div>

                        <div class="view-card-details">
                            <?php if ($place !== ''): ?>
                                <div>מקום מגורים: <?= e($place) ?></div>
                            <?php endif; ?>

                            <?php if ($zone !== ''): ?>
                                <div>אזור: <?= e($zone) ?></div>
                            <?php endif; ?>

                            <?php if ($lastDate !== ''): ?>
                                <div>הודעה אחרונה: <?= e($lastDate) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="message-list-actions">
                            <a href="?page=profile&id=<?= $otherId ?>" class="view-card-link">
                                מעבר לפרופיל
                            </a>

                            <a href="#"
                                class="view-card-link open-chat-from-list"
                                data-user-id="<?= $otherId ?>"
                                data-user-name="<?= e($name) ?>"
                                data-user-image="<?= e($image) ?>">
                                פתח חלון צ'אט
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.open-chat-from-list');
        if (!link) return;

        e.preventDefault();

        const userId = Number(link.getAttribute('data-user-id'));
        const userName = link.getAttribute('data-user-name') || 'משתמש';
        const userImage = link.getAttribute('data-user-image') || '/images/no_photo.jpg';

        if (!userId || typeof openMessageModal !== 'function') {
            return;
        }

        openMessageModal(
            userId,
            userName,
            userImage,
            window.chatViewer ? window.chatViewer.name : 'אני',
            window.chatViewer ? window.chatViewer.image : '/images/no_photo.jpg'
        );

        if (typeof markConversationAsRead === 'function') {
            markConversationAsRead(userId);
        }
    });
</script>