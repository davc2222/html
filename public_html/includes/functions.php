<?php

function hasActiveSubscription(PDO $pdo, int $userId): bool
{
    // כאשר האתר פתוח לכולם אין צורך במנוי
    if (siteIsOpen($pdo)) {
        return true;
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM subscriptions
        WHERE user_id = ?
          AND status = 'active'
          AND expires_at IS NOT NULL
          AND expires_at >= NOW()
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    return (bool)$stmt->fetchColumn();
}



function siteIsOpen(PDO $pdo): bool
{
    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM site_settings
        WHERE setting_key IN ('site_open_from', 'site_open_until')
    ");

    $settings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $from = strtotime($settings['site_open_from'] ?? '');
    $until = strtotime($settings['site_open_until'] ?? '');

    if ($from === false || $until === false) {
        return false;
    }

    $now = time();

    return $now >= $from && $now <= $until;
}

function hasMessagingAccess(PDO $pdo, int $userId): bool
{
    return siteIsOpen($pdo) || hasActiveSubscription($pdo, $userId);
}