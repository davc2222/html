<?php
// ===== FILE: mobile/home.php =====
?>

<style>
    .home-wrap {
        padding: 20px 14px 30px;
        background: #f4f4f4;
    }

    /* HERO */
    .home-hero {
        background: linear-gradient(135deg, #d91f4f, #b9153f);
        border-radius: 20px;
        padding: 26px 16px 22px;
        text-align: center;
        color: #fff;
        margin-bottom: 18px;
    }

    .home-hero h1 {
        font-size: 22px;
        margin-bottom: 10px;
    }

    .home-hero p {
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 8px;
    }

    /* BUTTON */
    .home-btn {
        display: block;
        width: 100%;
        margin-top: 14px;
        padding: 12px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
    }

    .home-btn-primary {
        background: #fff;
        color: #d91f4f;
    }

    .home-btn-primary:active {
        background: #ffe2ea;
    }

    /* FEATURES */
    .home-features {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .home-card {
        background: #fff;
        border-radius: 16px;
        padding: 14px;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .home-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff4d6d, #ff7590);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .home-text h3 {
        font-size: 15px;
        margin: 0 0 3px;
        color: #d61f4d;
    }

    .home-text p {
        font-size: 13px;
        margin: 0;
        color: #555;
    }
</style>

<main class="home-wrap">

    <div class="home-hero">
        <h1>אתר הכרויות LoveMatch ❤️</h1>
        <p>אתר הכרויות למציאת זוגיות, דייטים וקשרים אמיתיים בישראל</p>

        <a href="?page=search" class="home-btn home-btn-primary">
            התחל לחפש
        </a>

        <?php if (empty($_SESSION['user_id'])): ?>
            <!-- כאן אפשר לשים כפתור הרשמה אם תרצה -->
        <?php endif; ?>
    </div>

    <div class="home-features">

        <div class="home-card">
            <div class="home-icon">❤</div>
            <div class="home-text">
                <h3>קהילה רחבה</h3>
                <p>אלפי משתמשים מכל הארץ</p>
            </div>
        </div>

        <div class="home-card">
            <div class="home-icon">★</div>
            <div class="home-text">
                <h3>אתר הכרויות בישראל</h3>
                <p> LoveMatch הוא אתר הכרויות מוביל בישראל למציאת זוגיות אמיתית, דייטים וקשרים רציניים.
                    חפשו התאמות לפי אזור, גיל והעדפות אישיות, שלחו הודעות והכירו אנשים מכל הארץ בצורה נוחה ובטוחה.</p>
            </div>
        </div>

        <div class="home-card">
            <div class="home-icon">✓</div>
            <div class="home-text">
                <h3>בטיחות</h3>
                <p>שימוש מאובטח ונוח</p>
            </div>
        </div>

    </div>

</main>