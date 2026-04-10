<?php
// ===== FILE: viewed_by_me.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

$session_user_id = (int)$_SESSION['user_id'];

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* שליפה */
$stmt = $pdo->prepare("
    SELECT
        v.Num,
        v.Date,
        up.*
    FROM views v
    JOIN users_profile up
        ON up.Id = v.Id
    WHERE v.ById = :me
      AND (v.Deleted_By_ById = 0 OR v.Deleted_By_ById IS NULL)
    ORDER BY v.Date DESC
");
$stmt->execute([':me' => $session_user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="page-shell">
    <section class="search-container">

        <h2 class="views-page-title">פרופילים שצפיתי בהם</h2>

        <?php if (!$results): ?>
            <div class="no-views-box">עדיין לא צפית בפרופילים</div>
        <?php else: ?>

            <div class="results">

                <?php foreach ($results as $row): ?>
                    <?php
                    $user = $row;
                    $num  = (int)$row['Num'];

                    /* גיל */
                    $user['Age'] = '';
                    if (!empty($user['DOB'])) {
                        try {
                            $user['Age'] = date_diff(
                                date_create((string)$user['DOB']),
                                date_create('today')
                            )->y;
                        } catch (Throwable $e) {
                            $user['Age'] = '';
                        }
                    }

                    /* תאריך צפייה */
                    $viewDate = '';
                    if (!empty($row['Date'])) {
                        try {
                            $viewDate = date('d/m/Y H:i', strtotime((string)$row['Date']));
                        } catch (Throwable $e) {
                            $viewDate = '';
                        }
                    }

                    /* קארד */
                    $cardId = 'viewed-card-' . $num;
                    $cardMode = 'viewed_by_me';
                    $cardTopBadge = '';
                    $cardSubline = $viewDate !== '' ? 'נצפה בתאריך: ' . $viewDate : '';

                    /* 🔥 אייקונים אחידים */
                    $cardIconsHtml = '
                    <div style="display:flex;justify-content:center;gap:10px;width:100%;">
                        <span title="צפיתי">↗️ 👁️</span>
                        <span title="צפו בי">↙️ 👁️</span>
                        <span title="הודעה נכנסת">↙️ 💬</span>
                        <span title="הודעה יוצאת">↗️ 💬</span>
                    </div>';

                    /* פעולות */
                    $cardActionsHtml =
                        '<a href="/?page=profile&id=' . (int)$user['Id'] . '" class="view-card-profile-link">צפייה בפרופיל</a>
                        <span>|</span>
                        <a href="#" class="view-card-profile-link" onclick="deleteViewedCard(' . $num . '); return false;">הסר מהרשימה</a>';

                    /* נתונים נוספים */
                    $user['Image'] = getMainProfileImage($pdo, (int)$user['Id']);
                    $user['is_online'] = is_user_online($pdo, (int)$user['Id']);

                    include __DIR__ . '/includes/view_card.php';
                    ?>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>
</main>

<script>
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

    window.deleteViewedCard = function(viewNum) {
        if (!confirm('להסיר מהרשימה?')) {
            return;
        }

        const card = document.getElementById('viewed-card-' + viewNum);
        if (!card) {
            showToast('הכרטיס לא נמצא', true);
            return;
        }

        const formData = new FormData();
        formData.append('view_num', viewNum);

        fetch('/delete_viewed_by_me.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data = null;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON:', text);
                    showToast('תגובה לא תקינה מהשרת', true);
                    return;
                }

                if (!data.ok) {
                    showToast(data.error || 'שגיאה בהסרה', true);
                    return;
                }

                card.classList.add('is-removing');

                setTimeout(() => {
                    card.remove();

                    const list = document.querySelector('.results');
                    if (list && !list.querySelector('.view-card')) {
                        list.outerHTML = '<div class="no-views-box">עדיין לא צפית בפרופילים</div>';
                    }
                }, 280);

                showToast('הוסר מהרשימה');
            })
            .catch(err => {
                console.error(err);
                showToast('שגיאה בשרת', true);
            });
    };
</script>