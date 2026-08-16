<?php

function getUserActiveSubscription(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM subscriptions
        WHERE user_id = ?
          AND status = 'active'
          AND expires_at >= NOW()
        ORDER BY expires_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);

    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    return $subscription ?: null;
}

function createPendingPayment(
    PDO $pdo,
    int $userId,
    float $amount,
    string $provider = 'test'
): int {
    $stmt = $pdo->prepare("
        INSERT INTO payments
        (
            user_id,
            amount,
            currency,
            provider,
            status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            'ILS',
            ?,
            'pending',
            NOW()
        )
    ");

    $stmt->execute([
        $userId,
        $amount,
        $provider
    ]);

    return (int)$pdo->lastInsertId();
}

function activateSubscriptionFromPayment(
    PDO $pdo,
    int $paymentId,
    string $transactionId
): bool {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT *
            FROM payments
            WHERE id = ?
            FOR UPDATE
        ");
        $stmt->execute([$paymentId]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if ($payment['status'] === 'paid') {
            $pdo->commit();
            return true;
        }

        $userId = (int)$payment['user_id'];

        $stmt = $pdo->prepare("
            SELECT *
            FROM subscriptions
            WHERE user_id = ?
              AND status = 'active'
              AND expires_at >= NOW()
            ORDER BY expires_at DESC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$userId]);

        $currentSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentSubscription) {
            $subscriptionId = (int)$currentSubscription['id'];

            $stmt = $pdo->prepare("
                UPDATE subscriptions
                SET
                    expires_at = DATE_ADD(expires_at, INTERVAL 1 MONTH),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$subscriptionId]);

        } else {
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions
                (
                    user_id,
                    plan,
                    status,
                    paid_at,
                    expires_at,
                    created_at,
                    updated_at
                )
                VALUES
                (
                    ?,
                    'premium',
                    'active',
                    NOW(),
                    DATE_ADD(NOW(), INTERVAL 1 MONTH),
                    NOW(),
                    NOW()
                )
            ");
            $stmt->execute([$userId]);

            $subscriptionId = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("
            UPDATE payments
            SET
                subscription_id = ?,
                transaction_id = ?,
                status = 'paid',
                paid_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $subscriptionId,
            $transactionId,
            $paymentId
        ]);

        $pdo->commit();

        return true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Payment activation error: ' . $e->getMessage());

        return false;
    }
}