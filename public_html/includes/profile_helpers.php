<?php

if (!function_exists('getMainProfileImage')) {
    function getMainProfileImage(PDO $pdo, int $id): string {
        try {
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id
                  AND Main_Pic = 1
                  AND Pic_Status = 1
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $pic = $stmt->fetchColumn();

            if ($pic) {
                return '/uploads/' . ltrim((string)$pic, '/');
            }
        } catch (Throwable $e) {
        }

        return '/images/no_photo.jpg';
    }
}

if (!function_exists('is_user_online')) {
    function is_user_online(PDO $pdo, int $userId): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT last_seen
                FROM users_profile
                WHERE Id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $userId]);
            $ts = $stmt->fetchColumn();

            if (!$ts) {
                return false;
            }

            return strtotime((string)$ts) >= (time() - 120);
        } catch (Throwable $e) {
            return false;
        }
    }
}
