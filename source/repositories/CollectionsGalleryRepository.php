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
            $sql = "SELECT c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`
                    FROM `collections` c
                    WHERE c.`user_id` = :user_id
                    ORDER BY c.`name` ASC, c.`id` ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $collections = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fetch user's models for all custom collections
            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`, ci.`collection_id`
                FROM `models` m
                JOIN `collection_items` ci ON ci.`model_id` = m.`id`
                WHERE m.`user_id` = :user_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute(['user_id' => $userId]);
            $models = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $modelsByCol = [];
            foreach ($models as $m) {
                $cid = (int)$m['collection_id'];
                if (!isset($modelsByCol[$cid])) {
                    $modelsByCol[$cid] = [];
                }
                $modelsByCol[$cid][] = [
                    'id'         => (int)$m['id'],
                    'model'      => $m['model'],
                    'image_path' => $m['image_path'],
                ];
            }

            foreach ($collections as &$c) {
                $cid = (int)$c['id'];
                $c['models'] = $modelsByCol[$cid] ?? [];
                $c['models_count'] = count($c['models']);
            }
            unset($c);

            return $collections;
        } catch (Throwable $e) {
            error_log("Failed to fetch collections gallery list for user {$userId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve single collection details with user's model count and models list.
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
            $sql = "SELECT c.`id`, c.`user_id`, c.`name`, c.`description`, c.`logo_path`, c.`created`, c.`modified`
                    FROM `collections` c
                    WHERE c.`id` = :collection_id AND c.`user_id` = :user_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'collection_id' => $collectionId,
                'user_id'       => $userId,
            ]);
            $collection = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$collection) {
                return null;
            }

            $mStmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`
                FROM `models` m
                JOIN `collection_items` ci ON ci.`model_id` = m.`id`
                WHERE m.`user_id` = :user_id AND ci.`collection_id` = :collection_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $mStmt->execute([
                'user_id'       => $userId,
                'collection_id' => $collectionId,
            ]);
            $collection['models'] = $mStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $collection['models_count'] = count($collection['models']);

            return $collection;
        } catch (Throwable $e) {
            error_log("Failed to fetch collection details for collection {$collectionId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve models belonging to a collection for a specific user.
     * 
     * @param int $collectionId
     * @param int $userId
     * @return array
     */
    public static function getModelsByCollectionId(int $collectionId, int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $collectionId <= 0 || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.`id`, m.`model`, m.`image_path`
                FROM `models` m
                JOIN `collection_items` ci ON ci.`model_id` = m.`id`
                WHERE m.`user_id` = :user_id AND ci.`collection_id` = :collection_id
                ORDER BY m.`created` DESC, m.`id` DESC
            ");
            $stmt->execute([
                'user_id'       => $userId,
                'collection_id' => $collectionId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Failed to fetch models for collection {$collectionId}: " . $e->getMessage());
            return [];
        }
    }
}
