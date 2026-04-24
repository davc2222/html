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

function get_profile_image(PDO $pdo, int $userId): string {
    try {
        $stmt = $pdo->prepare("
            SELECT Pic_Name
            FROM user_pics
            WHERE Id = :id
              AND Main_Pic = 1
              AND Pic_Status = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $picName = $stmt->fetchColumn();

        if (!$picName) {
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id
                  AND Pic_Status = 1
                ORDER BY Main_Pic DESC, Pic_Num ASC
                LIMIT 1
            ");
            $stmt->execute([':id' => $userId]);
            $picName = $stmt->fetchColumn();
        }

        if ($picName) {
            return '/uploads/' . ltrim((string)$picName, '/');
        }
    } catch (Throwable $e) {
    }

    return '/images/no_photo.jpg';
}

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
                $user = $row;
                $id   = (int)$row['Id'];

                $user['Age'] = '';
                if (!empty($user['DOB'])) {
                    try {
                        $user['Age'] = date_diff(date_create((string)$user['DOB']), date_create('today'))->y;
                    } catch (Throwable $e) {
                        $user['Age'] = '';
                    }
                }

                $cardId = 'blocked-card-' . $id;
                $cardIconClass = 'vc-block';
                $cardTopBadge = '';
                $cardSubline = '';
                $cardShowOnline = false;
                $cardActionsHtml =
                    '<a href="/?page=profile&id=' . $id . '" class="view-card-profile-link">צפייה בפרופיל</a>
                     <span>|</span>
                     <a href="#" class="view-card-profile-link unblock-link" onclick="openUnblockConfirm(' . $id . '); return false;">בטל חסימה</a>';

                include __DIR__ . '/includes/view_card.php';
                ?>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<div id="unblockConfirmModal" class="unblock-modal" style="display:none;">
    <div class="unblock-modal-box">
        <div class="unblock-modal-title">לבטל חסימה?</div>
        <div class="unblock-modal-text">המשתמש יוסר מרשימת החסומים שלך.</div>

        <div class="unblock-modal-actions">
            <a href="#" class="unblock-modal-cancel" onclick="closeUnblockConfirm(); return false;">ביטול</a>
            <a href="#" class="unblock-modal-ok" onclick="confirmUnblock(); return false;">אשר</a>
        </div>
    </div>
</div>

<script>
    window.pendingUnblockUserId = 0;

    window.showToast = function(message, isError = false) {
        let toast = document.getElementById('siteToast');

        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'siteToast';
            toast.className = 'site-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.remove('is-error', 'show');

        if (isError) {
            toast.classList.add('is-error');
        }

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        clearTimeout(window.siteToastTimer);
        window.siteToastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    };

    window.openUnblockConfirm = function(userId) {
        window.pendingUnblockUserId = Number(userId || 0);

        const modal = document.getElementById('unblockConfirmModal');
        if (!modal) {
            console.error('unblockConfirmModal not found');
            return;
        }

        modal.style.display = 'flex';
    };

    window.closeUnblockConfirm = function() {
        window.pendingUnblockUserId = 0;

        const modal = document.getElementById('unblockConfirmModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.confirmUnblock = function() {
        const userId = window.pendingUnblockUserId;
        if (!userId) {
            window.closeUnblockConfirm();
            return;
        }

        const card = document.getElementById('blocked-card-' + userId);
        if (!card) {
            window.closeUnblockConfirm();
            window.showToast('הכרטיס לא נמצא', true);
            return;
        }

        const formData = new FormData();
        formData.append('blocked_id', userId);

        fetch('/unblock_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data = null;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON from unblock_user.php:', text);
                    window.closeUnblockConfirm();
                    window.showToast('תגובה לא תקינה מהשרת', true);
                    return;
                }

                window.closeUnblockConfirm();

                if (!data.ok) {
                    window.showToast(data.error || 'שגיאה בביטול החסימה', true);
                    return;
                }

                card.classList.add('is-removing');

                setTimeout(() => {
                    card.remove();

                    const list = document.querySelector('.views-list');
                    if (list && !list.querySelector('.view-card')) {
                        list.outerHTML = '<div class="no-views-box">אין חסומים עדיין</div>';
                    }
                }, 280);

                window.showToast('החסימה בוטלה');
            })
            .catch(err => {
                console.error(err);
                window.closeUnblockConfirm();
                window.showToast('שגיאה בשרת', true);
            });
    };
</script>