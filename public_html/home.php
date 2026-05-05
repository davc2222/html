<?php
// ===== FILE: home.php =====
?>

<style>
    .home-demo-wrap {
        padding: 35px 20px 50px;
        background: #efefef;
    }

    .home-page-shell {
        min-height: calc(100vh - 190px);
    }

    .home-demo-hero {
        max-width: 1150px;
        margin: 0 auto 35px;
        background: linear-gradient(135deg, #d91f4f, #b9153f);
        border-radius: 28px;
        padding: 70px 35px 60px;
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
        display: inline-block;
        text-decoration: none;
        padding: 14px 22px;
        border-radius: 14px;
        font-weight: bold;
        font-size: 17px;
        transition: 0.25s ease;
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
</style>

<main class="home-page-shell">
    <div class="home-demo-wrap">
        <section class="home-demo-hero">
            <h1>LoveMatch – אתר הכרויות למציאת זוגיות אמיתית ❤️</h1>
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