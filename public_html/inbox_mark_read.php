<?php

/**
 * inbox_mark_read.php
 * מסמן הודעות כנקראו עבור שיחה אחת בלבד
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    if (!isset($pdo)) {
        throw new Exception('PDO missing');
    }

    $me = (int)$_SESSION['user_id'];
    $other = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($other <= 0) {
        throw new Exception('Invalid user_id');
    }

    $sql = "
    UPDATE messages
    SET New = 0
    WHERE ById = :other
      AND Id = :me
      AND New = 1
      AND (Deleted_By_Id = 0 OR Deleted_By_Id IS NULL)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':other' => $other,
        ':me'    => $me
    ]);

    echo "OK";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR\n";
    echo $e->getMessage();
}
