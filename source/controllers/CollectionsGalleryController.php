<?php
/**
 * Collections Gallery Controller
 * 
 * Manages the user's Custom Collections Gallery showcase:
 * - Filter section (Collection Search)
 * - Sticky Alphabet selector (All, #, A-Z) with live item counts and greyed-out state
 * - List mode: Cards grid with instant search & filter, asc/desc sort toggle
 * - Detail mode: Editorial showcase with collection cover/logo, title, models count link
 *                (navigates to Garage filtered by current collection), and Markdown description.
 */

require_once dirname(__DIR__) . '/repositories/CollectionsGalleryRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/services/MarkdownService.php';

class CollectionsGalleryController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'collections_gallery')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Collections gallery.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $action = $_GET['action'] ?? null;

        // AJAX endpoint for single collection details (if needed dynamically)
        if ($action === 'get_collection_ajax') {
            header('Content-Type: application/json');
            try {
                $id = (int)($_GET['id'] ?? 0);
                $collection = CollectionsGalleryRepository::getCollectionById($id, $currentUserId);
                if (!$collection) {
                    throw new Exception("Collection not found.");
                }
                $collection['description_html'] = MarkdownService::parse($collection['description'] ?? '');
                echo json_encode(['success' => true, 'collection' => $collection]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch all collections with models count for the current user
        $allCollections = CollectionsGalleryRepository::getAllCollections($currentUserId);

        // Pre-parse markdown description for all collections
        foreach ($allCollections as &$c) {
            $c['description_html'] = MarkdownService::parse($c['description'] ?? '');
        }
        unset($c);

        // Check if a specific collection was requested via URL (e.g. ?id=3 or ?mode=detail&id=3)
        $detailCollectionId = (int)($_GET['id'] ?? 0);
        $initialMode = ($detailCollectionId > 0) ? 'detail' : 'list';
        $selectedCollection = null;
        if ($detailCollectionId > 0) {
            foreach ($allCollections as $c) {
                if ((int)$c['id'] === $detailCollectionId) {
                    $selectedCollection = $c;
                    break;
                }
            }
        }

        // Render View
        require dirname(__DIR__) . '/views/collections_gallery.php';
    }
}
