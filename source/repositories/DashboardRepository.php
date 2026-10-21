<?php
/**
 * Dashboard Repository
 * 
 * Aggregates collection metrics, brand distribution percentages,
 * scale ratios, condition breakdowns, color spectra, and recent
 * acquisitions for the user's scale car collection dashboard.
 */

require_once dirname(__DIR__) . '/services/Database.php';

class DashboardRepository {
    /**
     * Curated harmonious palette for chart slices
     */
    public const CHART_PALETTE = [
        '#FF5722', // Flame Coral / Hot Wheels orange
        '#3B82F6', // Cobalt Sky Blue
        '#10B981', // Emerald Mint
        '#8B5CF6', // Royal Purple
        '#F59E0B', // Amber Gold
        '#EC4899', // Hot Pink
        '#06B6D4', // Electric Cyan
        '#14B8A6', // Deep Teal
        '#E11D48', // Crimson Red
        '#84CC16', // Lime Green
        '#6366F1', // Indigo
        '#F97316', // Bright Orange
        '#A855F7', // Violet
        '#0EA5E9', // Sky Blue
        '#EAB308', // Yellow
    ];

    /**
     * Retrieve high-level summary KPIs for a user
     * 
     * @param int $userId
     * @return array
     */
    public static function getSummaryStats(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [
                'total_models'      => 0,
                'total_brands'      => 0,
                'total_makers'      => 0,
                'total_scales'      => 0,
                'total_collections' => 0,
                'primary_scale'     => 'None',
                'min_year'          => null,
                'max_year'          => null,
            ];
        }

        try {
            // Main counts from models
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(m.id) AS total_models,
                    COUNT(DISTINCT m.brand_id) AS total_brands,
                    COUNT(DISTINCT m.maker_id) AS total_makers,
                    COUNT(DISTINCT m.scale_id) AS total_scales,
                    MIN(NULLIF(m.year, 0)) AS min_mfg_year,
                    MAX(NULLIF(m.year, 0)) AS max_mfg_year,
                    MIN(NULLIF(m.model_year, 0)) AS min_car_year,
                    MAX(NULLIF(m.model_year, 0)) AS max_car_year
                FROM `models` m
                WHERE m.user_id = :uid
            ");
            $stmt->execute(['uid' => $userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Total custom collections for user
            $colStmt = $pdo->prepare("SELECT COUNT(*) FROM `collections` WHERE `user_id` = :uid");
            $colStmt->execute(['uid' => $userId]);
            $totalCollections = (int) ($colStmt->fetchColumn() ?: 0);

            // Dominant scale
            $scaleStmt = $pdo->prepare("
                SELECT s.scale, COUNT(m.id) as cnt
                FROM `models` m
                JOIN `scales` s ON s.id = m.scale_id
                WHERE m.user_id = :uid
                GROUP BY s.id, s.scale
                ORDER BY cnt DESC
                LIMIT 1
            ");
            $scaleStmt->execute(['uid' => $userId]);
            $topScale = $scaleStmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_models'      => (int) ($stats['total_models'] ?? 0),
                'total_brands'      => (int) ($stats['total_brands'] ?? 0),
                'total_makers'      => (int) ($stats['total_makers'] ?? 0),
                'total_scales'      => (int) ($stats['total_scales'] ?? 0),
                'total_collections' => $totalCollections,
                'primary_scale'     => $topScale ? $topScale['scale'] : 'None',
                'primary_scale_count' => $topScale ? (int)$topScale['cnt'] : 0,
                'min_car_year'      => $stats['min_car_year'] ?? null,
                'max_car_year'      => $stats['max_car_year'] ?? null,
                'min_mfg_year'      => $stats['min_mfg_year'] ?? null,
                'max_mfg_year'      => $stats['max_mfg_year'] ?? null,
            ];
        } catch (Throwable $e) {
            error_log("DashboardRepository::getSummaryStats error: " . $e->getMessage());
            return [
                'total_models'      => 0,
                'total_brands'      => 0,
                'total_makers'      => 0,
                'total_scales'      => 0,
                'total_collections' => 0,
                'primary_scale'     => 'None',
            ];
        }
    }

    /**
     * Retrieve brand distribution data and percentage calculations for the user's collection.
     * Explicitly computed by diecast brand (not maker).
     * 
     * @param int $userId
     * @return array
     */
    public static function getBrandDistribution(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    b.id AS brand_id,
                    COALESCE(b.name, 'Unassigned Brand') AS brand_name,
                    b.logo_path AS brand_logo_path,
                    b.modified AS brand_modified,
                    COUNT(m.id) AS model_count
                FROM `models` m
                LEFT JOIN `brands` b ON b.id = m.brand_id
                WHERE m.user_id = :uid
                GROUP BY b.id, b.name, b.logo_path, b.modified
                ORDER BY model_count DESC, brand_name ASC
            ");
            $stmt->execute(['uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = 0;
            foreach ($rows as $r) {
                $total += (int) $r['model_count'];
            }

            $palette = self::CHART_PALETTE;
            $paletteCount = count($palette);

            $result = [];
            $accumulatedPct = 0;
            foreach ($rows as $index => $row) {
                $count = (int) $row['model_count'];
                $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
                $color = $palette[$index % $paletteCount];

                $result[] = [
                    'brand_id'        => $row['brand_id'] ? (int) $row['brand_id'] : null,
                    'brand_name'      => $row['brand_name'],
                    'brand_logo_path' => $row['brand_logo_path'],
                    'brand_modified'  => $row['brand_modified'] ?? null,
                    'model_count'     => $count,
                    'percentage'      => $pct,
                    'color'           => $color,
                    'accumulated_pct' => $accumulatedPct,
                ];
                $accumulatedPct += $pct;
            }

            return [
                'total'  => $total,
                'brands' => $result,
            ];
        } catch (Throwable $e) {
            error_log("DashboardRepository::getBrandDistribution error: " . $e->getMessage());
            return ['total' => 0, 'brands' => []];
        }
    }

    /**
     * Retrieve scale distribution and percentages for user's collection
     * 
     * @param int $userId
     * @return array
     */
    public static function getScaleDistribution(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    s.id AS scale_id,
                    COALESCE(s.scale, 'Unspecified') AS scale_name,
                    COUNT(m.id) AS model_count
                FROM `models` m
                LEFT JOIN `scales` s ON s.id = m.scale_id
                WHERE m.user_id = :uid
                GROUP BY s.id, s.scale
                ORDER BY model_count DESC
            ");
            $stmt->execute(['uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = 0;
            foreach ($rows as $r) {
                $total += (int) $r['model_count'];
            }

            $scales = [];
            foreach ($rows as $row) {
                $count = (int) $row['model_count'];
                $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
                $scales[] = [
                    'scale_id'    => $row['scale_id'] ? (int) $row['scale_id'] : null,
                    'scale_name'  => $row['scale_name'],
                    'model_count' => $count,
                    'percentage'  => $pct,
                ];
            }

            return $scales;
        } catch (Throwable $e) {
            error_log("DashboardRepository::getScaleDistribution error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve condition breakdown for user's collection
     * 
     * @param int $userId
     * @return array
     */
    public static function getConditionDistribution(int $userId): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    cd.id AS condition_id,
                    COALESCE(cd.name, 'Unspecified') AS condition_name,
                    COUNT(m.id) AS model_count
                FROM `models` m
                LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                WHERE m.user_id = :uid
                GROUP BY cd.id, cd.name
                ORDER BY model_count DESC
            ");
            $stmt->execute(['uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = 0;
            foreach ($rows as $r) {
                $total += (int) $r['model_count'];
            }

            $conditions = [];
            foreach ($rows as $row) {
                $count = (int) $row['model_count'];
                $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
                $conditions[] = [
                    'condition_id'   => $row['condition_id'] ? (int) $row['condition_id'] : null,
                    'condition_name' => $row['condition_name'],
                    'model_count'    => $count,
                    'percentage'     => $pct,
                ];
            }

            return $conditions;
        } catch (Throwable $e) {
            error_log("DashboardRepository::getConditionDistribution error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve color spectrum for user's collection
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getColorDistribution(int $userId, int $limit = 8): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id AS color_id,
                    COALESCE(c.name, 'Unspecified') AS color_name,
                    c.hex_code,
                    COUNT(m.id) AS model_count
                FROM `models` m
                LEFT JOIN `colors` c ON c.id = m.color_id
                WHERE m.user_id = :uid
                GROUP BY c.id, c.name, c.hex_code
                ORDER BY model_count DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("DashboardRepository::getColorDistribution error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve recent additions to user's collection
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getRecentAdditions(int $userId, int $limit = 4): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.model,
                    m.year,
                    m.model_year,
                    m.image_path,
                    b.id AS brand_id,
                    b.name AS brand_name,
                    b.logo_path AS brand_logo_path,
                    mk.id AS maker_id,
                    mk.name AS maker_name,
                    s.scale AS scale_name,
                    cd.name AS condition_name,
                    c.name AS color_name,
                    c.hex_code AS color_hex,
                    m.created,
                    m.modified
                FROM `models` m
                LEFT JOIN `brands` b ON b.id = m.brand_id
                LEFT JOIN `makers` mk ON mk.id = m.maker_id
                LEFT JOIN `scales` s ON s.id = m.scale_id
                LEFT JOIN `conditions` cd ON cd.id = m.condition_id
                LEFT JOIN `colors` c ON c.id = m.color_id
                WHERE m.user_id = :uid
                ORDER BY m.id DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("DashboardRepository::getRecentAdditions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve custom collection summary sets
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getCustomCollectionsSummary(int $userId, int $limit = 4): array {
        $pdo = Database::getConnection();
        if (!$pdo || $userId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.description,
                    c.logo_path,
                    c.modified,
                    COUNT(ci.model_id) AS model_count
                FROM `collections` c
                LEFT JOIN `collection_items` ci ON ci.collection_id = c.id
                WHERE c.user_id = :uid
                GROUP BY c.id, c.name, c.description, c.logo_path, c.modified
                ORDER BY model_count DESC, c.name ASC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("DashboardRepository::getCustomCollectionsSummary error: " . $e->getMessage());
            return [];
        }
    }
}
