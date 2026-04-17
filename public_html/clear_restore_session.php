<?php
// ===== FILE: clear_restore_session.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['restore_user_id'], $_SESSION['restore_user_name']);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['ok' => true]);
exit;
