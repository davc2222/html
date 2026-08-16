<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/payment_functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$paymentId = isset($_GET['payment_id'])
    ? (int) $_GET['payment_id']
    : 0;

if ($paymentId <= 0) {
    header('Location: /payment/fail.php');
    exit;
}

/*
 * חשוב:
 * בודקים שהעסקה באמת שייכת למשתמש המחובר.
 */
$stmt = $pdo->prepare("
    SELECT id, user_id, status
    FROM payments
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$paymentId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment || (int)$payment['user_id'] !== $userId) {
    header('Location: /payment/fail.php');
    exit;
}

/*
 * מזהה עסקה מדומה לצורך הבדיקות.
 * בחברת סליקה אמיתית נקבל את המספר מחברת הסליקה.
 */
$transactionId = 'TEST-' . date('YmdHis') . '-' . $paymentId;

$success = activateSubscriptionFromPayment(
    $pdo,
    $paymentId,
    $transactionId
);

if (!$success) {
    header('Location: /payment/fail.php');
    exit;
}

header(
    'Location: /payment/success.php?payment_id='
    . urlencode((string)$paymentId)
);
exit;