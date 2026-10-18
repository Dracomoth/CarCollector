<?php
/**
 * Maker Repository
 * 
 * Handles database operations for the 'makers' catalog table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class MakerRepository {
    /**
     * Retrieve all makers ordered by name
     * 
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified` FROM `makers` ORDER BY `name` ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch makers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a maker by its ID
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified` FROM `makers` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $maker = $stmt->fetch(PDO::FETCH_ASSOC);
            return $maker ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find maker by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a maker by name
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `description`, `logo_path`, `created`, `modified` FROM `makers` WHERE `name` = :name LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
            $maker = $stmt->fetch(PDO::FETCH_ASSOC);
            return $maker ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find maker by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a maker name already exists, optionally excluding a given ID (for updates)
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
                $stmt = $pdo->prepare("SELECT 1 FROM `makers` WHERE `name` = :name AND `id` != :exclude_id LIMIT 1");
                $stmt->execute(['name' => trim($name), 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM `makers` WHERE `name` = :name LIMIT 1");
                $stmt->execute(['name' => trim($name)]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check maker name existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new maker record
     * 
     * @param array $data ['name' => ..., 'description' => ..., 'logo_path' => ...]
     * @return int Inserted maker ID
     * @throws Exception
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $sql = "INSERT INTO `makers` (`name`, `description`, `logo_path`, `created`, `modified`)
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
     * Update an existing maker record
     * 
     * @param int $id
     * @param array $data ['name' => ..., 'description' => ..., 'logo_path' => ...]
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid maker ID or database connection unavailable.");
        }

        $sql = "UPDATE `makers` 
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
     * Update only the logo_path of a maker
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
            $stmt = $pdo->prepare("UPDATE `makers` SET `logo_path` = :logo_path, `modified` = NOW() WHERE `id` = :id");
            return $stmt->execute([
                'id'        => $id,
                'logo_path' => !empty($logoPath) ? trim($logoPath) : null,
            ]);
        } catch (Throwable $e) {
            error_log("Failed to update logo path for maker {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a maker by ID
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid maker ID or database connection unavailable.");
        }

        $stmt = $pdo->prepare("DELETE FROM `makers` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
