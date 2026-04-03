<?php
// ===== FILE: login.php =====

$error = $_GET['error'] ?? '';
$verified = $_GET['verified'] ?? '';
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

<script>
function togglePassword() {
    const input = document.getElementById('Pass');
    input.type = (input.type === 'password') ? 'text' : 'password';
}
</script>