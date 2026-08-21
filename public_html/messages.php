<?php
// ===== FILE: messages.php =====

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_helpers.php';
require_once __DIR__ . '/includes/functions.php';

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

// ===== בדיקת מנוי =====
$hasPremium = hasActiveSubscription($pdo, $me);


/* ===== Mobile inline chat ===== */
$inlineChatUserId = (int)($_GET['user_id'] ?? 0);
$isInlineMobileChat = (($_GET['mobile_chat'] ?? '') === '1');

if ($isInlineMobileChat && $inlineChatUserId > 0 && $inlineChatUserId !== $me && $hasPremium) {
    ob_start();
    include __DIR__ . '/mobile/messages.php';
    $mobileChatHtml = ob_get_clean();

    // Keep /mobile/messages.php untouched; change only the rendered profile URL.
    $mobileChatHtml = str_replace(
        '/mobile/?page=profile&id=',
        '/?page=profile&id=',
        $mobileChatHtml
    );

    echo '
<style>
@media (max-width: 640px) {
    .chat-page {
        height: calc(100dvh - 230px) !important;
        min-height: calc(100dvh - 230px) !important;
        max-height: calc(100dvh - 230px) !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        overflow: hidden !important;
    }

    .chat-title-bar {
        flex: 0 0 auto !important;
        position: relative !important;
        top: 0 !important;
        margin-top: 0 !important;
        z-index: 5 !important;
    }

    .chat-messages {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        padding-bottom: 14px !important;
    }

    .chat-typing {
        flex: 0 0 auto !important;
    }

    .chat-send {
        flex: 0 0 auto !important;
        position: relative !important;
        bottom: 0 !important;
        z-index: 30 !important;
        margin-bottom: 0 !important;
    }
}
</style>
';

    echo '
<script>
(function () {
    function forcePageTop() {
        if ("scrollRestoration" in history) {
            history.scrollRestoration = "manual";
        }

        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        requestAnimationFrame(function () {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        });

        setTimeout(function () {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        }, 80);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", forcePageTop, { once: true });
    } else {
        forcePageTop();
    }
})();
</script>
';

    echo $mobileChatHtml;
    return;
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ===== שליפה ===== */
$stmt = $pdo->prepare("
    SELECT 
        up.*,
        MAX(m.Date_Sent) AS last_msg_date,
        COUNT(*) AS total_count,
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
      AND up.Is_Frozen = 0
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


            <style>
            .mobile-message-results{display:none}
            @media(max-width:640px){
              .desktop-message-results{display:none!important}
              .mobile-message-results{display:flex!important;flex-direction:column;gap:12px;width:100%}
              .mobile-message-card{background:#fff;border:1px solid #eee;border-radius:16px;box-shadow:0 4px 14px rgba(0,0,0,.05);overflow:hidden}
              .mobile-message-card-link{display:flex;align-items:center;gap:12px;width:100%;padding:12px;box-sizing:border-box;text-decoration:none;color:inherit;direction:rtl}
              .mobile-message-card-img{width:74px;height:74px;flex:0 0 74px;border-radius:14px;object-fit:cover;background:#f5f5f5}
              .mobile-message-card-info{min-width:0;flex:1 1 auto;display:flex;flex-direction:column;gap:6px}
              .mobile-message-card-name{font-size:17px;font-weight:700;color:#222;line-height:1.3}
              .mobile-message-card-date{font-size:13px;color:#777}
              .mobile-message-card-side{position:relative;flex:0 0 52px;min-width:52px;display:flex;align-items:center;justify-content:center}
              .mobile-message-total{width:38px;height:38px;border-radius:999px;background:#f3f3f3;border:1px solid #e8e8e8;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:800}
              .mobile-message-new{position:absolute;top:-17px;right:-4px;background:#e11d48;color:#fff;border-radius:999px;padding:3px 7px;font-size:10px;font-weight:800;white-space:nowrap}
            }
            </style>

            <div class="mobile-message-results">
            <?php foreach ($results as $mrow): ?>
              <?php
                $mid=(int)($mrow['other_user_id']??0);
                $mage='';
                if(!empty($mrow['DOB'])){try{$mage=date_diff(date_create((string)$mrow['DOB']),date_create('today'))->y;}catch(Throwable $e){}}
                $mimg=getMainProfileImage($pdo,$mid);
                $mlast='';
                if(!empty($mrow['last_msg_date'])){try{$mlast=(new DateTime((string)$mrow['last_msg_date']))->format('d/m/Y H:i');}catch(Throwable $e){}}
                $mtotal=(int)($mrow['total_count']??0);
                $munread=(int)($mrow['unread_count']??0);
              ?>
              <div class="mobile-message-card">
                <?php if($hasPremium): ?>
                  <a class="mobile-message-card-link" href="/?page=messages&mobile_chat=1&user_id=<?= $mid ?>">
                <?php else: ?>
                  <a class="mobile-message-card-link" href="#" onclick="if(typeof showSubscriptionPopup==='function'){showSubscriptionPopup();}else if(typeof openSubscriptionPopup==='function'){openSubscriptionPopup();}return false;">
                <?php endif; ?>
                  <img src="<?= h($mimg) ?>" class="mobile-message-card-img" onerror="this.onerror=null;this.src='/images/default_male.svg';" alt="">
                  <div class="mobile-message-card-info">
                    <div class="mobile-message-card-name"><?= h($mrow['Name']??'משתמש') ?><?= $mage!==''?', '.(int)$mage:'' ?></div>
                    <?php if($mlast!==''): ?><div class="mobile-message-card-date">הודעה אחרונה: <?= h($mlast) ?></div><?php endif; ?>
                  </div>
                  <div class="mobile-message-card-side">
                    <div class="mobile-message-total"><?= $mtotal ?></div>
                    <?php if($munread>0): ?><div class="mobile-message-new">הודעה חדשה</div><?php endif; ?>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
            </div>

            <div class="results desktop-message-results">

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

                    if ($hasPremium) {
                        $cardActionsHtml =
                            '<a href="#" class="view-card-profile-link" onclick="openMessageModal(' . $otherUserId . ', \''
                            . h($name) . '\', \''
                            . h($img) . '\'); return false;">פתח צ\'אט</a>
                             <span>|</span>
                             <a href="/?page=profile&id=' . $otherUserId . '" class="view-card-profile-link">פתח פרופיל</a>';
                    } else {
                        $cardActionsHtml =
                            '<a href="#" class="view-card-profile-link" onclick="
                                if (typeof showSubscriptionPopup === \'function\') {
                                    showSubscriptionPopup();
                                } else if (typeof openSubscriptionPopup === \'function\') {
                                    openSubscriptionPopup();
                                } else {
                                    window.location.href = \'/subscription.php?return=\' + encodeURIComponent(window.location.pathname + window.location.search);
                                }
                                return false;
                            ">פתח צ\'אט</a>
                             <span>|</span>
                             <a href="/?page=profile&id=' . $otherUserId . '" class="view-card-profile-link">פתח פרופיל</a>';
                    }

                    $user['Image'] = $img;
                    $user['is_online'] = is_user_online($pdo, $otherUserId);

                    include __DIR__ . '/includes/view_card.php';
                    ?>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>
</main>