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
     * Get the total count of cars in the collection overall.
     * 
     * @return int
     */
    public static function getTotalCars(): int {
        try {
            $pdo = Database::getConnection();
            if (!$pdo) {
                return 0;
            }

            // Check if 'cars' table exists before querying
            $tableExists = $pdo->query("SHOW TABLES LIKE 'cars'")->fetch();
            if (!$tableExists) {
                return 0;
            }

            $stmt = $pdo->query("SELECT COUNT(*) FROM `cars`");
            $count = $stmt->fetchColumn();
            return (int) ($count ?: 0);
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
