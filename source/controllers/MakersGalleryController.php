<?php
/**
 * Makers Gallery Controller
 * 
 * Manages the user's Makers Gallery showcase:
 * - Filter section (Maker Search)
 * - Sticky Alphabet selector (All, #, A-Z) with live item counts and greyed-out state
 * - List mode: Cards grid with instant search & filter, asc/desc sort toggle
 * - Detail mode: Editorial showcase with maker logo, title, models count link
 *                (navigates to Garage filtered by current maker), and Markdown description.
 */

require_once dirname(__DIR__) . '/repositories/MakersGalleryRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/services/MarkdownService.php';

class MakersGalleryController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'makers_gallery')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Makers gallery.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $action = $_GET['action'] ?? null;

        // AJAX endpoint for single maker details (if needed dynamically)
        if ($action === 'get_maker_ajax') {
            header('Content-Type: application/json');
            try {
                $id = (int)($_GET['id'] ?? 0);
                $maker = MakersGalleryRepository::getMakerById($id, $currentUserId);
                if (!$maker) {
                    throw new Exception("Maker not found.");
                }
                $maker['description_html'] = MarkdownService::parse($maker['description'] ?? '');
                echo json_encode(['success' => true, 'maker' => $maker]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch all makers with models count for the current user
        $allMakers = MakersGalleryRepository::getAllMakers($currentUserId);

        // Pre-parse markdown description for all makers
        foreach ($allMakers as &$m) {
            $m['description_html'] = MarkdownService::parse($m['description'] ?? '');
        }
        unset($m);

        // Check if a specific maker was requested via URL (e.g. ?id=3 or ?mode=detail&id=3)
        $detailMakerId = (int)($_GET['id'] ?? 0);
        $initialMode = ($detailMakerId > 0) ? 'detail' : 'list';
        $selectedMaker = null;
        if ($detailMakerId > 0) {
            foreach ($allMakers as $m) {
                if ((int)$m['id'] === $detailMakerId) {
                    $selectedMaker = $m;
                    break;
                }
            }
        }

        // Render View
        require dirname(__DIR__) . '/views/makers_gallery.php';
    }
}
