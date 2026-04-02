<?php
/* =========================
   views.php
   ========================= */

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo "<div class='page-shell'>יש להתחבר</div>";
    exit;
}

/* =========================
   שליפת צפיות
   ========================= */
$stmt = $pdo->prepare("
    SELECT DISTINCT u.Id, u.Name, u.Age, u.Place_Str, u.Zone_Str, v.Date
    FROM views v
    JOIN users_profile u ON u.Id = v.ById
    WHERE v.Id = :id 
      AND (v.Deleted_By_Id IS NULL OR v.Deleted_By_Id = 0)
    ORDER BY v.Date DESC
");

$stmt->execute([':id'=>$userId]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-shell">
    <h1>מי צפה בי</h1>

    <?php if (!$rows): ?>
        <p>אין צפיות עדיין</p>
    <?php else: ?>

        <div style="display:grid;gap:15px">

            <?php foreach ($rows as $r): ?>
                <div style="border:1px solid #ddd;padding:15px;border-radius:12px">

                    <h3><?= htmlspecialchars($r['Name']) ?></h3>

                    <p>גיל: <?= htmlspecialchars($r['Age'] ?? '') ?></p>
                    <p>אזור: <?= htmlspecialchars($r['Zone_Str'] ?? '') ?></p>
                    <p>מקום: <?= htmlspecialchars($r['Place_Str'] ?? '') ?></p>

                    <small>נצפה: <?= htmlspecialchars($r['Date']) ?></small>

                    <br><br>

                    <a href="?page=profile&id=<?= (int)$r['Id'] ?>">
                        לפרופיל
                    </a>

                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>
</section>