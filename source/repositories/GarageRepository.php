<?php
/**
 * Garage Repository
 * 
 * Data access layer for the user's scale car showcase gallery.
 * Queries models owned by the logged-in user with all metadata,
 * catalog brands/makers with logos, and custom collection associations.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class GarageRepository {
    /**
     * Retrieve all models for a user with joined metadata, logos, and custom collections.
     * 
     * @param int $userId
     * @param array $filters Optional filter criteria (model, brand_id, maker_id, collection_id, scale_id)
     * @param string $sortOrder 'asc' or 'desc' (default: 'asc')
     * @return array
     */
    public static function getAllModelsByUser(int $userId, array $filters = [], string $sortOrder = 'asc'): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        $orderDir = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';

        try {
            $sql = "SELECT m.*,
                           b.name AS brand_name,
                           b.logo_path AS brand_logo_path,
                           b.modified AS brand_modified,
                           mk.name AS maker_name,
                           mk.logo_path AS maker_logo_path,
                           mk.modified AS maker_modified,
                           s.scale AS scale_name,
                           c.name AS color_name,
                           c.hex_code AS color_hex,
                           cd.name AS condition_name,
                           GROUP_CONCAT(DISTINCT ci.collection_id) AS collection_ids,
                           GROUP_CONCAT(DISTINCT col.name ORDER BY col.name SEPARATOR ', ') AS collection_names
                    FROM `models` m
                    LEFT JOIN `brands` b ON b.id = m.brand_id
                    LEFT JOIN `makers` mk ON mk.id = m.maker_id
                    LEFT JOIN `scales` s ON s.id = m.scale_id
                    LEFT JOIN `colors` c ON c.id = m.color_id
                    LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                    LEFT JOIN `collection_items` ci ON ci.model_id = m.id
                    LEFT JOIN `collections` col ON col.id = ci.collection_id AND col.user_id = :col_user_id
                    WHERE m.user_id = :user_id";

            $params = [
                'user_id'     => $userId,
                'col_user_id' => $userId,
            ];

            // Filter: Model name
            if (!empty($filters['model'])) {
                $sql .= " AND m.model LIKE :model_search";
                $params['model_search'] = '%' . trim($filters['model']) . '%';
            }

            // Filter: Brand
            if (!empty($filters['brand_id'])) {
                $sql .= " AND m.brand_id = :brand_id";
                $params['brand_id'] = (int)$filters['brand_id'];
            }

            // Filter: Maker
            if (!empty($filters['maker_id'])) {
                $sql .= " AND m.maker_id = :maker_id";
                $params['maker_id'] = (int)$filters['maker_id'];
            }

            // Filter: Scale
            if (!empty($filters['scale_id'])) {
                $sql .= " AND m.scale_id = :scale_id";
                $params['scale_id'] = (int)$filters['scale_id'];
            }

            $sql .= " GROUP BY m.id";

            // Filter: Collection (having collection_id in collection_items)
            if (!empty($filters['collection_id'])) {
                $sql .= " HAVING FIND_IN_SET(:collection_id, collection_ids)";
                $params['collection_id'] = (int)$filters['collection_id'];
            }

            $sql .= " ORDER BY m.model {$orderDir}, m.id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch garage models for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single model details for Detail Mode.
     * 
     * @param int $modelId
     * @param int $userId
     * @return array|null
     */
    public static function getModelById(int $modelId, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $modelId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT m.*,
                           b.name AS brand_name,
                           b.logo_path AS brand_logo_path,
                           b.modified AS brand_modified,
                           mk.name AS maker_name,
                           mk.logo_path AS maker_logo_path,
                           mk.modified AS maker_modified,
                           s.scale AS scale_name,
                           c.name AS color_name,
                           c.hex_code AS color_hex,
                           cd.name AS condition_name,
                           GROUP_CONCAT(DISTINCT ci.collection_id) AS collection_ids,
                           GROUP_CONCAT(DISTINCT col.name ORDER BY col.name SEPARATOR ', ') AS collection_names
                    FROM `models` m
                    LEFT JOIN `brands` b ON b.id = m.brand_id
                    LEFT JOIN `makers` mk ON mk.id = m.maker_id
                    LEFT JOIN `scales` s ON s.id = m.scale_id
                    LEFT JOIN `colors` c ON c.id = m.color_id
                    LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                    LEFT JOIN `collection_items` ci ON ci.model_id = m.id
                    LEFT JOIN `collections` col ON col.id = ci.collection_id AND col.user_id = :col_user_id
                    WHERE m.id = :model_id AND m.user_id = :user_id
                    GROUP BY m.id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'model_id'    => $modelId,
                'user_id'     => $userId,
                'col_user_id' => $userId,
            ]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Throwable $e) {
            error_log("Failed to fetch garage model {$modelId} for user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve available filter options (brands, makers, collections, scales) for a user.
     * 
     * @param int $userId
     * @return array
     */
    public static function getFilterOptions(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [
                'brands'      => [],
                'makers'      => [],
                'collections' => [],
                'scales'      => [],
            ];
        }

        try {
            // All catalog brands so user can filter by any registered brand (even with 0 models)
            $stmt = $pdo->query("SELECT id, name FROM `brands` ORDER BY name ASC");
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // All catalog makers
            $stmt = $pdo->query("SELECT id, name FROM `makers` ORDER BY name ASC");
            $makers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Custom Collections belonging to user
            $stmt = $pdo->prepare("SELECT id, name FROM `collections` WHERE user_id = :user_id ORDER BY name ASC");
            $stmt->execute(['user_id' => $userId]);
            $collections = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // All catalog scales
            $stmt = $pdo->query("SELECT id, scale AS name FROM `scales` ORDER BY scale ASC");
            $scales = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'brands'      => $brands,
                'makers'      => $makers,
                'collections' => $collections,
                'scales'      => $scales,
            ];
        } catch (Throwable $e) {
            error_log("Failed to fetch filter options for user {$userId}: " . $e->getMessage());
            return [
                'brands'      => [],
                'makers'      => [],
                'collections' => [],
                'scales'      => [],
            ];
        }
    }
}
