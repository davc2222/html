<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.amount,
        p.currency,
        p.transaction_id,
        p.paid_at,
        s.expires_at
    FROM payments p
    LEFT JOIN subscriptions s
        ON s.id = p.subscription_id
    WHERE p.id = ?
      AND p.user_id = ?
      AND p.status = 'paid'
    LIMIT 1
");

$stmt->execute([$paymentId, $userId]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: /payment/fail.php');
    exit;
}

$returnUrl = $_SESSION['payment_return_url'] ?? '/';
unset($_SESSION['payment_return_url']);


?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>התשלום הושלם - LoveMatch</title>

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

        .success-icon {
            font-size: 54px;
            color: #19a15f;
            margin-bottom: 15px;
        }

        h1 {
            margin-top: 0;
        }

        .details {
            margin: 25px 0;
            line-height: 2;
        }

        .btn {
            display: inline-block;
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

    <div class="success-icon">✓</div>

    <h1>התשלום בוצע בהצלחה</h1>

    <p>
        המנוי שלך ב־LoveMatch הופעל.
    </p>

    <div class="details">

        <div>
            סכום:
            <strong>
                <?= htmlspecialchars(number_format((float)$payment['amount'], 2)) ?>
                ₪
            </strong>
        </div>

        <?php if (!empty($payment['expires_at'])): ?>
            <div>
                המנוי בתוקף עד:
                <strong>
                    <?= htmlspecialchars(
                        date('d/m/Y', strtotime($payment['expires_at']))
                    ) ?>
                </strong>
            </div>
        <?php endif; ?>

    </div>

    <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn">
    חזרה
</a>

</div>

</body>
</html>
