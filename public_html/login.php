<?php
$error = $_GET['error'] ?? '';
?>

<style>
.login-page {
    padding: 40px 20px 60px;
    min-height: calc(100vh - 88px);
}

.login-box {
    max-width: 520px;
    margin: 0 auto;
    background: #fff;
    border-radius: 24px;
    padding: 36px 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.login-box h1 {
    text-align: center;
    color: #d91f4f;
    font-size: 38px;
    margin-bottom: 10px;
}

.login-subtitle {
    text-align: center;
    color: #666;
    font-size: 17px;
    margin-bottom: 26px;
}

.login-message {
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 12px;
    text-align: center;
    font-weight: bold;
    font-size: 15px;
}

.login-message.error {
    background: #ffe2e7;
    color: #b3153e;
    border: 1px solid #f3b4c3;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.login-form label {
    font-weight: bold;
    color: #444;
    margin-bottom: 6px;
    display: block;
}

.login-form input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 12px;
    font-size: 16px;
    background: #fafafa;
}

.login-form input:focus {
    outline: none;
    border-color: #ff4d6d;
    box-shadow: 0 0 0 3px rgba(255, 77, 109, 0.12);
}

.password-wrap {
    position: relative;
}

.password-wrap input {
    padding-left: 52px;
}

.toggle-pass {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 18px;
}

.login-submit {
    margin-top: 8px;
    border: none;
    background: #ff4d6d;
    color: #fff;
    border-radius: 14px;
    padding: 14px 20px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.25s ease;
}

.login-submit:hover {
    background: #d91f4f;
}

.login-links {
    margin-top: 22px;
    text-align: center;
    color: #555;
}

.login-links a {
    color: #d91f4f;
    font-weight: bold;
}
</style>

<main class="page-shell">
    <section class="login-page">
        <div class="login-box">
            <h1>התחברות</h1>
            <p class="login-subtitle">ברוך הבא ל־LoveMatch</p>

            <?php if ($error === 'missing'): ?>
                <div class="login-message error">יש למלא אימייל וסיסמה.</div>
            <?php elseif ($error === 'badLogin'): ?>
                <div class="login-message error">אימייל או סיסמה שגויים.</div>
            <?php elseif ($error === 'notVerified'): ?>
                <div class="login-message error">צריך לאמת את המייל לפני התחברות.</div>
            <?php endif; ?>

            <form class="login-form" action="/login_action.php" method="POST">
                <div>
                    <label for="Email">אימייל</label>
                    <input type="email" id="Email" name="Email" required>
                </div>

                <div>
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