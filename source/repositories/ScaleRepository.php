<?php
/**
 * Scale Repository
 * 
 * Handles database operations for the 'scales' metadata catalog table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class ScaleRepository {
    /**
     * Retrieve all scales ordered by ID (natural sort order)
     * 
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT `id`, `scale`, `created`, `modified` FROM `scales` ORDER BY `id` ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch scales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a scale by its ID
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
            $stmt = $pdo->prepare("SELECT `id`, `scale`, `created`, `modified` FROM `scales` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $scale = $stmt->fetch(PDO::FETCH_ASSOC);
            return $scale ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find scale by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a scale by its ratio string (e.g. '1:64')
     * 
     * @param string $scale
     * @return array|null
     */
    public static function findByScale(string $scale): ?array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT `id`, `scale`, `created`, `modified` FROM `scales` WHERE `scale` = :scale LIMIT 1");
            $stmt->execute(['scale' => trim($scale)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find scale: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a scale string already exists, optionally excluding a given ID (for updates)
     * 
     * @param string $scale
     * @param int|null $excludeId
     * @return bool
     */
    public static function scaleExists(string $scale, ?int $excludeId = null): bool {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return false;
        }

        try {
            if ($excludeId !== null && $excludeId > 0) {
                $stmt = $pdo->prepare("SELECT 1 FROM `scales` WHERE `scale` = :scale AND `id` != :exclude_id LIMIT 1");
                $stmt->execute(['scale' => trim($scale), 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM `scales` WHERE `scale` = :scale LIMIT 1");
                $stmt->execute(['scale' => trim($scale)]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check scale existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new scale record
     * 
     * @param string $scale
     * @return int Inserted scale ID
     * @throws Exception
     */
    public static function create(string $scale): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $cleanScale = trim($scale);
        if ($cleanScale === '') {
            throw new Exception("Scale ratio cannot be empty.");
        }

        if (self::scaleExists($cleanScale)) {
            throw new Exception("Scale '{$cleanScale}' already exists in the catalog.");
        }

        $sql = "INSERT INTO `scales` (`scale`, `created`, `modified`) VALUES (:scale, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['scale' => $cleanScale]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing scale record
     * 
     * @param int $id
     * @param string $scale
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, string $scale): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid scale ID or database connection unavailable.");
        }

        $cleanScale = trim($scale);
        if ($cleanScale === '') {
            throw new Exception("Scale ratio cannot be empty.");
        }

        if (self::scaleExists($cleanScale, $id)) {
            throw new Exception("Scale '{$cleanScale}' already exists on another record.");
        }

        $sql = "UPDATE `scales` SET `scale` = :scale, `modified` = NOW() WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id'    => $id,
            'scale' => $cleanScale,
        ]);
    }

    /**
     * Delete a scale by ID
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid scale ID or database connection unavailable.");
        }

        $stmt = $pdo->prepare("DELETE FROM `scales` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
