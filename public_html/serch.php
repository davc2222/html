<?php
require_once __DIR__ . '/config/config.php';

$stmt = $pdo->query("
    SELECT Id, Name, Age, Zone_Str, Place_Str, Who_Am_I
    FROM users_profile
    WHERE email_verified = 1
    ORDER BY Id DESC
    LIMIT 20
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="page-shell">
    <section class="search-results-page">
        <h1 class="results-title">תוצאות חיפוש</h1>

        <div class="results-list">
            <?php foreach ($users as $u): ?>
                <article class="search-result-card">

                    <div class="search-result-image">
                        <img src="/images/no_photo.jpg" alt="<?= htmlspecialchars($u['Name']) ?>">
                    </div>

                    <div class="search-result-content">
                        <h2><?= htmlspecialchars($u['Name'] ?? '') ?></h2>

                        <div class="search-result-meta">
                            <span>גיל: <?= htmlspecialchars($u['Age'] ?? '-') ?></span>
                            <span>אזור: <?= htmlspecialchars($u['Zone_Str'] ?? '-') ?></span>
                            <span>מקום: <?= htmlspecialchars($u['Place_Str'] ?? '-') ?></span>
                        </div>

                        <p class="search-result-text">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($u['Who_Am_I'] ?? '', 0, 160, '...'))) ?>
                        </p>

                        <div class="search-result-actions">
                            <button type="button" class="result-action-btn">שלח הודעה</button>
                            <button type="button" class="result-action-btn secondary">שמור</button>
                        </div>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>