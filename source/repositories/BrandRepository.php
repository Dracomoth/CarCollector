<?php
/**
 * Brand Repository
 * 
 * Handles database operations for the 'brands' catalog table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class BrandRepository {
    /**
     * Retrieve all brands ordered by name
     * 
     * @return array
    /**
     * Retrieve all brands ordered by name, with models count
     * 
     * @param int|null $userId If supplied, counts models for that user; otherwise overall.
     * @return array
     */
    public static function getAll(?int $userId = null): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            if ($userId !== null && $userId > 0) {
                $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`,
                               COUNT(m.`id`) AS `models_count`
                        FROM `brands` b
                        LEFT JOIN `models` m ON m.`brand_id` = b.`id` AND m.`user_id` = :uid
                        GROUP BY b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                        ORDER BY b.`name` ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['uid' => $userId]);
            } else {
                $sql = "SELECT b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`,
                               COUNT(m.`id`) AS `models_count`
                        FROM `brands` b
                        LEFT JOIN `models` m ON m.`brand_id` = b.`id`
                        GROUP BY b.`id`, b.`name`, b.`description`, b.`logo_path`, b.`created`, b.`modified`
                        ORDER BY b.`name` ASC";
                $stmt = $pdo->query($sql);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            try {
                $stmt = $pdo->query("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified`, 0 AS `models_count` FROM `brands` ORDER BY `name` ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {
                error_log("Failed to fetch brands: " . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Find a brand by its ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified` FROM `brands` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            return $brand ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find brand by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a brand by name
     * 
     * @param string $name
     * @return array|null
     */
    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified` FROM `brands` WHERE `name` = :name LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            return $brand ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find brand by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a brand name already exists, optionally excluding a given ID (for updates)
     * 
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public static function nameExists(string $name, ?int $excludeId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return false;
        }

        try {
            if ($excludeId !== null && $excludeId > 0) {
                $stmt = $pdo->prepare("SELECT 1 FROM `brands` WHERE `name` = :name AND `id` != :exclude_id LIMIT 1");
                $stmt->execute(['name' => trim($name), 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM `brands` WHERE `name` = :name LIMIT 1");
                $stmt->execute(['name' => trim($name)]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check brand name existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new brand record
     * 
     * @param array $data ['name' => ..., 'description' => ..., 'logo_path' => ...]
     * @return int Inserted brand ID
     * @throws Exception
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $sql = "INSERT INTO `brands` (`name`, `description`, `logo_path`, `created`, `modified`)
                VALUES (:name, :description, :logo_path, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name'        => trim($data['name']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'logo_path'   => !empty($data['logo_path']) ? trim($data['logo_path']) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing brand record
     * 
     * @param int $id
     * @param array $data ['name' => ..., 'description' => ..., 'logo_path' => ...]
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid brand ID or database connection unavailable.");
        }

        $sql = "UPDATE `brands` 
                SET `name` = :name,
                    `description` = :description,
                    `logo_path` = :logo_path,
                    `modified` = NOW()
                WHERE `id` = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id'          => $id,
            'name'        => trim($data['name']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'logo_path'   => !empty($data['logo_path']) ? trim($data['logo_path']) : null,
        ]);
    }

    /**
     * Update only the logo_path of a brand
     * 
     * @param int $id
     * @param string|null $logoPath
     * @return bool
     */
    public static function updateLogoPath(int $id, ?string $logoPath): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("UPDATE `brands` SET `logo_path` = :logo_path, `modified` = NOW() WHERE `id` = :id");
            return $stmt->execute([
                'id'        => $id,
                'logo_path' => !empty($logoPath) ? trim($logoPath) : null,
            ]);
        } catch (Throwable $e) {
            error_log("Failed to update logo path for brand {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a brand by ID
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid brand ID or database connection unavailable.");
        }

        $stmt = $pdo->prepare("DELETE FROM `brands` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
