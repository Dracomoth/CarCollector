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
            // Fetch brands
            $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                    FROM `brands` b
                    ORDER BY b.`name` ASC, b.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fetch user's models for all brands
            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`, m.`brand_id`
                FROM `models` m
                WHERE m.`user_id` = :user_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute(['user_id' => $userId]);
            $models = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $modelsByBrand = [];
            foreach ($models as $m) {
                $bid = (int)$m['brand_id'];
                if (!isset($modelsByBrand[$bid])) {
                    $modelsByBrand[$bid] = [];
                }
                $modelsByBrand[$bid][] = [
                    'id'         => (int)$m['id'],
                    'model'      => $m['model'],
                    'image_path' => $m['image_path'],
                ];
            }

            foreach ($brands as &$b) {
                $bid = (int)$b['id'];
                $b['models'] = $modelsByBrand[$bid] ?? [];
                $b['models_count'] = count($b['models']);
            }
            unset($b);

            return $brands;
        } catch (Throwable $e) {
            error_log("Failed to fetch brands gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single brand details with user's model count and models list.
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
            $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                    FROM `brands` b
                    WHERE b.`id` = :brand_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['brand_id' => $brandId]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$brand) {
                return null;
            }

            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`
                FROM `models` m
                WHERE m.`user_id` = :user_id AND m.`brand_id` = :brand_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute([
                'user_id'  => $userId,
                'brand_id' => $brandId,
            ]);
            $brand['models'] = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $brand['models_count'] = count($brand['models']);

            return $brand;
        } catch (Throwable $e) {
            error_log("Failed to fetch brand details for brand {$brandId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve models belonging to a brand for a specific user.
     * 
     * @param int $brandId
     * @param int $userId
     * @return array
     */
    public static function getModelsByBrandId(int $brandId, int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $brandId <= 0 || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`
                FROM `models` m
                WHERE m.`user_id` = :user_id AND m.`brand_id` = :brand_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $stmt->execute([
                'user_id'  => $userId,
                'brand_id' => $brandId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch models for brand {$brandId}: " . $e->getMessage());
            return [];
        }
    }
}
