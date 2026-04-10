<?php

if (!function_exists('getMainProfileImage')) {
    function getMainProfileImage(PDO $pdo, int $id): string {
        try {
            // תמונה ראשית
            $stmt = $pdo->prepare("
                SELECT Pic_Name
                FROM user_pics
                WHERE Id = :id AND Main_Pic = 1
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $img = $stmt->fetchColumn();

            // אם אין ראשית → כל תמונה
            if (empty($img)) {
                $stmt = $pdo->prepare("
                    SELECT Pic_Name
                    FROM user_pics
                    WHERE Id = :id
                    LIMIT 1
                ");
                $stmt->execute([':id' => $id]);
                $img = $stmt->fetchColumn();
            }

            if (!empty($img)) {
                return '/uploads/' . ltrim($img, '/'); // חשוב!
            }

            // fallback לפי מין
            $stmt = $pdo->prepare("
                SELECT Gender
                FROM users_profile
                WHERE Id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $gender = trim((string)$stmt->fetchColumn());

            $femaleValues = ['אישה', 'נקבה', 'female', 'f', 'woman', 'Female', 'F', 'Woman'];

            if (in_array($gender, $femaleValues, true)) {
                return '/images/default_female.svg';
            }

            return '/images/default_male.svg';
        } catch (Throwable $e) {
            return '/images/default_male.svg';
        }
    }
}
if (!function_exists('is_user_online')) {
    function is_user_online(PDO $pdo, int $userId): bool {
        try {
            $stmt = $pdo->prepare("
            SELECT UNIX_TIMESTAMP(last_seen)
            FROM users_profile
            WHERE Id = :id
            LIMIT 1
        ");
            $stmt->execute([':id' => $userId]);

            $lastSeen = (int)$stmt->fetchColumn();

            if (!$lastSeen) {
                return false;
            }

            // 🔥 120 שניות בלבד!
            return ($lastSeen >= (time() - 120));
        } catch (Throwable $e) {
            return false;
        }
    }
}
