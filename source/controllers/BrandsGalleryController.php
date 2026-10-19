<?php
/**
 * Brands Gallery Controller
 * 
 * Manages the user's Brands Gallery showcase:
 * - Filter section (Brand Search)
 * - Sticky Alphabet selector (All, #, A-Z) with live item counts and greyed-out state
 * - List mode: Cards grid with instant search & filter, asc/desc sort toggle
 * - Detail mode: Editorial showcase with brand logo, title, models count link
 *                (navigates to Garage filtered by current brand), and Markdown description.
 */

require_once dirname(__DIR__) . '/repositories/BrandsGalleryRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/services/MarkdownService.php';

class BrandsGalleryController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'brands_gallery')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Brands gallery.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $action = $_GET['action'] ?? null;

        // AJAX endpoint for single brand details (if needed dynamically)
        if ($action === 'get_brand_ajax') {
            header('Content-Type: application/json');
            try {
                $id = (int)($_GET['id'] ?? 0);
                $brand = BrandsGalleryRepository::getBrandById($id, $currentUserId);
                if (!$brand) {
                    throw new Exception("Brand not found.");
                }
                $brand['description_html'] = MarkdownService::parse($brand['description'] ?? '');
                echo json_encode(['success' => true, 'brand' => $brand]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch all brands with models count for the current user
        $allBrands = BrandsGalleryRepository::getAllBrands($currentUserId);

        // Pre-parse markdown description for all brands
        foreach ($allBrands as &$b) {
            $b['description_html'] = MarkdownService::parse($b['description'] ?? '');
        }
        unset($b);

        // Check if a specific brand was requested via URL (e.g. ?id=3 or ?mode=detail&id=3)
        $detailBrandId = (int)($_GET['id'] ?? 0);
        $initialMode = ($detailBrandId > 0) ? 'detail' : 'list';
        $selectedBrand = null;
        if ($detailBrandId > 0) {
            foreach ($allBrands as $b) {
                if ((int)$b['id'] === $detailBrandId) {
                    $selectedBrand = $b;
                    break;
                }
            }
        }

        // Render View
        require dirname(__DIR__) . '/views/brands_gallery.php';
    }
}
