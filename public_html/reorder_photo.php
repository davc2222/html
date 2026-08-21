<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$picNum = (int)($_POST['pic_num'] ?? 0);
$action = (string)($_POST['action'] ?? '');

if ($userId <= 0) {
    header('Location: /?page=login');
    exit;
}

$profileUrl = '/?page=profile&id=' . $userId . '&edit=1';

if ($picNum <= 0 || !in_array($action, ['first', 'up', 'down', 'last'], true)) {
    header('Location: ' . $profileUrl);
    exit;
}

try {
    $pdo->beginTransaction();

    /* כל התמונות של המשתמש לפי הסדר הנוכחי */
    $stmt = $pdo->prepare("
        SELECT Pic_Num
        FROM user_pics
        WHERE Id = :id
        ORDER BY
            CASE
                WHEN Sort_Order IS NOT NULL AND Sort_Order > 0 THEN Sort_Order
                ELSE 1000000 + Pic_Num
            END ASC,
            Pic_Num ASC
        FOR UPDATE
    ");
    $stmt->execute([':id' => $userId]);

    $photoIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $index = array_search($picNum, $photoIds, true);

    if ($index !== false) {
        $count = count($photoIds);

        if ($action === 'first' && $index > 0) {
            array_splice($photoIds, $index, 1);
            array_unshift($photoIds, $picNum);
        } elseif ($action === 'up' && $index > 0) {
            [$photoIds[$index - 1], $photoIds[$index]] = [$photoIds[$index], $photoIds[$index - 1]];
        } elseif ($action === 'down' && $index < $count - 1) {
            [$photoIds[$index + 1], $photoIds[$index]] = [$photoIds[$index], $photoIds[$index + 1]];
        } elseif ($action === 'last' && $index < $count - 1) {
            array_splice($photoIds, $index, 1);
            $photoIds[] = $picNum;
        }

        $update = $pdo->prepare("
            UPDATE user_pics
            SET Sort_Order = :sort_order
            WHERE Id = :id
              AND Pic_Num = :pic_num
        ");

        foreach ($photoIds as $position => $photoId) {
            $update->execute([
                ':sort_order' => $position + 1,
                ':id'         => $userId,
                ':pic_num'    => $photoId,
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('REORDER PHOTO ERROR: ' . $e->getMessage());
}

header('Location: ' . $profileUrl);
exit;
