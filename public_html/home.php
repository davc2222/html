<?php
// ===== FILE: home.php =====
?>

<style>
    .home-demo-wrap {
        padding: 0px 20px 50px;
        min-height: calc(100vh - 140px);
    }


    .home-page-shell {
        min-height: auto;
    }

    .home-demo-hero {
        max-width: 1150px;
        margin: 0 auto 10px;
        background: linear-gradient(135deg, #d91f4f, #b9153f);
        border-radius: 28px;
        padding: 0px 35px 10px;
        text-align: center;
        color: #fff;
        box-shadow: 0 10px 30px rgba(228, 9, 64, 0.14);
    }

    .home-demo-hero h1 {
        font-size: 52px;
        margin-bottom: 18px;
        color: #fff;
        font-weight: 800;
    }

    .home-demo-hero p {
        font-size: 21px;
        line-height: 1.9;
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.95);
    }

    .home-demo-actions {
        margin-top: 28px;
        display: flex;
        gap: 14px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .home-demo-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-height: 50px;

        padding: 0 22px;

        border-radius: 14px;

        font-weight: bold;
        font-size: 17px;

        text-decoration: none;
    }

    .home-demo-btn-primary {
        background: #fff;
        color: #d91f4f;
    }

    .home-demo-btn-primary:hover {
        background: #ffe2ea;
    }

    .home-demo-btn-secondary {
        background: transparent;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.8);
    }

    .home-demo-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    .home-demo-features {
        max-width: 1150px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .home-demo-card {
        background: #fff;
        border-radius: 24px;
        padding: 28px 24px;
        text-align: center;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
    }

    .home-demo-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, #ff4d6d, #ff7590);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        font-weight: bold;
    }

    .home-demo-card h3 {
        color: #d61f4d;
        font-size: 28px;
        margin-bottom: 12px;
    }

    .home-demo-card p {
        color: #555;
        font-size: 18px;
        line-height: 1.8;
        margin: 0;
    }

    .home-demo-hero {
        background: linear-gradient(135deg, #d91f4f, #b9153f) !important;
        color: #fff !important;
    }

    .home-demo-hero h1,
    .home-demo-hero p,
    .home-demo-hero .home-logged-in {
        color: #fff !important;
    }

    .home-demo-btn-primary {
        background: #fff !important;
        color: #d91f4f !important;
    }

    /* =========================
   MOBILE
   ========================= */
@media (max-width: 640px) {

    .home-page-shell {
        width: 100%;
        min-width: 0;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .home-demo-wrap {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 10px 30px;
        min-height: 0;
    }

    .home-demo-hero {
        width: 100%;
        max-width: none;
        box-sizing: border-box;
        margin: 0 0 12px;
        padding: 20px 14px 18px;
        border-radius: 18px;
    }

    .home-demo-hero h1 {
        font-size: 27px;
        line-height: 1.3;
        margin: 0 0 14px;
        overflow-wrap: anywhere;
    }

    .home-demo-hero p {
        font-size: 15px;
        line-height: 1.6;
        margin: 0 0 8px;
    }

    .hero-mini-logo {
        display: block;
        width: 80px;
        max-width: 80px;
        height: auto;
        margin: 12px auto 0;
    }

    .home-demo-actions {
        width: 100%;
        margin-top: 18px;
        gap: 10px;
        flex-direction: column;
        align-items: stretch;
    }

    .home-demo-btn {
        width: 100%;
        box-sizing: border-box;
        min-height: 46px;
        padding: 0 14px;
        font-size: 15px;
    }

    .quick-register,
    .home-logged-in {
        width: 100%;
        box-sizing: border-box;
    }

    .home-logged-in h2 {
        font-size: 20px;
        margin: 8px 0 0;
    }

    .home-demo-features {
        width: 100%;
        max-width: none;
        box-sizing: border-box;
        grid-template-columns: 1fr;
        gap: 12px;
        margin: 0;
    }

    .home-demo-card {
        width: 100%;
        box-sizing: border-box;
        padding: 20px 16px;
        border-radius: 18px;
    }

    .home-demo-icon {
        width: 50px;
        height: 50px;
        margin-bottom: 10px;
        font-size: 24px;
    }

    .home-demo-card h3 {
        font-size: 21px;
        margin: 0 0 8px;
    }

    .home-demo-card p {
        font-size: 15px;
        line-height: 1.6;
    }
}
</style>

<main class="home-page-shell">
    <div class="home-demo-wrap">
        <section class="home-demo-hero">
            <h1>LoveMatch – אתר הכרויות למציאת זוגיות אמיתית <img src="/images/logonew.jpeg" class="hero-mini-logo" alt="LoveMatch"></h1>
            <p>מצא את ההתאמה המושלמת עבורך במהירות ובקלות.</p>
            <p>קהילה איכותית, התאמות חכמות וחוויית שימוש נעימה ופשוטה.</p>

            <div class="home-demo-actions">
                <a href="?page=search" class="home-demo-btn home-demo-btn-primary">התחל לחפש</a>

                <?php if (empty($_SESSION['user_id'])): ?>
                    <div class="quick-register">
                        <!-- כל הטופס שלך -->
                    </div>
                <?php else: ?>
                    <div class="home-logged-in">
                        <h2>ברוך הבא 👋</h2>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="home-demo-features">
            <div class="home-demo-card">
                <div class="home-demo-icon">❤</div>
                <h3>קהילה רחבה</h3>
                <p>אנשים מכל רחבי הארץ שמחפשים קשר אמיתי ומשמעותי.</p>
            </div>

            <div class="home-demo-card">
                <div class="home-demo-icon">★</div>
                <h3>אתר הכרויות בישראל</h3>
                <p>
                    LoveMatch הוא אתר הכרויות מוביל בישראל למציאת זוגיות אמיתית, דייטים וקשרים רציניים.
                    חפשו התאמות לפי אזור, גיל והעדפות אישיות, שלחו הודעות והכירו אנשים מכל הארץ בצורה נוחה ובטוחה.
                </p>
            </div>

            <div class="home-demo-card">
                <div class="home-demo-icon">✓</div>
                <h3>פרטיות ובטיחות</h3>
                <p>המידע שלך נשמר בצורה מאובטחת עם חוויית שימוש בטוחה ונוחה.</p>
            </div>
        </section>
    </div>
</main>