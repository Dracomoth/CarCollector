<?php
/**
 * Screen Repository
 * 
 * Handles database operations for the 'screens' table.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class ScreenRepository {
    /**
     * Retrieve all screens ordered by sort_order
     * 
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->query("SELECT `id`, `screen_key`, `name`, `category`, `sort_order` FROM `screens` ORDER BY `sort_order` ASC, `id` ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch screens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a screen by its screen_key identifier
     * 
     * @param string $screenKey
     * @return array|null
     */
    public static function findByKey(string $screenKey): ?array {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT `id`, `screen_key`, `name`, `category`, `sort_order` FROM `screens` WHERE `screen_key` = :key LIMIT 1");
            $stmt->execute(['key' => $screenKey]);
            $screen = $stmt->fetch(PDO::FETCH_ASSOC);
            return $screen ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find screen by key {$screenKey}: " . $e->getMessage());
            return null;
        }
    }
}
