<?php
/**
 * Collections Gallery Repository
 * 
 * Data access layer for the Collections Gallery showcase.
 * Provides custom user collection details along with models counts for the logged-in user.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class CollectionsGalleryRepository {
    /**
     * Retrieve all custom collections with models count for the given user, ordered by name ASC.
     * 
     * @param int $userId
     * @return array
     */
    public static function getAllCollections(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`,
                           COUNT(ci.`model_id`) AS `models_count`
                    FROM `collections` c
                    LEFT JOIN `collection_items` ci ON ci.`collection_id` = c.`id`
                    WHERE c.`user_id` = :user_id
                    GROUP BY c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`
                    ORDER BY c.`name` ASC, c.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch collections gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single collection details with user's model count.
     * 
     * @param int $collectionId
     * @param int $userId
     * @return array|null
     */
    public static function getCollectionById(int $collectionId, int $userId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo || $collectionId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`,
                           COUNT(ci.`model_id`) AS `models_count`
                    FROM `collections` c
                    LEFT JOIN `collection_items` ci ON ci.`collection_id` = c.`id`
                    WHERE c.`id` = :collection_id AND c.`user_id` = :user_id
                    GROUP BY c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'collection_id' => $collectionId,
                'user_id'       => $userId,
            ]);
            $collection = $stmt->fetch(PDO::FETCH_ASSOC);
            return $collection ?: null;
        } catch (Throwable $e) {
            error_log("Failed to fetch collection details for collection {$collectionId}: " . $e->getMessage());
            return null;
        }
    }
}
