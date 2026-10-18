<?php
/**
 * Conditions Controller
 * 
 * Manages model car conditions metadata:
 * - Condition details: id, condition name (e.g. Mint in Box, Mint Loose, Junkyard)
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Filter by condition in toolbar directly above column headers
 * - Instant client-side AJAX row selection without full page reload
 * - Enforces screen access and write permissions for the 'conditions' screen
 */

require_once dirname(__DIR__) . '/repositories/ConditionRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class ConditionsController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'conditions')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Conditions screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedCondition = null;

        // ---------------------------------------------------------------------
        // 3. Handle POST Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                // Enforce write permission for modifications
                if (!PermissionService::canWrite($currentUser, 'conditions')) {
                    throw new Exception("Permission denied: You have read-only access to the Conditions catalog.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int) ($_POST['id'] ?? 0);
                        $conditionName = trim($_POST['name'] ?? '');

                        if ($conditionName === '') {
                            throw new Exception("Condition text is required (e.g. Mint in Box (MIB), Junkyard).");
                        }

                        if ($id > 0) {
                            // Update existing condition
                            ConditionRepository::update($id, $conditionName);
                            $_SESSION['flash_success'] = "Condition '{$conditionName}' updated successfully.";
                            header("Location: /conditions?edit={$id}", true, 302);
                            exit;
                        } else {
                            // Create new condition
                            $newId = ConditionRepository::create($conditionName);
                            $_SESSION['flash_success'] = "Condition '{$conditionName}' created successfully.";
                            header("Location: /conditions?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int) ($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid condition ID specified for deletion.");
                        }

                        $condRecord = ConditionRepository::findById($id);
                        if (!$condRecord) {
                            throw new Exception("Condition not found.");
                        }

                        ConditionRepository::delete($id);

                        $_SESSION['flash_success'] = "Condition '{$condRecord['name']}' deleted successfully.";
                        header("Location: /conditions", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                if (!empty($_POST['id'])) {
                    $selectedCondition = ConditionRepository::findById((int) $_POST['id']);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            if ($editId > 0) {
                $selectedCondition = ConditionRepository::findById($editId);
                if (!$selectedCondition && empty($error)) {
                    $error = "Requested condition was not found.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $allConditions = ConditionRepository::getAll();
        $selectedId = !empty($selectedCondition) ? (int)$selectedCondition['id'] : 0;

        $grid = new GridController([
            'id'                => 'conditions-grid',
            'action'            => '/conditions',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'id',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/conditions?edit=' . (int)$row['id'];
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

        // Condition Name column (with filter)
        $grid->addColumn('name', 'Condition', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                $safeName = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                return '<span class="condition-grid-title"><strong style="font-size: 0.95rem; color: #ffffff;">' . $safeName . '</strong></span>';
            },
        ]);

        $grid->setData($allConditions);
        $grid->process();

        // 6. Render View
        require dirname(__DIR__) . '/views/conditions.php';
    }
}
