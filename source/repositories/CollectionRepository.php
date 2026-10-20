<?php
/**
 * Collection Repository
 * 
 * Manages database operations for the user custom collections and their associated model items.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class CollectionRepository {
    /**
     * Retrieve all custom collections for a user with model counts.
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllByUser(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT c.*,
                           COUNT(ci.id) AS model_count
                    FROM `collections` c
                    LEFT JOIN `collection_items` ci ON ci.collection_id = c.id
                    WHERE c.user_id = :user_id
                    GROUP BY c.id
                    ORDER BY c.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch collections for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a collection by ID ensuring it belongs to the given user.
     * 
     * @param int $id
     * @param int $userId
     * @return array|null
     */
    public static function findById(int $id, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT c.*,
                           COUNT(ci.id) AS model_count
                    FROM `collections` c
                    LEFT JOIN `collection_items` ci ON ci.collection_id = c.id
                    WHERE c.id = :id AND c.user_id = :user_id
                    GROUP BY c.id
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find collection {$id} for user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a collection name already exists for the given user.
     * 
     * @param string $name
     * @param int $userId
     * @param int|null $excludeId
     * @return bool
     */
    public static function nameExists(string $name, int $userId, ?int $excludeId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0 || trim($name) === '') {
            return false;
        }

        try {
            $sql = "SELECT 1 FROM `collections` WHERE `user_id` = :user_id AND LOWER(TRIM(`name`)) = LOWER(TRIM(:name))";
            $params = [
                'user_id' => $userId,
                'name'    => $name,
            ];

            if ($excludeId !== null && $excludeId > 0) {
                $sql .= " AND `id` != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }

            $sql .= " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check collection nameExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new custom collection.
     * 
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $sql = "INSERT INTO `collections` (`user_id`, `name`, `description`, `logo_path`)
                VALUES (:user_id, :name, :description, :logo_path)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id'     => $data['user_id'],
            'name'        => trim($data['name'] ?? ''),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'logo_path'   => !empty($data['logo_path']) ? trim($data['logo_path']) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing custom collection.
     * 
     * @param int $id
     * @param array $data
     * @param int $userId
     * @return bool
     */
    public static function update(int $id, array $data, int $userId): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0 || $userId <= 0) {
            return false;
        }

        $params = [
            'id'          => $id,
            'user_id'     => $userId,
            'name'        => trim($data['name'] ?? ''),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
        ];

        $logoSql = "";
        if (array_key_exists('logo_path', $data)) {
            $logoSql = ", `logo_path` = :logo_path";
            $params['logo_path'] = $data['logo_path'];
        }

        $sql = "UPDATE `collections`
                SET `name` = :name,
                    `description` = :description
                    {$logoSql}
                WHERE `id` = :id AND `user_id` = :user_id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update only the logo path of a collection.
     * 
     * @param int $id
     * @param string|null $logoPath
     * @param int $userId
     * @return bool
     */
    public static function updateLogoPath(int $id, ?string $logoPath, int $userId): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0 || $userId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("UPDATE `collections` SET `logo_path` = :logo_path WHERE `id` = :id AND `user_id` = :user_id");
        return $stmt->execute([
            'logo_path' => $logoPath,
            'id'        => $id,
            'user_id'   => $userId,
        ]);
    }

    /**
     * Delete a collection and its items.
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function delete(int $id, int $userId): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0 || $userId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM `collections` WHERE `id` = :id AND `user_id` = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * Retrieve all models currently in a specific collection.
     * 
     * @param int $collectionId
     * @param int $userId
     * @return array
     */
    public static function getModelsInCollection(int $collectionId, int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $collectionId <= 0 || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT m.id, m.model, m.model_year, m.year, m.image_path,
                           b.name AS brand_name,
                           mk.name AS maker_name,
                           s.scale AS scale_name,
                           ci.id AS collection_item_id,
                           ci.created AS added_at
                    FROM `collection_items` ci
                    JOIN `collections` c ON c.id = ci.collection_id
                    JOIN `models` m ON m.id = ci.model_id
                    LEFT JOIN `brands` b ON b.id = m.brand_id
                    LEFT JOIN `makers` mk ON mk.id = m.maker_id
                    LEFT JOIN `scales` s ON s.id = m.scale_id
                    WHERE ci.collection_id = :collection_id AND c.user_id = :user_id
                    ORDER BY ci.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'collection_id' => $collectionId,
                'user_id'       => $userId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch models for collection {$collectionId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve models owned by the user that are NOT yet in the given collection.
     * Supports search and filtering by brand and maker.
     * 
     * @param int $collectionId
     * @param int $userId
     * @param array $filters [name, brand_id, maker_id]
     * @return array
     */
    public static function getAvailableModels(int $collectionId, int $userId, array $filters = []): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT m.id, m.model, m.model_year, m.year, m.image_path,
                           b.name AS brand_name,
                           mk.name AS maker_name,
                           s.scale AS scale_name
                    FROM `models` m
                    LEFT JOIN `brands` b ON b.id = m.brand_id
                    LEFT JOIN `makers` mk ON mk.id = m.maker_id
                    LEFT JOIN `scales` s ON s.id = m.scale_id
                    WHERE m.user_id = :user_id";

            $params = ['user_id' => $userId];

            // Exclude already added models if collectionId > 0
            if ($collectionId > 0) {
                $sql .= " AND m.id NOT IN (
                    SELECT model_id FROM `collection_items` WHERE collection_id = :collection_id
                )";
                $params['collection_id'] = $collectionId;
            }

            $search = !empty($filters['search']) ? $filters['search'] : (!empty($filters['name']) ? $filters['name'] : '');
            if (!empty($search)) {
                $searchTerm = '%' . trim($search) . '%';
                $sql .= " AND (m.model LIKE :search_model OR m.series LIKE :search_series OR b.name LIKE :search_brand OR mk.name LIKE :search_maker)";
                $params['search_model'] = $searchTerm;
                $params['search_series'] = $searchTerm;
                $params['search_brand'] = $searchTerm;
                $params['search_maker'] = $searchTerm;
            }

            if (!empty($filters['brand_id'])) {
                $sql .= " AND m.brand_id = :brand_id";
                $params['brand_id'] = (int)$filters['brand_id'];
            }

            if (!empty($filters['maker_id'])) {
                $sql .= " AND m.maker_id = :maker_id";
                $params['maker_id'] = (int)$filters['maker_id'];
            }

            $sql .= " ORDER BY m.model ASC, m.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch available models: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add multiple models to a collection.
     * 
     * @param int $collectionId
     * @param array $modelIds
     * @param int $userId
     * @return int Number of models added
     */
    public static function addModelsToCollection(int $collectionId, array $modelIds, int $userId): int {
        $pdo = Database::getConnection();
        if (!$pdo || $collectionId <= 0 || empty($modelIds) || $userId <= 0) {
            return 0;
        }

        // Verify collection ownership
        $coll = self::findById($collectionId, $userId);
        if (!$coll) {
            throw new Exception("Collection not found or access denied.");
        }

        $addedCount = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO `collection_items` (`collection_id`, `model_id`)
                               SELECT :collection_id, m.id
                               FROM `models` m
                               WHERE m.id = :model_id AND m.user_id = :user_id");

        foreach ($modelIds as $mId) {
            $mId = (int)$mId;
            if ($mId <= 0) continue;
            $stmt->execute([
                'collection_id' => $collectionId,
                'model_id'      => $mId,
                'user_id'       => $userId,
            ]);
            $addedCount += $stmt->rowCount();
        }

        return $addedCount;
    }

    /**
     * Remove a single model from a collection.
     * 
     * @param int $collectionId
     * @param int $modelId
     * @param int $userId
     * @return bool
     */
    public static function removeModelFromCollection(int $collectionId, int $modelId, int $userId): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $collectionId <= 0 || $modelId <= 0 || $userId <= 0) {
            return false;
        }

        // Verify collection ownership
        $coll = self::findById($collectionId, $userId);
        if (!$coll) {
            throw new Exception("Collection not found or access denied.");
        }

        $stmt = $pdo->prepare("DELETE FROM `collection_items`
                               WHERE `collection_id` = :collection_id AND `model_id` = :model_id");
        return $stmt->execute([
            'collection_id' => $collectionId,
            'model_id'      => $modelId,
        ]);
    }
}
