<?php
/**
 * Brands Gallery Repository
 * 
 * Data access layer for the Brands Gallery showcase.
 * Provides catalog brand details along with models counts for the logged-in user.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class BrandsGalleryRepository {
    /**
     * Retrieve all brands with models count for the given user, ordered by name ASC.
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllBrands(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`,
                           COUNT(m.`id`) AS `models_count`
                    FROM `brands` b
                    LEFT JOIN `models` m ON m.`brand_id` = b.`id` AND m.`user_id` = :user_id
                    GROUP BY b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                    ORDER BY b.`name` ASC, b.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch brands gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single brand details with user's model count.
     * 
     * @param int $brandId
     * @param int $userId
     * @return array|null
     */
    public static function getBrandById(int $brandId, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $brandId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`,
                           COUNT(m.`id`) AS `models_count`
                    FROM `brands` b
                    LEFT JOIN `models` m ON m.`brand_id` = b.`id` AND m.`user_id` = :user_id
                    WHERE b.`id` = :brand_id
                    GROUP BY b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'brand_id' => $brandId,
                'user_id'  => $userId,
            ]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            return $brand ?: null;
        } catch (Throwable $e) {
            error_log("Failed to fetch brand details for brand {$brandId}: " . $e->getMessage());
            return null;
        }
    }
}
