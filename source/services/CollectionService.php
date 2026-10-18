<?php
/**
 * Collection Service
 * 
 * Provides statistics and metrics about the user's scale car collection.
 * Gracefully handles the state where the 'cars' table has not yet been migrated.
 */

require_once __DIR__ . '/Database.php';

class CollectionService {
    /**
     * Get the total count of models in the collection (optionally by user).
     * 
     * @param int|null $userId
     * @return int
     */
    public static function getTotalCars(?int $userId = null): int {
        try {
            $pdo = Database::getConnection();
            if (!$pdo) {
                return 0;
            }

            // Check if 'models' table exists
            $tableExists = $pdo->query("SHOW TABLES LIKE 'models'")->fetch();
            if ($tableExists) {
                if ($userId !== null && $userId > 0) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `models` WHERE `user_id` = :uid");
                    $stmt->execute(['uid' => $userId]);
                } else {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `models`");
                }
                return (int) ($stmt->fetchColumn() ?: 0);
            }

            // Fallback to cars table if exists
            $tableExistsCars = $pdo->query("SHOW TABLES LIKE 'cars'")->fetch();
            if ($tableExistsCars) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `cars`");
                return (int) ($stmt->fetchColumn() ?: 0);
            }

            return 0;
        } catch (Throwable $e) {
            error_log("CollectionService error counting cars: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get collection summary badges and telemetry data.
     * 
     * @return array
     */
    public static function getCollectionSummary(): array {
        $total = self::getTotalCars();
        return [
            'total'    => $total,
            'label'    => $total === 1 ? 'Car' : 'Cars',
            'scales'   => '1:18 • 1:43 • 1:64',
        ];
    }
}
