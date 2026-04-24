<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$contactEmail = trim((string)($_SESSION['user_email'] ?? ''));
$contactName  = trim((string)($_SESSION['user_name'] ?? ($_SESSION['username'] ?? '')));
$isLoggedIn   = !empty($_SESSION['user_id']);
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
            <a href="/?mobile=1" class="go-mobile-btn" title="מעבר לגרסת מובייל"> 📱  </a>
            <?php if ($isLoggedIn): ?>
                <span>|</span>
                <button type="button" id="accountManageFooterLink" class="footer-link-btn footer-link-danger">
                    ניהול כרטיס
                </button>
            <?php endif; ?>
        </div>

        <div class="footer-right">
            ❤️ נבנה עבורך
        </div>

    </div>
</footer>

<!-- =======================
     CONTACT POPUP
======================= -->
<div id="contactPopupOverlay" class="footer-popup-overlay" style="display:none;">
    <div class="footer-popup-box">
        <button type="button" class="footer-popup-close" id="contactPopupCloseBtn">×</button>

        <h2 class="footer-popup-title">צור קשר</h2>

        <form id="contactPopupForm" class="footer-popup-form">
            <input
                type="text"
                name="name"
                placeholder="שם"
                value="<?= htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') ?>"
                required>

            <input
                type="email"
                name="email"
                placeholder="אימייל"
                value="<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>"
                required>

            <textarea
                name="message"
                rows="6"
                placeholder="כתוב את ההודעה שלך..."
                required></textarea>

            <button type="submit" class="footer-popup-submit">שליחה</button>

            <div id="contactPopupMsg" class="footer-popup-msg"></div>
        </form>
    </div>
</div>

<!-- =======================
     TERMS POPUP
======================= -->
<div id="termsPopupOverlay" class="footer-popup-overlay" style="display:none;">
    <div class="footer-popup-box footer-popup-box-terms">
        <button type="button" class="footer-popup-close" id="termsPopupCloseBtn">×</button>

        <h2 class="footer-popup-title">תנאי שימוש</h2>

        <div class="terms-content">
            <p>
                התקנון ותנאי השימוש באתר <strong>LoveMatch</strong> מנוסחים בלשון זכר לצרכי נוחות,
                אך האמור מתייחס לכל המינים. הרשמה ושימוש באתר מהווים הסכמה לתנאים אלה.
            </p>

            <h3>התחייבויות המשתמש</h3>
            <ul>
                <li>המשתמש מצהיר כי גילו מעל 18 שנים.</li>
                <li>המשתמש מתחייב למסור פרטים נכונים, עדכניים ואמיתיים.</li>
                <li>המשתמש אחראי באופן אישי לכל תוכן, טקסט, תמונה או מידע שיפרסם באתר.</li>
                <li>אין להציג זהות בדויה או להתחזות לאחר.</li>
                <li>אין להשתמש בשפה פוגענית, קללות, איומים, תכנים פורנוגרפיים, אלימים או בלתי חוקיים.</li>
                <li>אין להטריד משתמשים אחרים, להציק להם או לחדש קשר כאשר הובהר שאין עניין בכך.</li>
                <li>אין להשתמש באתר למטרות מסחריות, פרסום, שידול או הצעת שירותים ללא אישור מפורש.</li>
                <li>אין לפרסם בפרופיל הפומבי פרטי קשר כמו טלפון, אימייל, פייסבוק, אינסטגרם או קישורים חיצוניים.</li>
                <li>אין לפתוח יותר מחשבון אחד לאותו משתמש.</li>
                <li>אין להשתמש באמצעים אוטומטיים או בפעולות העלולות ליצור עומס על השרת.</li>
                <li>כל תוכן או התנהגות הקשורים לפגיעה, ניצול או התעללות בקטינים אסורים בהחלט.</li>
            </ul>

            <h3>זכויות מפעילת האתר</h3>
            <ul>
                <li>לשנות את מבנה האתר, השירותים והתכנים בכל עת.</li>
                <li>להציע שירותים בחינם ובתשלום.</li>
                <li>לעדכן את תנאי השימוש, המחירים ותוקף השירותים ללא הודעה מוקדמת.</li>
                <li>לערוך, להסיר או לשנות תוכן שאינו עומד בתנאי האתר.</li>
                <li>לפעול נגד משתמשים שמפרים את התקנון, לרבות חסימה או הסרה של חשבון.</li>
            </ul>

            <h3>אחריות</h3>
            <ul>
                <li>מפעילת האתר אינה אחראית לאמינות, דיוק או חוקיות של תוכן שמפורסם על ידי משתמשים.</li>
                <li>אין התחייבות כי הודעות שיישלחו באתר יקבלו מענה.</li>
                <li>מפעילת האתר אינה אחראית לכל נזק ישיר או עקיף, אובדן מידע או פגיעה שייגרמו עקב השימוש באתר.</li>
            </ul>

            <h3>שירותים בתשלום</h3>
            <p>
                ההרשמה לאתר היא ללא תשלום, אך חלק מהשירותים באתר עשויים להיות בתשלום,
                כגון מנוי להתכתבות, הדגשת פרופיל, הקפצת פרופיל, מתנות וירטואליות ושירותים נוספים.
                מחירי השירותים עשויים להתעדכן מעת לעת.
            </p>

            <h3>מחיקת פרופיל וביטול שירות</h3>
            <p>
                ניתן למחוק את הפרופיל בכל עת דרך ההגדרות באתר או בפנייה לשירות הלקוחות.
                מחיקת הפרופיל אינה בהכרח מבטלת חיובים מתחדשים, ועל המשתמש לוודא ביטול מנוי מתחדש
                לפי אופן התשלום שבו השתמש.
            </p>

            <h3>החזרים</h3>
            <p>
                ביטול עסקה והחזרים יתבצעו בהתאם להוראות הדין הישראלי, ובפרט בהתאם לחוק הגנת הצרכן.
            </p>

            <h3>סמכות שיפוט</h3>
            <p>
                כל עניין הנוגע לשימוש באתר יהיה כפוף לדין הישראלי בלבד,
                ובסמכותם הבלעדית של בתי המשפט המוסמכים בחיפה.
            </p>
        </div>
    </div>
</div>

<!-- =======================
     PRIVACY POPUP
======================= -->
<div id="privacyPopupOverlay" class="footer-popup-overlay" style="display:none;">
    <div class="footer-popup-box footer-popup-box-terms">
        <button type="button" class="footer-popup-close" id="privacyPopupCloseBtn">×</button>

        <h2 class="footer-popup-title">מדיניות פרטיות</h2>

        <div class="terms-content">
            <p>
                מדיניות פרטיות זו מסבירה כיצד אתר <strong>LoveMatch</strong> אוסף, משתמש ושומר מידע אישי של המשתמשים.
                השימוש באתר מהווה הסכמה למדיניות זו.
            </p>

            <h3>איסוף מידע</h3>
            <p>
                בעת הרשמה לאתר נאסף מידע כגון שם משתמש, כתובת אימייל, תאריך לידה ופרטים נוספים שהמשתמש בוחר להזין בפרופיל.
            </p>

            <h3>שימוש במידע</h3>
            <ul>
                <li>ניהול חשבון המשתמש והפעלת האתר</li>
                <li>התאמת תכנים והצגת התאמות בין משתמשים</li>
                <li>שליחת הודעות והתראות בתוך האתר</li>
                <li>שיפור חוויית המשתמש</li>
            </ul>

            <h3>שמירת מידע</h3>
            <p>
                המידע נשמר במאגרי המידע של האתר ומוגן באמצעים סבירים בהתאם לנהוג בתחום.
            </p>

            <h3>שיתוף מידע</h3>
            <p>
                האתר אינו מוכר ואינו מעביר מידע אישי לצדדים שלישיים, למעט מקרים בהם נדרש על פי חוק
                או לצורך תפעול השירות כגון ספקי אחסון.
            </p>

            <h3>קבצי Cookies</h3>
            <p>
                האתר עשוי להשתמש בקבצי Cookies לצורך תפעול תקין, שמירת העדפות משתמש ושיפור השירות.
            </p>

            <h3>אבטחת מידע</h3>
            <p>
                מפעילת האתר נוקטת באמצעים סבירים לאבטחת המידע, אך אינה יכולה להבטיח חסינות מוחלטת מפני פריצות.
            </p>

            <h3>מחיקת מידע</h3>
            <p>
                המשתמש רשאי לבקש מחיקת חשבונו והמידע המשויך אליו באמצעות פנייה דרך האתר או דרך אפשרויות ניהול הכרטיס.
            </p>

            <h3>יצירת קשר</h3>
            <p>
                בכל שאלה בנושא פרטיות ניתן לפנות דרך טופס "צור קשר" באתר.
            </p>

            <p class="terms-note">
                מדיניות זו עשויה להתעדכן מעת לעת.
            </p>
        </div>
    </div>
</div>

<?php if ($isLoggedIn): ?>
    <!-- =======================
     ACCOUNT MANAGE POPUP
======================= -->
    <div id="accountManagePopupOverlay" class="footer-popup-overlay" style="display:none;">
        <div class="footer-popup-box">
            <button type="button" class="footer-popup-close" id="accountManagePopupCloseBtn">×</button>

            <h2 class="footer-popup-title">ניהול כרטיס</h2>

            <div class="terms-content" style="text-align:right;">
                <p>
                    אפשר להקפיא את הכרטיס ולחזור בעתיד, או למחוק את הפרופיל לצמיתות.
                </p>

                <div class="account-manage-actions">
                    <button type="button" id="freezeAccountBtn" class="footer-popup-submit account-freeze-btn">
                        הקפאת כרטיס
                    </button>

                    <button type="button" id="deleteAccountBtn" class="footer-popup-submit account-delete-btn">
                        מחיקה מלאה
                    </button>
                </div>

                <div id="accountManageMsg" class="footer-popup-msg"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    (function() {
        function openPopup(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePopup(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'none';
            document.body.style.overflow = '';
        }

        window.openContactPopup = function() {
            openPopup('contactPopupOverlay');
        };
        window.closeContactPopup = function() {
            closePopup('contactPopupOverlay');
        };

        window.openTermsPopup = function() {
            openPopup('termsPopupOverlay');
        };
        window.closeTermsPopup = function() {
            closePopup('termsPopupOverlay');
        };

        window.openPrivacyPopup = function() {
            openPopup('privacyPopupOverlay');
        };
        window.closePrivacyPopup = function() {
            closePopup('privacyPopupOverlay');
        };

        window.openAccountManagePopup = function() {
            openPopup('accountManagePopupOverlay');
        };
        window.closeAccountManagePopup = function() {
            closePopup('accountManagePopupOverlay');
        };

        async function postJson(url) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            return res.json();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const contactLink = document.getElementById('contactFooterLink');
            const termsLink = document.getElementById('termsFooterLink');
            const privacyLink = document.getElementById('privacyFooterLink');
            const accountManageLink = document.getElementById('accountManageFooterLink');

            const contactOverlay = document.getElementById('contactPopupOverlay');
            const termsOverlay = document.getElementById('termsPopupOverlay');
            const privacyOverlay = document.getElementById('privacyPopupOverlay');
            const accountManageOverlay = document.getElementById('accountManagePopupOverlay');

            const contactCloseBtn = document.getElementById('contactPopupCloseBtn');
            const termsCloseBtn = document.getElementById('termsPopupCloseBtn');
            const privacyCloseBtn = document.getElementById('privacyPopupCloseBtn');
            const accountManageCloseBtn = document.getElementById('accountManagePopupCloseBtn');

            const form = document.getElementById('contactPopupForm');
            const msgBox = document.getElementById('contactPopupMsg');

            const freezeBtn = document.getElementById('freezeAccountBtn');
            const deleteBtn = document.getElementById('deleteAccountBtn');
            const accountManageMsg = document.getElementById('accountManageMsg');

            if (contactLink) {
                contactLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    openContactPopup();
                });
            }

            if (termsLink) {
                termsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    openTermsPopup();
                });
            }

            if (privacyLink) {
                privacyLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    openPrivacyPopup();
                });
            }

            if (accountManageLink) {
                accountManageLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    openAccountManagePopup();
                });
            }

            if (contactCloseBtn) {
                contactCloseBtn.addEventListener('click', closeContactPopup);
            }

            if (termsCloseBtn) {
                termsCloseBtn.addEventListener('click', closeTermsPopup);
            }

            if (privacyCloseBtn) {
                privacyCloseBtn.addEventListener('click', closePrivacyPopup);
            }

            if (accountManageCloseBtn) {
                accountManageCloseBtn.addEventListener('click', closeAccountManagePopup);
            }

            [contactOverlay, termsOverlay, privacyOverlay, accountManageOverlay].forEach(function(overlay) {
                if (!overlay) return;

                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeContactPopup();
                    closeTermsPopup();
                    closePrivacyPopup();
                    closeAccountManagePopup();
                }
            });

            if (form && msgBox) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    msgBox.textContent = '';
                    msgBox.className = 'footer-popup-msg';

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

                            setTimeout(function() {
                                closeContactPopup();
                                msgBox.textContent = '';
                                msgBox.className = 'footer-popup-msg';
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
            }

            if (freezeBtn) {
                freezeBtn.addEventListener('click', async function() {
                    if (!confirm('להקפיא את הכרטיס? ניתן יהיה לשחזר אותו בעתיד.')) {
                        return;
                    }

                    accountManageMsg.textContent = '';
                    accountManageMsg.className = 'footer-popup-msg';

                    try {
                        const data = await postJson('/freeze_account.php');

                        if (data.ok) {
                            window.location.href = data.redirect || '/';
                        } else {
                            accountManageMsg.textContent = data.error || 'שגיאה בהקפאת הכרטיס';
                            accountManageMsg.classList.add('error');
                        }
                    } catch (err) {
                        accountManageMsg.textContent = 'שגיאה בתקשורת עם השרת';
                        accountManageMsg.classList.add('error');
                    }
                });
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', async function() {
                    if (!confirm('האם למחוק את הפרופיל לצמיתות?')) {
                        return;
                    }

                    if (!confirm('אישור אחרון: כל הנתונים יימחקו לחלוטין ולא יהיה ניתן לשחזר.')) {
                        return;
                    }

                    accountManageMsg.textContent = '';
                    accountManageMsg.className = 'footer-popup-msg';

                    try {
                        const data = await postJson('/delete_account.php');

                        if (data.ok) {
                            window.location.href = data.redirect || '/';
                        } else {
                            accountManageMsg.textContent = data.error || 'שגיאה במחיקת הכרטיס';
                            accountManageMsg.classList.add('error');
                        }
                    } catch (err) {
                        accountManageMsg.textContent = 'שגיאה בתקשורת עם השרת';
                        accountManageMsg.classList.add('error');
                    }
                });
            }
        });
    })();
</script>