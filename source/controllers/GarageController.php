<?php
/**
 * Garage Controller
 * 
 * Manages the user's scale car showcase gallery:
 * - Filter section (Model, Brand, Maker, Collection, Scale)
 * - Sticky Alphabet selector (All, #, A-Z) with live item counts and greyed-out state
 * - List mode: Cards grid with instant search & filter, asc/desc sort toggle
 * - Detail mode: High-octane showcase with condition/scale badges, brand & maker logos,
 *                color swatch, specs panel, and Markdown-formatted notes & description.
 */

require_once dirname(__DIR__) . '/repositories/GarageRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/services/MarkdownService.php';

class GarageController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'garage')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Garage gallery.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $action = $_GET['action'] ?? null;

        // AJAX endpoint for single model details (if requested dynamically)
        if ($action === 'get_model_ajax') {
            header('Content-Type: application/json');
            try {
                $id = (int)($_GET['id'] ?? 0);
                $model = GarageRepository::getModelById($id, $currentUserId);
                if (!$model) {
                    throw new Exception("Model not found or access denied.");
                }
                $model['description_html'] = MarkdownService::parse($model['description'] ?? '');
                echo json_encode(['success' => true, 'model' => $model]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch filter options and all models for the logged-in user
        $filterOptions = GarageRepository::getFilterOptions($currentUserId);
        $allModels = GarageRepository::getAllModelsByUser($currentUserId);

        // Pre-parse markdown description for all models
        foreach ($allModels as &$m) {
            $m['description_html'] = MarkdownService::parse($m['description'] ?? '');
        }
        unset($m);

        // Check if a specific model was requested via URL (e.g. ?id=123 or ?mode=detail&id=123)
        $detailModelId = (int)($_GET['id'] ?? 0);
        $initialMode = ($detailModelId > 0) ? 'detail' : 'list';
        $selectedModel = null;
        if ($detailModelId > 0) {
            foreach ($allModels as $m) {
                if ((int)$m['id'] === $detailModelId) {
                    $selectedModel = $m;
                    break;
                }
            }
        }

        // Render View
        require dirname(__DIR__) . '/views/garage.php';
    }
}
