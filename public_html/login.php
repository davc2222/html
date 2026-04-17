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