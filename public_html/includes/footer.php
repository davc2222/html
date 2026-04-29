<?php
// ================================
// FOOTER DESKTOP (רגיל)
// ================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);
?>

<footer class="site-footer">
    <div class="footer-inner">

        <div class="footer-left">
            <span>© <?= date('Y') ?> LoveMatch</span>
        </div>

        <div class="footer-center">
            <button type="button" id="termsFooterLink" class="footer-link-btn">תנאי שימוש</button>
            <span>|</span>

            <button type="button" id="privacyFooterLink" class="footer-link-btn">מדיניות פרטיות</button>
            <span>|</span>

            <button type="button" id="contactFooterLink" class="footer-link-btn">צור קשר</button>

            <a href="/?mobile=1" class="go-mobile-btn" title="מעבר לגרסת מובייל">📱</a>

            <?php if ($isLoggedIn): ?>
                <span>|</span>
                <button type="button" id="accountManageFooterLink" class="footer-link-btn account-manage-footer-link">ניהול כרטיס</button>
            <?php endif; ?>
        </div>

        <div class="footer-right">
            ❤️ נבנה עבורך
        </div>

    </div>
</footer>
