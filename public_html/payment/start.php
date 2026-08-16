<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/payment_functions.php';

/*
 * המשתמש חייב להיות מחובר
 */
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
//$_SESSION['payment_return_url'] = $_SERVER['HTTP_REFERER'] ?? '/';


if ($userId <= 0) {
    header('Location: /login.php');
    exit;
}

/*
 * מחיר מנוי חודשי
 */
$amount = 39.90;

try {

    /*
     * יצירת עסקה במצב pending
     */
    $paymentId = createPendingPayment(
        $pdo,
        $userId,
        $amount,
        'test'
    );

    /*
     * כרגע אין עדיין חברת סליקה אמיתית.
     * עוברים למסך בדיקה המדמה תשלום מוצלח.
     */
    header(
        'Location: /payment/test_success.php?payment_id='
        . urlencode((string)$paymentId)
    );
    exit;

} catch (Throwable $e) {

    error_log(
        'Payment start error: '
        . $e->getMessage()
    );

    header('Location: /payment/fail.php');
    exit;
}