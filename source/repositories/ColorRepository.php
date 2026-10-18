<?php
/**
 * Color Repository
 * 
 * Handles database operations for the 'colors' metadata catalog table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class ColorRepository {
    /**
     * Normalize and validate a HEX color string into #RRGGBB format
     * 
     * @param string $hex
     * @return string
     * @throws Exception
     */
    public static function normalizeHex(string $hex): string {
        $clean = trim($hex);
        if ($clean === '') {
            throw new Exception("Hex code cannot be empty.");
        }

        // Remove leading # if present
        if (str_starts_with($clean, '#')) {
            $clean = substr($clean, 1);
        }

        // Validate hex characters
        if (!ctype_xdigit($clean)) {
            throw new Exception("Invalid hex code: '{$hex}'. Only hexadecimal characters (0-9, A-F) are allowed.");
        }

        // Handle 3-digit shorthand (e.g. FFF -> FFFFFF)
        if (strlen($clean) === 3) {
            $clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
        }

        if (strlen($clean) !== 6) {
            throw new Exception("Hex code must be 6 hex digits (e.g. #FFFFFF or FFFFFF).");
        }

        return '#' . strtoupper($clean);
    }

    /**
     * Retrieve all colors ordered by ID
     * 
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT `id`, `name`, `hex_code`, `created`, `modified` FROM `colors` ORDER BY `id` ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch colors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a color by its ID
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `hex_code`, `created`, `modified` FROM `colors` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $color = $stmt->fetch(PDO::FETCH_ASSOC);
            return $color ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find color by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a color by name
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
            $stmt = $pdo->prepare("SELECT `id`, `name`, `hex_code`, `created`, `modified` FROM `colors` WHERE `name` = :name LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find color by name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a color name already exists, optionally excluding an ID (for updates)
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
                $stmt = $pdo->prepare("SELECT 1 FROM `colors` WHERE `name` = :name AND `id` != :exclude_id LIMIT 1");
                $stmt->execute(['name' => trim($name), 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM `colors` WHERE `name` = :name LIMIT 1");
                $stmt->execute(['name' => trim($name)]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to check color name existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new color record
     * 
     * @param string $name
     * @param string $hexCode
     * @return int Inserted color ID
     * @throws Exception
     */
    public static function create(string $name, string $hexCode): int {
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new Exception("Color name cannot be empty.");
        }

        $formattedHex = self::normalizeHex($hexCode);

        if (self::nameExists($cleanName)) {
            throw new Exception("Color name '{$cleanName}' already exists.");
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO `colors` (`name`, `hex_code`) VALUES (:name, :hex_code)");
            $stmt->execute([
                'name'     => $cleanName,
                'hex_code' => $formattedHex,
            ]);
            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log("Failed to create color: " . $e->getMessage());
            throw new Exception("Failed to create color: " . $e->getMessage());
        }
    }

    /**
     * Update an existing color record
     * 
     * @param int $id
     * @param string $name
     * @param string $hexCode
     * @return bool
     * @throws Exception
     */
    public static function update(int $id, string $name, string $hexCode): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid color ID.");
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new Exception("Color name cannot be empty.");
        }

        $formattedHex = self::normalizeHex($hexCode);

        if (self::nameExists($cleanName, $id)) {
            throw new Exception("Color name '{$cleanName}' is already taken by another entry.");
        }

        try {
            $stmt = $pdo->prepare("UPDATE `colors` SET `name` = :name, `hex_code` = :hex_code WHERE `id` = :id LIMIT 1");
            return $stmt->execute([
                'name'     => $cleanName,
                'hex_code' => $formattedHex,
                'id'       => $id,
            ]);
        } catch (Throwable $e) {
            error_log("Failed to update color ID {$id}: " . $e->getMessage());
            throw new Exception("Failed to update color: " . $e->getMessage());
        }
    }

    /**
     * Delete a color record
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        if (!$pdo || $id <= 0) {
            throw new Exception("Invalid color ID.");
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM `colors` WHERE `id` = :id LIMIT 1");
            return $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            error_log("Failed to delete color ID {$id}: " . $e->getMessage());
            throw new Exception("Failed to delete color: " . $e->getMessage());
        }
    }
}
