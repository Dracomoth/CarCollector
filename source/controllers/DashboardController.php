<?php
/**
 * Dashboard Controller
 * 
 * Handles requests to /dashboard, /dashboard.php, and /
 * Loads collection statistics, brand percentages, scale ratios,
 * and recent models for display on the protected collection dashboard.
 */

require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/repositories/DashboardRepository.php';

class DashboardController {
    /**
     * Handle incoming dashboard request
     */
    public static function handle(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header('Location: /login', true, 302);
            exit;
        }

        // Screen permission check
        if (!PermissionService::canAccess($user, 'dashboard')) {
            http_response_code(403);
            require_once dirname(__DIR__) . '/views/layout/header.php';
            echo '<main class="container page-content"><div class="card"><h1>403 Forbidden</h1><p>You do not have permission to access the Dashboard.</p></div></main>';
            require_once dirname(__DIR__) . '/views/layout/footer.php';
            exit;
        }

        $userId = (int) $user['id'];

        // Retrieve telemetry data
        $summaryStats = DashboardRepository::getSummaryStats($userId);
        $brandDistribution = DashboardRepository::getBrandDistribution($userId);
        $scaleDistribution = DashboardRepository::getScaleDistribution($userId);
        $conditionDistribution = DashboardRepository::getConditionDistribution($userId);
        $colorDistribution = DashboardRepository::getColorDistribution($userId, 10);
        $recentAdditions = DashboardRepository::getRecentAdditions($userId, 4);
        $collectionsSummary = DashboardRepository::getCustomCollectionsSummary($userId, 4);

        // Permissions for fast navigation shortcuts
        $canAccessGarage = PermissionService::canAccess($user, 'garage');
        $canAccessBrandsGallery = PermissionService::canAccess($user, 'brands_gallery');
        $canAccessMakersGallery = PermissionService::canAccess($user, 'makers_gallery');
        $canAccessCollectionsGallery = PermissionService::canAccess($user, 'collections_gallery');
        $canAccessModels = PermissionService::canAccess($user, 'models');
        $canAccessCollections = PermissionService::canAccess($user, 'collections');
        $canAccessBrands = PermissionService::canAccess($user, 'brands');
        $canAccessMakers = PermissionService::canAccess($user, 'makers');
        $canAccessScales = PermissionService::canAccess($user, 'scales');
        $canAccessConditions = PermissionService::canAccess($user, 'conditions');
        $canAccessColors = PermissionService::canAccess($user, 'colors');
        $canAccessUsers = PermissionService::canAccess($user, 'users');
        $canAccessRoles = PermissionService::canAccess($user, 'roles');

        $pageTitle = 'Dashboard - CarCollector';
        $currentUser = $user;

        require dirname(__DIR__) . '/views/dashboard.php';
    }
}
