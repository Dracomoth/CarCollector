<?php
/**
 * Makers Gallery Repository
 * 
 * Data access layer for the Makers Gallery showcase.
 * Provides catalog maker details along with models counts for the logged-in user.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class MakersGalleryRepository {
    /**
     * Retrieve all makers with models count for the given user, ordered by name ASC.
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllMakers(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`,
                           COUNT(m.`id`) AS `models_count`
                    FROM `makers` mk
                    LEFT JOIN `models` m ON m.`maker_id` = mk.`id` AND m.`user_id` = :user_id
                    GROUP BY mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`
                    ORDER BY mk.`name` ASC, mk.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch makers gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single maker details with user's model count.
     * 
     * @param int $makerId
     * @param int $userId
     * @return array|null
     */
    public static function getMakerById(int $makerId, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $makerId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`,
                           COUNT(m.`id`) AS `models_count`
                    FROM `makers` mk
                    LEFT JOIN `models` m ON m.`maker_id` = mk.`id` AND m.`user_id` = :user_id
                    WHERE mk.`id` = :maker_id
                    GROUP BY mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'maker_id' => $makerId,
                'user_id'  => $userId,
            ]);
            $maker = $stmt->fetch(PDO::FETCH_ASSOC);
            return $maker ?: null;
        } catch (Throwable $e) {
            error_log("Failed to fetch maker details for maker {$makerId}: " . $e->getMessage());
            return null;
        }
    }
}
