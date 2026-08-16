<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$returnUrl = trim((string)($_GET['return'] ?? ''));

if (
    $returnUrl !== '' &&
    str_starts_with($returnUrl, '/') &&
    !str_starts_with($returnUrl, '//')
) {
    $_SESSION['payment_return_url'] = $returnUrl;
}
$userId = (int)$_SESSION['user_id'];



?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>מנוי Premium - LoveMatch</title>

    <style>
        body {
            margin: 0;
            background: #f4f7fb;
            font-family: Arial, sans-serif;
            color: #263238;
        }

        .subscription-page {
            max-width: 720px;
            margin: 50px auto;
            padding: 20px;
        }

        .subscription-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,.08);
            padding: 38px;
            text-align: center;
        }

        .subscription-card h1 {
            margin: 0 0 10px;
            font-size: 32px;
            color: #1769aa;
        }

        .subtitle {
            font-size: 18px;
            color: #607d8b;
            margin-bottom: 30px;
        }

        .price {
            font-size: 46px;
            font-weight: 700;
            color: #222;
            margin-top: 20px;
        }

        .period {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .one-month {
            font-size: 17px;
            color: #d35400;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .features {
            margin: 28px auto;
            max-width: 430px;
            text-align: right;
            line-height: 2;
            font-size: 17px;
        }

        .buy-button {
            display: inline-block;
            border: 0;
            border-radius: 10px;
            background: #1769aa;
            color: #fff;
            padding: 14px 40px;
            font-size: 19px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin: 15px 0;
        }

        .buy-button:hover {
            background: #12558a;
        }

        .security-box {
            background: #f6f9fc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 28px;
            line-height: 1.8;
        }

        .security-box strong {
            color: #263238;
        }

        .notice {
            margin-top: 22px;
            font-size: 15px;
            color: #546e7a;
        }

        .free-info {
            border-top: 1px solid #e5e9ed;
            margin-top: 30px;
            padding-top: 25px;
        }

        @media (max-width: 600px) {
            .subscription-page {
                margin: 20px auto;
                padding: 12px;
            }

            .subscription-card {
                padding: 25px 18px;
            }

            .price {
                font-size: 38px;
            }
        }
    </style>
</head>

<body>

<div class="subscription-page">

    <div class="subscription-card">

        <h1>💎 מנוי Premium</h1>

        <div class="subtitle">
            התחילו להכיר ללא מגבלות
        </div>

        <div class="price">
            ₪39.90
        </div>

        <div class="period">
            לחודש אחד
        </div>

        <div class="one-month">
            ללא התחייבות וללא חידוש אוטומטי
        </div>

        <div class="features">
            ✓ קריאת הודעות<br>
            ✓ שליחת הודעות ללא הגבלה<br>
            ✓ גישה מלאה ל-Inbox<br>
            ✓ שימוש מלא בצ'אט<br>
            ✓ הפעלה מיידית לאחר התשלום
        </div>

        <a href="/payment/start.php" class="buy-button">
    רכישת מנוי Premium
</a>

        <div class="security-box">
            🔒 <strong>תשלום מאובטח</strong><br>
            התשלום יתבצע באמצעות מערכת סליקה מאובטחת.<br><br>

            🚫 <strong>אין חידוש אוטומטי</strong><br>
            המנוי תקף לחודש אחד בלבד ומסתיים באופן אוטומטי בתום התקופה.<br><br>

            💳 <strong>חיוב חד-פעמי בלבד</strong><br>
            אם תרצו להמשיך לאחר סיום המנוי, תוכלו לרכוש חודש נוסף ביוזמתכם.
        </div>

        <div class="free-info">
            <strong>אפשר להמשיך להשתמש באתר גם ללא מנוי.</strong>

            <p>
                הרשמה, יצירת פרופיל, העלאת תמונות,
                חיפוש וצפייה בפרופילים נשארים פתוחים.
            </p>

            <p>
                מנוי Premium נדרש רק עבור קריאה ושליחה של הודעות.
            </p>
        </div>

        <div class="notice">
            אין התחייבות • אין חיובים חוזרים • אתם מחליטים אם ומתי לחדש
        </div>

    </div>

</div>

</body>
</html>