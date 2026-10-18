<?php
/**
 * Model Repository
 * 
 * Handles database operations for the 'models' collection table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class ModelRepository {
    /**
     * Retrieve all models for a specific user, with joined catalog and metadata labels
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
            $sql = "SELECT m.*,
                           b.name AS brand_name,
                           mk.name AS maker_name,
                           s.scale AS scale_name,
                           c.name AS color_name,
                           c.hex_code AS color_hex,
                           cd.name AS condition_name
                    FROM `models` m
                    LEFT JOIN `brands` b ON b.id = m.brand_id
                    LEFT JOIN `makers` mk ON mk.id = m.maker_id
                    LEFT JOIN `scales` s ON s.id = m.scale_id
                    LEFT JOIN `colors` c ON c.id = m.color_id
                    LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                    WHERE m.user_id = :user_id
                    ORDER BY m.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch models for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a model by ID, optionally ensuring it belongs to the given user
     * 
     * @param int $id
     * @param int|null $userId
     * @return array|null
     */
    public static function findById(int $id, ?int $userId = null): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            return null;
        }

        try {
            if ($userId !== null && $userId > 0) {
                $sql = "SELECT m.*,
                               b.name AS brand_name,
                               mk.name AS maker_name,
                               s.scale AS scale_name,
                               c.name AS color_name,
                               c.hex_code AS color_hex,
                               cd.name AS condition_name
                        FROM `models` m
                        LEFT JOIN `brands` b ON b.id = m.brand_id
                        LEFT JOIN `makers` mk ON mk.id = m.maker_id
                        LEFT JOIN `scales` s ON s.id = m.scale_id
                        LEFT JOIN `colors` c ON c.id = m.color_id
                        LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                        WHERE m.id = :id AND m.user_id = :user_id
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $id, 'user_id' => $userId]);
            } else {
                $sql = "SELECT m.*,
                               b.name AS brand_name,
                               mk.name AS maker_name,
                               s.scale AS scale_name,
                               c.name AS color_name,
                               c.hex_code AS color_hex,
                               cd.name AS condition_name
                        FROM `models` m
                        LEFT JOIN `brands` b ON b.id = m.brand_id
                        LEFT JOIN `makers` mk ON mk.id = m.maker_id
                        LEFT JOIN `scales` s ON s.id = m.scale_id
                        LEFT JOIN `colors` c ON c.id = m.color_id
                        LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                        WHERE m.id = :id
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $id]);
            }

            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            return $model ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find model by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new model record
     * 
     * @param array $data
     * @return int Inserted ID
     * @throws Exception
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $sql = "INSERT INTO `models` (
                    `user_id`, `brand_id`, `year`, `scale_id`, `series`, 
                    `series_number`, `serial_number`, `maker_id`, `model`, 
                    `model_year`, `color_id`, `condition_id`, `link`, 
                    `description`, `image_path`, `created`, `modified`
                ) VALUES (
                    :user_id, :brand_id, :year, :scale_id, :series, 
                    :series_number, :serial_number, :maker_id, :model, 
                    :model_year, :color_id, :condition_id, :link, 
                    :description, :image_path, NOW(), NOW()
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id'       => (int)$data['user_id'],
            'brand_id'      => (int)$data['brand_id'],
            'year'          => !empty($data['year']) ? (int)$data['year'] : null,
            'scale_id'      => (int)$data['scale_id'],
            'series'        => !empty($data['series']) ? trim($data['series']) : null,
            'series_number' => !empty($data['series_number']) ? trim($data['series_number']) : null,
            'serial_number' => !empty($data['serial_number']) ? trim($data['serial_number']) : null,
            'maker_id'      => !empty($data['maker_id']) ? (int)$data['maker_id'] : null,
            'model'         => trim($data['model']),
            'model_year'    => !empty($data['model_year']) ? (int)$data['model_year'] : null,
            'color_id'      => !empty($data['color_id']) ? (int)$data['color_id'] : null,
            'condition_id'  => !empty($data['condition_id']) ? (int)$data['condition_id'] : null,
            'link'          => !empty($data['link']) ? trim($data['link']) : null,
            'description'   => !empty($data['description']) ? trim($data['description']) : null,
            'image_path'    => !empty($data['image_path']) ? trim($data['image_path']) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing model record
     * 
     * @param int $id
     * @param array $data
     * @param int|null $userId
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, array $data, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid model ID or database connection unavailable.");
        }

        $sql = "UPDATE `models` SET
                    `brand_id`      = :brand_id,
                    `year`          = :year,
                    `scale_id`      = :scale_id,
                    `series`        = :series,
                    `series_number` = :series_number,
                    `serial_number` = :serial_number,
                    `maker_id`      = :maker_id,
                    `model`         = :model,
                    `model_year`    = :model_year,
                    `color_id`      = :color_id,
                    `condition_id`  = :condition_id,
                    `link`          = :link,
                    `description`   = :description,
                    `image_path`    = :image_path,
                    `modified`      = NOW()
                WHERE `id` = :id";

        $params = [
            'id'            => $id,
            'brand_id'      => (int)$data['brand_id'],
            'year'          => !empty($data['year']) ? (int)$data['year'] : null,
            'scale_id'      => (int)$data['scale_id'],
            'series'        => !empty($data['series']) ? trim($data['series']) : null,
            'series_number' => !empty($data['series_number']) ? trim($data['series_number']) : null,
            'serial_number' => !empty($data['serial_number']) ? trim($data['serial_number']) : null,
            'maker_id'      => !empty($data['maker_id']) ? (int)$data['maker_id'] : null,
            'model'         => trim($data['model']),
            'model_year'    => !empty($data['model_year']) ? (int)$data['model_year'] : null,
            'color_id'      => !empty($data['color_id']) ? (int)$data['color_id'] : null,
            'condition_id'  => !empty($data['condition_id']) ? (int)$data['condition_id'] : null,
            'link'          => !empty($data['link']) ? trim($data['link']) : null,
            'description'   => !empty($data['description']) ? trim($data['description']) : null,
            'image_path'    => !empty($data['image_path']) ? trim($data['image_path']) : null,
        ];

        if ($userId !== null && $userId > 0) {
            $sql .= " AND `user_id` = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update only image_path for a model
     * 
     * @param int $id
     * @param string|null $imagePath
     * @param int|null $userId
     * @return bool
     */
    public static function updateImagePath(int $id, ?string $imagePath, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            return false;
        }

        try {
            $sql = "UPDATE `models` SET `image_path` = :image_path, `modified` = NOW() WHERE `id` = :id";
            $params = [
                'id'         => $id,
                'image_path' => !empty($imagePath) ? trim($imagePath) : null,
            ];

            if ($userId !== null && $userId > 0) {
                $sql .= " AND `user_id` = :user_id";
                $params['user_id'] = $userId;
            }

            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Throwable $e) {
            error_log("Failed to update image path for model {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a model record
     * 
     * @param int $id
     * @param int|null $userId
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid model ID or database connection unavailable.");
        }

        $sql = "DELETE FROM `models` WHERE `id` = :id";
        $params = ['id' => $id];

        if ($userId !== null && $userId > 0) {
            $sql .= " AND `user_id` = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Count models for a user
     * 
     * @param int $userId
     * @return int
     */
    public static function countByUser(int $userId): int {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `models` WHERE `user_id` = :user_id");
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to count models for user {$userId}: " . $e->getMessage());
            return 0;
        }
    }
}
