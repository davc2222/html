<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$session_user_id = (int)$_SESSION['user_id'];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* שליפה כמו צפיות — רק מחסומים */
$stmt = $pdo->prepare("
    SELECT up.*
    FROM blocked_users bu
    JOIN users_profile up ON up.Id = bu.Id
    WHERE bu.Blocked_ById = :me
    ORDER BY bu.Created_At DESC
");
$stmt->execute([':me' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-shell">

    <h2 class="views-page-title">פרופילים שחסמתי</h2>

    <?php if (!$results): ?>
        <div class="no-views-box">אין חסומים עדיין</div>
    <?php else: ?>

        <div class="views-list">

            <?php foreach ($results as $row): ?>

                <?php
                $id   = (int)$row['Id'];
                $name = trim((string)$row['Name']);

                $age = '';
                if (!empty($row['DOB'])) {
                    try {
                        $age = date_diff(date_create($row['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                    }
                }

                $img = '/images/no_photo.jpg';
                ?>

                <div class="view-card" id="blocked-card-<?= $id ?>">

                    <div class="view-card-media">
                        <img src="<?= h($img) ?>" class="view-card-image">
                    </div>

                    <div class="view-card-content">

                        <div class="view-card-name">
                            <?= h($name) ?>
                            <?= $age ? ', ' . $age : '' ?>
                        </div>

                        <div class="view-card-divider"></div>

                        <div class="view-card-details">
                            <div>אזור: <?= h($row['Zone_Str'] ?? '') ?></div>
                            <div>גובה: <?= h($row['Height_Str'] ?? '') ?></div>
                        </div>

                        <div class="blocked-card-actions">

                            <a class="view-card-link" href="/?page=profile&id=<?= $id ?>">
                                צפייה בפרופיל
                            </a>

                            <button class="unblock-user-btn"
                                onclick="unblockUser(<?= $id ?>)">
                                בטל חסימה
                            </button>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>
    function unblockUser(userId) {

        if (!confirm('לבטל חסימה למשתמש זה?')) {
            return;
        }

        const formData = new FormData();
        formData.append('blocked_id', userId);

        fetch('/unblock_user.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    alert('שגיאה');
                    return;
                }

                document.getElementById('blocked-card-' + userId)?.remove();
            })
            .catch(() => alert('שגיאה'));
    }
</script>