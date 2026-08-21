<?php
// ===== FILE: login.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = $_GET['error'] ?? '';
$verified = $_GET['verified'] ?? '';
// ב-login.php שורה 11 בערך:
$showFrozenRestorePopup = (
    isset($_GET['frozen_restore']) && // לוודא שזה מגיע מה-URL באופן מפורש
    !empty($_SESSION['restore_user_id'])
);
?>

<style>
@media (max-width:640px){
.page-shell{width:100%!important;max-width:none!important;margin:0!important;padding:0!important}
.login-page{width:100%!important;min-height:calc(100vh - 190px)!important;padding:28px 14px 105px!important;margin:0!important;box-sizing:border-box!important;display:flex!important;align-items:flex-start!important;justify-content:center!important}
.login-box{width:100%!important;max-width:400px!important;margin:0 auto!important;padding:28px 14px 24px!important;box-sizing:border-box!important;border-radius:22px!important}
.login-box h1{margin:0 0 5px!important;font-size:25px!important;line-height:1.25!important;text-align:center!important}
.login-subtitle{margin:0 0 24px!important;font-size:13px!important;text-align:center!important}
.login-form,.form-row,.password-wrap{width:100%!important;max-width:none!important;min-width:0!important;box-sizing:border-box!important}
.login-form{display:block!important}
.form-row{margin:0 0 16px!important}
.form-row label{display:block!important;margin:0 0 6px!important;font-size:13px!important;font-weight:700!important;text-align:right!important}
.form-row input{display:block!important;width:100%!important;max-width:none!important;min-width:0!important;height:46px!important;padding:0 14px!important;box-sizing:border-box!important;border-radius:12px!important;font-size:14px!important}
.password-wrap{position:relative!important}
.password-wrap input{padding-left:44px!important}
.password-wrap .toggle-pass{position:absolute!important;left:12px!important;right:auto!important;top:50%!important;transform:translateY(-50%)!important;margin:0!important}
.login-submit{width:100%!important;max-width:none!important;min-width:0!important;height:48px!important;margin:6px 0 0!important;box-sizing:border-box!important;border-radius:12px!important}
.login-links{margin-top:20px!important;text-align:center!important;font-size:13px!important}
.login-message{width:100%!important;box-sizing:border-box!important;margin-bottom:16px!important}
}
</style>
<main class="page-shell">
    <section class="login-page">
        <div class="login-box">
            <h1>התחברות</h1>
            <p class="login-subtitle">ברוך הבא ל־LoveMatch</p>

            <?php if ($verified === '1'): ?>
                <div class="login-message success">האימייל אומת בהצלחה. עכשיו אפשר להתחבר.</div>
            <?php endif; ?>

            <?php if ($error === 'missing'): ?>
                <div class="login-message error">יש למלא אימייל וסיסמה.</div>
            <?php elseif ($error === 'badLogin'): ?>
                <div class="login-message error">אימייל או סיסמה שגויים.</div>
            <?php elseif ($error === 'notVerified'): ?>
                <div class="login-message error">צריך לאמת את המייל לפני התחברות.</div>
            <?php endif; ?>

            <form class="login-form" action="/login_action.php" method="POST" autocomplete="off">
                <div class="form-row">
                    <label for="Email">אימייל</label>
                    <input type="email" id="Email" name="Email" required>
                </div>

                <div class="form-row">
                    <label for="Pass">סיסמה</label>
                    <div class="password-wrap">
                        <input type="password" id="Pass" name="Pass" required>
                        <button type="button" class="toggle-pass" onclick="togglePassword()">👁</button>
                    </div>
                </div>

                <button type="submit" class="login-submit">התחבר</button>
            </form>

            <div class="login-links">
                אין לך חשבון?
                <a href="/?page=register">להרשמה</a>
            </div>
        </div>
    </section>
</main>

<?php if ($showFrozenRestorePopup): ?>
    <div id="restoreFrozenModal" class="footer-popup-overlay" style="display:flex;">
        <div class="footer-popup-box">
            <h2 class="footer-popup-title">הפרופיל מוקפא</h2>

            <div class="terms-content" style="text-align:right;">
                <p>הפרופיל שלך מוקפא כרגע. האם תרצה/י לשחזר אותו?</p>

                <div class="account-manage-actions">
                    <button type="button" id="restoreFrozenYesBtn" class="footer-popup-submit account-freeze-btn">
                        כן, שחזרו את הפרופיל
                    </button>

                    <button type="button" id="restoreFrozenNoBtn" class="footer-popup-submit">
                        לא
                    </button>
                </div>

                <div id="restoreFrozenMsg" class="footer-popup-msg"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function togglePassword() {
        const input = document.getElementById('Pass');
        input.type = (input.type === 'password') ? 'text' : 'password';
    }

    <?php if ($showFrozenRestorePopup): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const yesBtn = document.getElementById('restoreFrozenYesBtn');
            const noBtn = document.getElementById('restoreFrozenNoBtn');
            const msgBox = document.getElementById('restoreFrozenMsg');

            if (yesBtn) {
                yesBtn.addEventListener('click', async function() {
                    msgBox.textContent = '';
                    msgBox.className = 'footer-popup-msg';

                    try {
                        const res = await fetch('/restore_account.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await res.json();

                        if (data.ok) {
                            window.location.href = data.redirect || '/?page=login';
                        } else {
                            msgBox.textContent = data.error || 'שגיאה בשחזור הפרופיל';
                            msgBox.classList.add('error');
                        }
                    } catch (err) {
                        msgBox.textContent = 'שגיאה בתקשורת עם השרת';
                        msgBox.classList.add('error');
                    }
                });
            }

            if (noBtn) {
                noBtn.addEventListener('click', async function() {
                    try {
                        await fetch('/clear_restore_session.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                    } catch (err) {
                        // גם אם נכשל, נמשיך לדף התחברות
                    }

                    window.location.href = '/?page=login';
                });
            }
        });
    <?php endif; ?>
</script>