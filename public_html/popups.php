<?php
// ================================
// POPUPS DESKTOP (רגיל)
// פופאפים מחוץ ל-footer כדי למנוע סקרול לרוחב
// ================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);
?>

<!-- =======================
     CONTACT POPUP
     Loads contact.php only when the popup is opened
======================= -->
<div id="contactPopupOverlay" class="footer-popup-overlay" style="display:none;">
    <div class="footer-popup-box">
        <button type="button" class="footer-popup-close" id="contactPopupCloseBtn">×</button>

        <div id="contactPopupContent">
            <div class="footer-popup-msg">טוען...</div>
        </div>
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
                בעת הרשמה לאתר נאסף מידע כגון שם משתמש, כתובת אימייל, תאריך לידה ופרטים נוספים שהמשתמש בוחר להזין
                בפרופיל.
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
            <p style="text-align:center;">
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
        <div class="footer-popup-box account-manage-box">
            <button type="button" class="footer-popup-close" id="accountManagePopupCloseBtn">×</button>

            <h2 class="footer-popup-title account-manage-title">
                <span class="account-title-text">ניהול כרטיס</span>
                <span class="account-title-icon">▣</span>
            </h2>

            <div class="account-manage-content">
                <p>
                    מאפשר לך לשלוט בסטטוס הנראות והפעילות של הפרופיל שלך.
                </p>
                <p>
                    ניתן להקפיא את הכרטיס ולחזור בעתיד. או למחוק את הפרופיל לצמיתות.
                </p>

                <div class="account-manage-actions">

                    <button type="button" id="freezeAccountBtn" class="footer-popup-submit account-freeze-btn">
                        <div class="account-btn-row">
                            <span class="account-btn-title">הקפאת כרטיס</span>
                            <span class="account-btn-icon">❄</span>
                        </div>
                        <span class="account-btn-subtitle">הסתרה זמנית</span>
                    </button>

                    <button type="button" id="deleteAccountBtn" class="footer-popup-submit account-delete-btn">
                        <div class="account-btn-row">
                            <span class="account-btn-title">מחיקת פרופיל</span>
                            <span class="account-btn-icon">🗑</span>
                        </div>
                        <span class="account-btn-subtitle">מחיקה לצמיתות</span>
                    </button>
                </div>

                <div class="account-help-row">
                    <span>זקוק לעזרה?</span>
                    <button type="button" class="account-help-link"
                        onclick="closeAccountManagePopup(); openContactPopup();">
                        פנה אלינו
                    </button>
                </div>

                <div id="accountManageMsg" class="footer-popup-msg"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .footer-popup-overlay {
        position: fixed !important;
        inset: 0 !important;
        display: none;
        align-items: center !important;
        justify-content: center !important;
        padding: 16px !important;
        background: rgba(0, 0, 0, .55) !important;
        z-index: 1000000 !important;
        box-sizing: border-box !important;
        overflow: auto !important;
        overflow-x: hidden !important;
    }

    .footer-popup-box,
    .footer-popup-box-terms {
        position: relative !important;
        width: min(560px, calc(100vw - 32px)) !important;
        max-height: calc(100vh - 32px) !important;
        overflow: auto !important;
        background: #fff !important;
        border-radius: 18px !important;
        padding: 24px 22px !important;
        direction: rtl !important;
        text-align: right !important;
        box-shadow: 0 18px 50px rgba(0, 0, 0, .25) !important;
        box-sizing: border-box !important;
    }

    .footer-popup-box-terms {
        width: min(760px, calc(100vw - 32px)) !important;
    }

    .footer-popup-close {
        position: absolute !important;
        top: 16px !important;
        left: 18px !important;
        width: 40px !important;
        height: 40px !important;
        border: 0 !important;
        border-radius: 50% !important;
        background: #f1f1f1 !important;
        color: #333 !important;
        font-size: 28px !important;
        line-height: 1 !important;
        cursor: pointer !important;
    }

    .footer-popup-title {
        margin: 0 0 18px !important;
        text-align: center !important;
        color: #d91f4f !important;
    }

    .terms-content {
        line-height: 1.8 !important;
        color: #333 !important;
    }

    .footer-popup-msg {
        min-height: 22px;
        margin-top: 10px;
        font-size: 14px;
    }

    .footer-popup-msg.success {
        color: #138a36;
    }

    .footer-popup-msg.error {
        color: #c62828;
    }

    .footer-popup-submit {
        border: 0;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: bold;
        cursor: pointer;
        background: #ff4d6d;
        color: #fff;
    }

    /* =======================
       ACCOUNT MANAGE DESIGN ONLY
    ======================= */
    #accountManagePopupOverlay {
        background: rgba(0, 0, 0, .58) !important;
        backdrop-filter: blur(3px);
    }

    #accountManagePopupOverlay .account-manage-box {
        width: min(1080px, calc(100vw - 90px)) !important;
        max-height: calc(100vh - 48px) !important;
        overflow-x: hidden !important;
        background: #ffffff !important;
        border-radius: 26px !important;
        padding: 74px 72px 52px !important;
        border: 0 !important;
        box-shadow: 0 28px 90px rgba(0, 0, 0, .28) !important;
    }

    #accountManagePopupOverlay .footer-popup-close {
        top: 28px !important;
        left: 30px !important;
        background: transparent !important;
        color: #e11d48 !important;
        font-size: 48px !important;
        width: 46px !important;
        height: 46px !important;
        font-weight: 300 !important;
    }

    #accountManagePopupOverlay .account-manage-title {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 14px !important;
        margin: 0 0 26px !important;
        color: #e11d48 !important;
        font-size: 44px !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
    }

    #accountManagePopupOverlay .account-title-icon {
        font-size: 36px !important;
        line-height: 1 !important;
    }

    #accountManagePopupOverlay .account-manage-content {
        text-align: center !important;
        color: #333 !important;
        font-size: 25px !important;
        line-height: 1.75 !important;
    }

    #accountManagePopupOverlay .account-manage-content p {
        margin: 0 0 22px !important;
    }

    #accountManagePopupOverlay .account-manage-actions {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 20px !important;
        margin: 48px auto 28px !important;
        max-width: 880px !important;
    }

    #accountManagePopupOverlay .footer-popup-submit {
        min-height: 140px !important;
        border-radius: 14px !important;
        padding: 22px 34px !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        cursor: pointer !important;
        transition: .2s !important;
        box-sizing: border-box !important;
    }

    #accountManagePopupOverlay .footer-popup-submit:hover {
        transform: translateY(-2px);
    }

    #accountManagePopupOverlay .account-btn-row {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 22px !important;
        direction: rtl !important;
        width: 100% !important;
    }

    #accountManagePopupOverlay .account-btn-title {
        font-size: 30px !important;
        font-weight: 900 !important;
        line-height: 1.15 !important;
        white-space: nowrap !important;
    }

    #accountManagePopupOverlay .account-btn-icon {
        font-size: 42px !important;
        line-height: 1 !important;
    }

    #accountManagePopupOverlay .account-btn-subtitle {
        font-size: 21px !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
    }

    #accountManagePopupOverlay .account-freeze-btn {
        background: linear-gradient(180deg, #ffa200 0%, #ff9800 100%) !important;
        color: #fff !important;
        border: 2px solid #c97900 !important;
        box-shadow: inset 0 2px 0 rgba(255, 255, 255, .22), 0 10px 20px rgba(255, 152, 0, .22) !important;
    }

    #accountManagePopupOverlay .account-delete-btn {
        background: #fff !important;
        color: #e11d48 !important;
        border: 2px solid #e11d48 !important;
        box-shadow: 0 10px 22px rgba(225, 29, 72, .12) !important;
    }

    #accountManagePopupOverlay .account-delete-btn .account-btn-title,
    #accountManagePopupOverlay .account-delete-btn .account-btn-subtitle,
    #accountManagePopupOverlay .account-delete-btn .account-btn-icon {
        color: #e11d48 !important;
    }

    #accountManagePopupOverlay .account-help-row {
        border-top: 1px solid #ddd !important;
        margin-top: 30px !important;
        padding-top: 28px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        color: #333 !important;
        font-size: 21px !important;
    }

    #accountManagePopupOverlay .account-help-link {
        border: 0 !important;
        background: transparent !important;
        color: #e11d48 !important;
        font-size: 21px !important;
        font-weight: 700 !important;
        text-decoration: underline !important;
        cursor: pointer !important;
        padding: 0 !important;
    }

    @media (max-width: 700px) {
        #accountManagePopupOverlay .account-manage-box {
            width: min(460px, calc(100vw - 24px)) !important;
            padding: 48px 22px 28px !important;
        }

        #accountManagePopupOverlay .account-manage-title {
            font-size: 24px !important;
        }

        #accountManagePopupOverlay .account-manage-content {
            font-size: 16px !important;
        }

        #accountManagePopupOverlay .account-manage-actions {
            gap: 16px !important;
            margin-top: 28px !important;
        }

        #accountManagePopupOverlay .footer-popup-submit {
            min-height: 136px !important;
            padding: 18px 12px !important;
        }

        #accountManagePopupOverlay .account-btn-row {
            flex-direction: column-reverse !important;
            gap: 8px !important;
        }

        #accountManagePopupOverlay .account-btn-title {
            font-size: 23px !important;
            white-space: normal !important;
        }

        #accountManagePopupOverlay .account-btn-icon {
            font-size: 28px !important;
        }

        #accountManagePopupOverlay .account-btn-subtitle {
            font-size: 14px !important;
        }

        #accountManagePopupOverlay .account-help-row,
        #accountManagePopupOverlay .account-help-link {
            font-size: 16px !important;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        function byId(id) {
            return document.getElementById(id);
        }

        function openPopup(id) {
            var el = byId(id);
            if (!el) {
                console.warn('Popup not found:', id);
                return false;
            }
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            return false;
        }

        function closePopup(id) {
            var el = byId(id);
            if (!el) return false;
            el.style.display = 'none';
            document.body.style.overflow = '';
            return false;
        }

        function loadContactIfNeeded() {
            var box = byId('contactPopupContent');
            if (!box || box.getAttribute('data-loaded') === '1') return;
            fetch('/contact.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (r) {
                return r.text();
            }).then(function (html) {
                box.innerHTML = html;
                box.setAttribute('data-loaded', '1');
            }).catch(function () {
                box.innerHTML = '<div class="footer-popup-msg error">שגיאה בטעינת צור קשר</div>';
            });
        }
        window.openContactPopup = function () {
            loadContactIfNeeded();
            return openPopup('contactPopupOverlay');
        };
        window.closeContactPopup = function () {
            return closePopup('contactPopupOverlay');
        };
        window.openTermsPopup = function () {
            return openPopup('termsPopupOverlay');
        };
        window.closeTermsPopup = function () {
            return closePopup('termsPopupOverlay');
        };
        window.openPrivacyPopup = function () {
            return openPopup('privacyPopupOverlay');
        };
        window.closePrivacyPopup = function () {
            return closePopup('privacyPopupOverlay');
        };
        window.openAccountManagePopup = function () {
            return openPopup('accountManagePopupOverlay');
        };
        window.closeAccountManagePopup = function () {
            return closePopup('accountManagePopupOverlay');
        };
        document.addEventListener('click', function (e) {
            var t = e.target;
            if (t.closest('#termsFooterLink')) {
                e.preventDefault();
                openTermsPopup();
                return;
            }
            if (t.closest('#privacyFooterLink')) {
                e.preventDefault();
                openPrivacyPopup();
                return;
            }
            if (t.closest('#contactFooterLink')) {
                e.preventDefault();
                openContactPopup();
                return;
            }
            if (t.closest('#accountManageFooterLink')) {
                e.preventDefault();
                openAccountManagePopup();
                return;
            }
            if (t.closest('#termsPopupCloseBtn')) {
                closeTermsPopup();
                return;
            }
            if (t.closest('#privacyPopupCloseBtn')) {
                closePrivacyPopup();
                return;
            }
            if (t.closest('#contactPopupCloseBtn')) {
                closeContactPopup();
                return;
            }
            if (t.closest('#accountManagePopupCloseBtn')) {
                closeAccountManagePopup();
                return;
            } ['termsPopupOverlay', 'privacyPopupOverlay', 'contactPopupOverlay', 'accountManagePopupOverlay'].forEach(function (id) {
                var o = byId(id);
                if (o && t === o) closePopup(id);
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeContactPopup();
                closeTermsPopup();
                closePrivacyPopup();
                closeAccountManagePopup();
            }
        });
        document.addEventListener('submit', function (e) {
            if (!e.target || e.target.id !== 'contactPopupForm') return;
            e.preventDefault();
            var f = e.target,
                m = byId('contactPopupMsg');
            if (!m) return;
            m.textContent = '';
            m.className = 'footer-popup-msg';
            fetch('/contact_send.php', {
                method: 'POST',
                body: new FormData(f)
            }).then(function (r) {
                return r.json();
            }).then(function (d) {
                if (d.ok) {
                    m.textContent = 'ההודעה נשלחה בהצלחה';
                    m.className = 'footer-popup-msg success';
                    f.reset();
                    setTimeout(function () {
                        closeContactPopup();
                        m.textContent = '';
                        m.className = 'footer-popup-msg';
                    }, 2500);
                } else {
                    m.textContent = d.error || 'שגיאה בשליחה';
                    m.classList.add('error');
                }
            }).catch(function () {
                m.textContent = 'שגיאה בתקשורת עם השרת';
                m.classList.add('error');
            });
        });

        function postJson(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (r) {
                return r.json();
            });
        }
        document.addEventListener('click', function (e) {
            var m = byId('accountManageMsg');
            if (e.target.closest('#freezeAccountBtn')) {
                e.preventDefault();
                if (!confirm('להקפיא את הכרטיס? ניתן יהיה לשחזר אותו בעתיד.')) return;
                if (m) {
                    m.textContent = '';
                    m.className = 'footer-popup-msg';
                }
                postJson('/freeze_account.php').then(function (d) {
                    if (d.ok) window.location.href = d.redirect || '/';
                    else if (m) {
                        m.textContent = d.error || 'שגיאה בהקפאת הכרטיס';
                        m.classList.add('error');
                    }
                }).catch(function () {
                    if (m) {
                        m.textContent = 'שגיאה בתקשורת עם השרת';
                        m.classList.add('error');
                    }
                });
            }
            if (e.target.closest('#deleteAccountBtn')) {
                e.preventDefault();
                if (!confirm('האם למחוק את הפרופיל לצמיתות?')) return;
                if (!confirm('אישור אחרון: כל הנתונים יימחקו לחלוטין ולא יהיה ניתן לשחזר.')) return;
                if (m) {
                    m.textContent = '';
                    m.className = 'footer-popup-msg';
                }
                postJson('/delete_account.php').then(function (d) {
                    if (d.ok) window.location.href = d.redirect || '/';
                    else if (m) {
                        m.textContent = d.error || 'שגיאה במחיקת הכרטיס';
                        m.classList.add('error');
                    }
                }).catch(function () {
                    if (m) {
                        m.textContent = 'שגיאה בתקשורת עם השרת';
                        m.classList.add('error');
                    }
                });
            }
        });
    })();
</script>