
<style>
.verify-notice-page {
    padding: 60px 20px;
    display: flex;
    justify-content: center;
}

.verify-notice-box {
    width: 100%;
    max-width: 650px;
    background: #ffffff;
    border-radius: 22px;
    padding: 40px 32px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    text-align: center;
}

.verify-notice-box h1 {
    margin: 0 0 18px;
    color: #d6336c;
    font-size: 34px;
}

.verify-notice-box p {
    margin: 0 0 16px;
    font-size: 18px;
    line-height: 1.8;
    color: #444;
}

.verify-notice-actions {
    margin-top: 28px;
}

.verify-btn {
    display: inline-block;
    padding: 12px 24px;
    background: #d6336c;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
}

.verify-btn:hover {
    opacity: 0.92;
}
</style>


<?php
$status = $_GET['status'] ?? '';
?>

<main class="page-shell">
    <section class="verify-notice-page">
        <div class="verify-notice-box">
    <div class="verify-notice-box">
        <?php if ($status === 'verified'): ?>
            <h1>האימייל אומת בהצלחה</h1>
            <p>החשבון שלך הופעל בהצלחה.</p>
            <p>עכשיו אפשר להתחבר לאתר.</p>
            <div class="verify-notice-actions">
                <a href="/?page=login" class="verify-btn">לעמוד ההתחברות</a>
            </div>

        <?php elseif ($status === 'already_verified'): ?>
            <h1>האימייל כבר אומת</h1>
            <p>החשבון שלך כבר מאומת.</p>
            <div class="verify-notice-actions">
                <a href="/?page=login" class="verify-btn">לעמוד ההתחברות</a>
            </div>

        <?php elseif ($status === 'bad_token'): ?>
            <h1>קישור האימות אינו תקין</h1>
            <p>ייתכן שהקישור שגוי, פגום או שכבר בוצע בו שימוש.</p>
            <div class="verify-notice-actions">
                <a href="/?page=register" class="verify-btn">חזרה להרשמה</a>
            </div>

        <?php else: ?>
            <h1>בדיקת אימייל</h1>
            <p>לאחר ההרשמה יש להיכנס למייל וללחוץ על קישור האימות.</p>
            <div class="verify-notice-actions">
                <a href="/?page=home" class="verify-btn">חזרה לדף הבית</a>
            </div>
        <?php endif; ?>
    </div>
</section>