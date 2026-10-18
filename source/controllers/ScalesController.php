<?php
/**
 * Scales Controller
 * 
 * Manages model car scales metadata:
 * - Scale details: id, scale ratio (e.g. 1:8, 1:18, 1:64)
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Filter by scale in toolbar directly above column headers
 * - Instant client-side AJAX row selection without full page reload
 * - Enforces screen access and write permissions for the 'scales' screen
 */

require_once dirname(__DIR__) . '/repositories/ScaleRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class ScalesController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'scales')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Scales screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedScale = null;

        // ---------------------------------------------------------------------
        // 3. Handle POST Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                // Enforce write permission for modifications
                if (!PermissionService::canWrite($currentUser, 'scales')) {
                    throw new Exception("Permission denied: You have read-only access to the Scales catalog.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int) ($_POST['id'] ?? 0);
                        $scaleRatio = trim($_POST['scale'] ?? '');

                        if ($scaleRatio === '') {
                            throw new Exception("Scale ratio is required (e.g. 1:64, 1:18).");
                        }

                        if ($id > 0) {
                            // Update existing scale
                            ScaleRepository::update($id, $scaleRatio);
                            $_SESSION['flash_success'] = "Scale '{$scaleRatio}' updated successfully.";
                            header("Location: /scales?edit={$id}", true, 302);
                            exit;
                        } else {
                            // Create new scale
                            $newId = ScaleRepository::create($scaleRatio);
                            $_SESSION['flash_success'] = "Scale '{$scaleRatio}' created successfully.";
                            header("Location: /scales?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int) ($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid scale ID specified for deletion.");
                        }

                        $scaleRecord = ScaleRepository::findById($id);
                        if (!$scaleRecord) {
                            throw new Exception("Scale not found.");
                        }

                        ScaleRepository::delete($id);

                        $_SESSION['flash_success'] = "Scale '{$scaleRecord['scale']}' deleted successfully.";
                        header("Location: /scales", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                if (!empty($_POST['id'])) {
                    $selectedScale = ScaleRepository::findById((int) $_POST['id']);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            if ($editId > 0) {
                $selectedScale = ScaleRepository::findById($editId);
                if (!$selectedScale && empty($error)) {
                    $error = "Requested scale was not found.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $allScales = ScaleRepository::getAll();
        $selectedId = !empty($selectedScale) ? (int)$selectedScale['id'] : 0;

        $grid = new GridController([
            'id'                => 'scales-grid',
            'action'            => '/scales',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'id',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/scales?edit=' . (int)$row['id'];
            },
            'row_class'         => function($row) use ($selectedId) {
                return ((int)$row['id'] === $selectedId) ? 'is-selected' : '';
            },
        ]);

        // ID column
        $grid->addColumn('id', 'ID', [
            'sortable'   => true,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '85px',
            'formatter'  => function($val) {
                return '<span class="text-muted text-sm">#' . (int)$val . '</span>';
            },
        ]);

        // Scale column (with filter)
        $grid->addColumn('scale', 'Scale', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                $safeScale = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                return '<span class="scale-grid-ratio"><strong style="font-family: var(--font-heading); font-size: 1.05rem; letter-spacing: 0.05em; color: #ffffff;">' . $safeScale . '</strong></span>';
            },
        ]);

        $grid->setData($allScales);
        $grid->process();

        // 6. Render View
        require dirname(__DIR__) . '/views/scales.php';
    }
}
