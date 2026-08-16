<?php
// ===== FILE: subscription_popup.php =====
// פופאפ מנוי Premium עצמאי - נטען פעם אחת דרך index.php
?>

<div id="subscriptionPopupOverlay" class="subscription-popup-overlay" style="display:none;">
    <div class="subscription-popup-card" role="dialog" aria-modal="true" aria-labelledby="subscriptionPopupTitle">

        <button
            type="button"
            class="subscription-popup-close"
            onclick="closeSubscriptionPopup()"
            aria-label="סגור">
            ×
        </button>

        <div class="subscription-popup-icon">💎</div>

        <h2 id="subscriptionPopupTitle" class="subscription-popup-title">
            פתיחת הודעות זמינה למנויי Premium
        </h2>

        <p class="subscription-popup-text">
            כדי לקרוא ולשלוח הודעות יש צורך במנוי פעיל.
        </p>

        <div class="subscription-popup-price">39.90 ₪</div>
        <div class="subscription-popup-period">לחודש אחד בלבד</div>

        <div class="subscription-popup-benefits">
            <div>✓ קריאת הודעות</div>
            <div>✓ שליחת הודעות ללא הגבלה</div>
            <div>✓ גישה מלאה לצ׳אט</div>
        </div>

        <div class="subscription-popup-trust">
            <span>🔒 תשלום מאובטח</span>
            <span>✓ ללא חידוש אוטומטי</span>
            <span>✓ חיוב חד־פעמי בלבד</span>
        </div>

        <a href="/subscription.php?return=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>" class="subscription-popup-buy">
            רכישת מנוי Premium
        </a>

        <button
            type="button"
            class="subscription-popup-later"
            onclick="closeSubscriptionPopup()">
            אולי אחר כך
        </button>
    </div>
</div>

<style>
.subscription-popup-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000000;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    direction: rtl;
}

.subscription-popup-card {
    position: relative;
    width: min(470px, calc(100vw - 32px));
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    background: #ffffff;
    border-radius: 22px;
    padding: 34px 30px 28px;
    box-sizing: border-box;
    text-align: center;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
}

.subscription-popup-close {
    position: absolute;
    top: 12px;
    left: 14px;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    font-size: 26px;
    line-height: 1;
    cursor: pointer;
}

.subscription-popup-icon {
    font-size: 46px;
    line-height: 1;
    margin: 4px 0 12px;
}

.subscription-popup-title {
    margin: 0 0 12px;
    color: #1d4ed8;
    font-size: 25px;
    line-height: 1.3;
    font-weight: 900;
}

.subscription-popup-text {
    margin: 0 auto 18px;
    color: #64748b;
    font-size: 16px;
    line-height: 1.7;
}

.subscription-popup-price {
    color: #0f172a;
    font-size: 42px;
    line-height: 1;
    font-weight: 900;
}

.subscription-popup-period {
    margin-top: 8px;
    color: #334155;
    font-size: 16px;
    font-weight: 800;
}

.subscription-popup-benefits {
    margin: 22px auto 18px;
    max-width: 330px;
    padding: 16px 18px;
    border-radius: 14px;
    background: #f8fafc;
    color: #334155;
    text-align: right;
    line-height: 2;
    font-size: 15px;
    font-weight: 700;
}

.subscription-popup-trust {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin: 18px 0 22px;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
}

.subscription-popup-buy {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 14px 18px;
    border-radius: 12px;
    background: #2563eb;
    color: #ffffff;
    text-decoration: none;
    font-size: 18px;
    font-weight: 900;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.22);
}

.subscription-popup-buy:hover {
    background: #1d4ed8;
}

.subscription-popup-later {
    margin-top: 12px;
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 14px;
    cursor: pointer;
    text-decoration: underline;
}

@media (max-width: 700px) {
    .subscription-popup-card {
        padding: 30px 20px 24px;
    }

    .subscription-popup-title {
        font-size: 21px;
    }

    .subscription-popup-price {
        font-size: 36px;
    }
}
</style>

<script>
(function () {
    'use strict';

    function getSubscriptionPopup() {
        return document.getElementById('subscriptionPopupOverlay');
    }

    window.openSubscriptionPopup = function () {
        const popup = getSubscriptionPopup();

        if (!popup) {
            console.error('subscriptionPopupOverlay not found');
            return false;
        }

        popup.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        return false;
    };

    window.showSubscriptionPopup = window.openSubscriptionPopup;

    window.closeSubscriptionPopup = function () {
        const popup = getSubscriptionPopup();

        if (!popup) {
            return false;
        }

        popup.style.display = 'none';
        document.body.style.overflow = '';
        return false;
    };

    document.addEventListener('click', function (event) {
        const popup = getSubscriptionPopup();

        if (popup && event.target === popup) {
            window.closeSubscriptionPopup();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            window.closeSubscriptionPopup();
        }
    });
})();
</script>