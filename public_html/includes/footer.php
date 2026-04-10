<footer class="site-footer">
    <div class="footer-inner">

        <div class="footer-left">
            <span>© <?= date('Y') ?> LoveMatch</span>
        </div>

        <div class="footer-center">
            <a href="#">תנאי שימוש</a>
            <span>|</span>
            <a href="#">מדיניות פרטיות</a>
            <span>|</span>
            <button type="button" id="contactFooterLink" class="footer-link-btn">
                צור קשר
            </button>
        </div>

        <div class="footer-right">
            ❤️ נבנה עבורך
        </div>

    </div>
</footer>

<div id="contactPopupOverlay" class="contact-popup-overlay" style="display:none;">
    <div class="contact-popup-box">
        <button type="button" class="contact-popup-close" id="contactPopupCloseBtn">×</button>

        <h2 class="contact-popup-title">צור קשר</h2>

        <form id="contactPopupForm" class="contact-popup-form">
            <input type="text" name="name" placeholder="שם" required>
            <input type="email" name="email" placeholder="אימייל" required>
            <textarea name="message" rows="6" placeholder="כתוב את ההודעה שלך..." required></textarea>

            <button type="submit" class="contact-popup-submit">שליחה</button>

            <div id="contactPopupMsg" class="contact-popup-msg"></div>
        </form>
    </div>
</div>

<script>
    function openContactPopup() {
        const overlay = document.getElementById('contactPopupOverlay');
        if (!overlay) return;

        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeContactPopup() {
        const overlay = document.getElementById('contactPopupOverlay');
        if (!overlay) return;

        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const contactLink = document.getElementById('contactFooterLink');
        const overlay = document.getElementById('contactPopupOverlay');
        const closeBtn = document.getElementById('contactPopupCloseBtn');
        const form = document.getElementById('contactPopupForm');
        const msgBox = document.getElementById('contactPopupMsg');

        if (contactLink) {
            contactLink.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openContactPopup();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                closeContactPopup();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeContactPopup();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeContactPopup();
            }
        });

        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            msgBox.textContent = '';
            msgBox.className = 'contact-popup-msg';

            const formData = new FormData(form);

            try {
                const res = await fetch('/contact_send.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.ok) {
                    msgBox.textContent = 'ההודעה נשלחה בהצלחה';
                    msgBox.classList.add('success');
                    form.reset();

                    setTimeout(() => {
                        closeContactPopup();
                        msgBox.textContent = '';
                        msgBox.className = 'contact-popup-msg';
                    }, 1200);
                } else {
                    msgBox.textContent = data.error || 'שגיאה בשליחה';
                    msgBox.classList.add('error');
                }
            } catch (err) {
                msgBox.textContent = 'שגיאה בתקשורת עם השרת';
                msgBox.classList.add('error');
            }
        });
    });
</script>