<?php
// ===== FILE: contact.php =====
// תוכן פופאפ צור קשר לגרסה הרגילה / לוקאל

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$userEmail = trim((string)($_SESSION['user_email'] ?? ''));
?>

<h2 class="footer-popup-title">צור קשר</h2>

<p class="contact-desc">
    יש לך שאלה, תקלה, הצעה לשיפור או בקשה בנושא החשבון?
    שלח לנו הודעה ונחזור אליך בהקדם.
</p>

<div class="contact-email-box">
    📧 lovematch@lovematch.co.il
</div>

<form id="contactPopupForm" class="footer-popup-form">
    <input
        type="email"
        name="email"
        placeholder="הכנס אימייל..."
        value="<?= e($userEmail) ?>"
        <?= $userEmail !== '' ? 'readonly' : '' ?>
        required>

    <textarea
        name="message"
        rows="6"
        placeholder="כתוב כאן את ההודעה שלך..."
        required></textarea>

    <button type="submit" class="footer-popup-submit">שלח הודעה</button>

    <div id="contactPopupMsg" class="footer-popup-msg"></div>
</form>

<style>
    .contact-desc {
        text-align: center;
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 14px;
    }

    .contact-email-box {
        background: #fff;
        border: 1px solid #eee;
        padding: 12px;
        text-align: center;
        border-radius: 14px;
        margin-bottom: 15px;
        font-weight: bold;
        color: #333;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    }

    #contactPopupForm input,
    #contactPopupForm textarea {
        padding: 12px;
        border-radius: 14px;
        border: 1px solid #ddd;
        font-family: inherit;
        font-size: 14px;
        box-sizing: border-box;
        width: 100%;
    }

    #contactPopupForm input[readonly] {
        background: #f5f5f5;
        color: #666;
        cursor: not-allowed;
    }

    #contactPopupForm textarea {
        resize: vertical;
        min-height: 130px;
    }
</style>