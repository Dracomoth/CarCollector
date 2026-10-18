<?php
/**
 * Condition Repository
 * 
 * Handles database operations for the 'conditions' metadata catalog table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class ConditionRepository {
    /**
     * Retrieve all conditions ordered by ID (preserves collector severity ranking)
     * 
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT `id`, `name`, `created`, `modified` FROM `conditions` ORDER BY `id` ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch conditions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a condition by its ID
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `created`, `modified` FROM `conditions` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $condition = $stmt->fetch(PDO::FETCH_ASSOC);
            return $condition ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find condition by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a condition by name
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `created`, `modified` FROM `conditions` WHERE `name` = :name LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find condition by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a condition name already exists, optionally excluding an ID (for updates)
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
                $stmt = $pdo->prepare("SELECT 1 FROM `conditions` WHERE `name` = :name AND `id` != :exclude_id LIMIT 1");
                $stmt->execute(['name' => trim($name), 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM `conditions` WHERE `name` = :name LIMIT 1");
                $stmt->execute(['name' => trim($name)]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check condition name existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new condition record
     * 
     * @param string $name
     * @return int Inserted condition ID
     * @throws Exception
     */
    public static function create(string $name): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new Exception("Condition name cannot be empty.");
        }

        if (self::nameExists($cleanName)) {
            throw new Exception("Condition '{$cleanName}' already exists in the catalog.");
        }

        $sql = "INSERT INTO `conditions` (`name`, `created`, `modified`) VALUES (:name, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $cleanName]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing condition record
     * 
     * @param int $id
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, string $name): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid condition ID or database connection unavailable.");
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new Exception("Condition name cannot be empty.");
        }

        if (self::nameExists($cleanName, $id)) {
            throw new Exception("Condition '{$cleanName}' already exists on another record.");
        }

        $sql = "UPDATE `conditions` SET `name` = :name, `modified` = NOW() WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id'   => $id,
            'name' => $cleanName,
        ]);
    }

    /**
     * Delete a condition by ID
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid condition ID or database connection unavailable.");
        }

        $stmt = $pdo->prepare("DELETE FROM `conditions` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
