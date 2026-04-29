<?php
// ===== FILE: mobile/manage_account.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /mobile/?page=login');
    exit;
}
?>

<div class="mobile-page-card">
    <h2>ניהול כרטיס</h2>

    <p>
        אפשר להקפיא את הכרטיס ולחזור בעתיד, או למחוק את הפרופיל לצמיתות.
    </p>

    <div class="mobile-account-actions">
        <button type="button" id="freezeAccountBtn" class="mobile-freeze-btn">
            הקפאת כרטיס
        </button>

        <button type="button" id="deleteAccountBtn" class="mobile-delete-btn">
            מחיקה מלאה
        </button>
    </div>

    <div id="accountManageMsg" class="mobile-account-msg"></div>
</div>

<style>
    .mobile-page-card {
        max-width: 420px;
        margin: 25px auto;
        padding: 22px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        text-align: center;
    }

    .mobile-page-card h2 {
        margin-bottom: 12px;
    }

    .mobile-page-card p {
        color: #555;
        line-height: 1.7;
    }

    .mobile-account-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
    }

    .mobile-account-actions button {
        border: none;
        border-radius: 14px;
        padding: 13px;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
    }

    .mobile-freeze-btn {
        background: #f59e0b;
    }

    .mobile-delete-btn {
        background: #e11d48;
    }

    .mobile-account-msg {
        margin-top: 14px;
        font-size: 14px;
    }
</style>

<script>
    async function postJson(url) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        return res.json();
    }

    document.getElementById('freezeAccountBtn')?.addEventListener('click', async function() {
        if (!confirm('להקפיא את הכרטיס? ניתן יהיה לשחזר אותו בעתיד.')) return;

        const msg = document.getElementById('accountManageMsg');
        msg.textContent = '';

        try {
            const data = await postJson('/freeze_account.php');

            if (data.ok) {
                window.location.href = data.redirect || '/mobile/';
            } else {
                msg.textContent = data.error || 'שגיאה בהקפאת הכרטיס';
            }
        } catch (e) {
            msg.textContent = 'שגיאה בתקשורת עם השרת';
        }
    });

    document.getElementById('deleteAccountBtn')?.addEventListener('click', async function() {
        if (!confirm('האם למחוק את הפרופיל לצמיתות?')) return;
        if (!confirm('אישור אחרון: כל הנתונים יימחקו לחלוטין ולא יהיה ניתן לשחזר.')) return;

        const msg = document.getElementById('accountManageMsg');
        msg.textContent = '';

        try {
            const data = await postJson('/delete_account.php');

            if (data.ok) {
                window.location.href = data.redirect || '/mobile/';
            } else {
                msg.textContent = data.error || 'שגיאה במחיקת הכרטיס';
            }
        } catch (e) {
            msg.textContent = 'שגיאה בתקשורת עם השרת';
        }
    });
</script>