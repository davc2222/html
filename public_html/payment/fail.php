<?php
declare(strict_types=1);

session_start();
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>התשלום נכשל - LoveMatch</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            color: #222;
        }

        .payment-box {
            max-width: 520px;
            margin: 80px auto;
            background: #fff;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,.08);
            text-align: center;
        }

        .icon {
            font-size: 54px;
            color: #d93025;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            background: #0875e1;
            color: #fff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="payment-box">

    <div class="icon">✕</div>

    <h1>התשלום לא הושלם</h1>

    <p>
        לא בוצע חיוב ולא הופעל מנוי.
    </p>

    <a href="/subscription.php" class="btn">
        חזרה לעמוד המנוי
    </a>

</div>

</body>
</html>