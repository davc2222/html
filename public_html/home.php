<!-- home.php-->
<style>
.home-demo-wrap {
    padding: 35px 20px 50px;
    background: #efefef;
}

.home-demo-hero {
    max-width: 1150px;
    margin: 0 auto 35px;
    background: linear-gradient(135deg, #d91f4f, #b9153f);
    border-radius: 28px;
    padding: 70px 35px 60px;
    text-align: center;
    color: #fff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.14);
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
    color: rgba(255,255,255,0.95);
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
    border: 2px solid rgba(255,255,255,0.8);
}

.home-demo-btn-secondary:hover {
    background: rgba(255,255,255,0.14);
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
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
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

@media (max-width: 900px) {
    .home-demo-hero h1 {
        font-size: 38px;
    }

    .home-demo-hero p {
        font-size: 18px;
    }

    .home-demo-features {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .home-demo-hero {
        padding: 50px 20px 42px;
    }

    .home-demo-hero h1 {
        font-size: 30px;
    }

    .home-demo-actions {
        flex-direction: column;
        align-items: center;
    }

    .home-demo-btn {
        width: 100%;
        max-width: 260px;
        text-align: center;
    }
}
</style>

<div class="home-demo-wrap">
    <section class="home-demo-hero">
        <h1>ברוכים הבאים ל־LoveMatch ❤️</h1>
        <p>מצא את ההתאמה המושלמת עבורך במהירות ובקלות.</p>
        <p>קהילה איכותית, התאמות חכמות וחוויית שימוש נעימה ופשוטה.</p>

        <div class="home-demo-actions">
            <a href="?page=search" class="home-demo-btn home-demo-btn-primary">התחל לחפש</a>
            <a href="?page=register" class="home-demo-btn home-demo-btn-secondary">הרשמה מהירה</a>
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
            <h3>התאמות אישיות</h3>
            <p>מערכת חכמה שמסייעת למצוא התאמות מדויקות וטובות יותר.</p>
        </div>

        <div class="home-demo-card">
            <div class="home-demo-icon">✓</div>
            <h3>פרטיות ובטיחות</h3>
            <p>המידע שלך נשמר בצורה מאובטחת עם חוויית שימוש בטוחה ונוחה.</p>
        </div>
    </section>
</div>