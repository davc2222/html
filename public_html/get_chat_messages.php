<?php
// ===== FILE: get_chat_messages.php =====

require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$me = (int)($_SESSION['user_id'] ?? 0);
$otherId = (int)($_GET['user_id'] ?? 0);

if ($me <= 0 || $otherId <= 0 || $otherId === $me) {
    echo json_encode([
        'ok' => false,
        'html' => ''
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $pdo->prepare("
        SELECT Msg_Num, Id, ById, Date_Sent, Msg_Txt
        FROM messages
        WHERE (
                Id = :me
            AND ById = :other_id
            AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
        )
           OR (
                Id = :other_id
            AND ById = :me
            AND (Deleted_By_ById = 0 OR Deleted_By_ById IS NULL)
        )
        ORDER BY Date_Sent ASC, Msg_Num ASC
    ");
    $stmt->execute([
        ':me' => $me,
        ':other_id' => $otherId
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();

    if (!$messages) {
        echo '<div class="cw-empty">אין עדיין הודעות</div>';
    } else {
        foreach ($messages as $msg) {
            $isMe = ((int)$msg['ById'] === $me);

            $time = '';
            if (!empty($msg['Date_Sent'])) {
                $time = date('d/m/Y H:i', strtotime((string)$msg['Date_Sent']));
            }
?>
            <div class="cw-row <?= $isMe ? 'cw-row-me' : 'cw-row-other' ?>">
                <div class="cw-bubble-wrap">
                    <div class="cw-bubble"><?= nl2br(h($msg['Msg_Txt'] ?? '')) ?></div>
                    <div class="cw-time"><?= h($time) ?></div>
                </div>
            </div>
<?php
        }
    }

    $html = ob_get_clean();

    echo json_encode([
        'ok' => true,
        'html' => $html
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'html' => ''
    ], JSON_UNESCAPED_UNICODE);
}
