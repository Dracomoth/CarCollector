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
            $stmt = $pdo->query("SELECT `id`, `screen_key`, `name`, `category`, `sort_order`, `grouped` FROM `screens` ORDER BY `sort_order` ASC, `id` ASC");
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
            $stmt = $pdo->prepare("SELECT `id`, `screen_key`, `name`, `category`, `sort_order`, `grouped` FROM `screens` WHERE `screen_key` = :key LIMIT 1");
            $stmt->execute(['key' => $screenKey]);
            $screen = $stmt->fetch(PDO::FETCH_ASSOC);
            return $screen ?: null;
        } catch (Throwable $e) {
            error_log("Failed to find screen by key {$screenKey}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve all screens accessible to a user on the current device (PC or other)
     * respecting sort_order and role permissions (read or write access required).
     * SuperAdmins have access to all screens.
     * 
     * @param array $user
     * @return array
     */
    public static function getAccessibleScreens(array $user): array {
        $screens = self::getAll();
        if (empty($screens)) {
            return [];
        }

        if (!empty($user['is_super'])) {
            return $screens;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId <= 0) {
            return [];
        }

        require_once __DIR__ . '/RolePermissionRepository.php';
        require_once dirname(__DIR__) . '/services/PermissionService.php';

        $permissions = RolePermissionRepository::getPermissionsByRoleId($roleId);
        $isOther = PermissionService::isOtherDevice();

        $accessible = [];
        foreach ($screens as $screen) {
            $sid = (int) $screen['id'];
            $perm = $permissions[$sid] ?? null;
            if (!$perm) {
                continue;
            }

            $access = $isOther ? ($perm['access_other'] ?? 'none') : ($perm['access_pc'] ?? 'none');
            if ($access === 'read' || $access === 'write') {
                $accessible[] = $screen;
            }
        }

        return $accessible;
    }
}
