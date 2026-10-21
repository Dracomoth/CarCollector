<?php
/**
 * Makers Gallery Repository
 * 
 * Data access layer for the Makers Gallery showcase.
 * Provides catalog maker details along with models counts for the logged-in user.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class MakersGalleryRepository {
    /**
     * Retrieve all makers with models count for the given user, ordered by name ASC.
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllMakers(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`
                    FROM `makers` mk
                    ORDER BY mk.`name` ASC, mk.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $makers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fetch user's models for all makers
            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`, m.`modified`, m.`maker_id`
                FROM `models` m
                WHERE m.`user_id` = :user_id AND m.`maker_id` IS NOT NULL
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute(['user_id' => $userId]);
            $models = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $modelsByMaker = [];
            foreach ($models as $m) {
                $mid = (int)$m['maker_id'];
                if (!isset($modelsByMaker[$mid])) {
                    $modelsByMaker[$mid] = [];
                }
                $modelsByMaker[$mid][] = [
                    'id'         => (int)$m['id'],
                    'model'      => $m['model'],
                    'image_path' => $m['image_path'],
                    'modified'   => $m['modified'],
                ];
            }

            foreach ($makers as &$mk) {
                $mid = (int)$mk['id'];
                $mk['models'] = $modelsByMaker[$mid] ?? [];
                $mk['models_count'] = count($mk['models']);
            }
            unset($mk);

            return $makers;
        } catch (Throwable $e) {
            error_log("Failed to fetch makers gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single maker details with user's model count and models list.
     * 
     * @param int $makerId
     * @param int $userId
     * @return array|null
     */
    public static function getMakerById(int $makerId, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $makerId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT mk.`id`, mk.`name`, mk.`description`, mk.`logo_path`, mk.`created`, mk.`modified`
                    FROM `makers` mk
                    WHERE mk.`id` = :maker_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['maker_id' => $makerId]);
            $maker = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$maker) {
                return null;
            }

            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`, m.`modified`
                FROM `models` m
                WHERE m.`user_id` = :user_id AND m.`maker_id` = :maker_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute([
                'user_id'  => $userId,
                'maker_id' => $makerId,
            ]);
            $maker['models'] = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $maker['models_count'] = count($maker['models']);

            return $maker;
        } catch (Throwable $e) {
            error_log("Failed to fetch maker details for maker {$makerId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve models belonging to a maker for a specific user.
     * 
     * @param int $makerId
     * @param int $userId
     * @return array
     */
    public static function getModelsByMakerId(int $makerId, int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $makerId <= 0 || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`, m.`modified`
                FROM `models` m
                WHERE m.`user_id` = :user_id AND m.`maker_id` = :maker_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $stmt->execute([
                'user_id'  => $userId,
                'maker_id' => $makerId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch models for maker {$makerId}: " . $e->getMessage());
            return [];
        }
    }
}
