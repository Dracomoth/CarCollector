<?php
/**
 * Colors Controller
 * 
 * Manages model car colors metadata:
 * - Color details: id, name, hex code (e.g. #FFFFFF White, #FF0000 Red)
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Interactive color picker popup button + direct hex code textbox with live sync
 * - Filter by color in toolbar directly above column headers
 * - Instant client-side AJAX row selection without full page reload
 * - Enforces screen access and write permissions for the 'colors' screen
 */

require_once dirname(__DIR__) . '/repositories/ColorRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class ColorsController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'colors')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Colors screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedColor = null;

        // ---------------------------------------------------------------------
        // 3. Handle POST Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                // Enforce write permission for modifications
                if (!PermissionService::canWrite($currentUser, 'colors')) {
                    throw new Exception("Permission denied: You have read-only access to the Colors catalog.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int) ($_POST['id'] ?? 0);
                        $name = trim($_POST['name'] ?? '');
                        $hexCode = trim($_POST['hex_code'] ?? '');

                        if ($name === '') {
                            throw new Exception("Color name is required (e.g. Red, Candy Apple, Midnight Blue).");
                        }
                        if ($hexCode === '') {
                            throw new Exception("Hex code is required (e.g. #FF0000 or FF0000).");
                        }

                        $normalizedHex = ColorRepository::normalizeHex($hexCode);

                        if ($id > 0) {
                            // Update existing color
                            ColorRepository::update($id, $name, $normalizedHex);
                            $_SESSION['flash_success'] = "Color '{$name}' updated successfully.";
                            header("Location: /colors?edit={$id}", true, 302);
                            exit;
                        } else {
                            // Create new color
                            $newId = ColorRepository::create($name, $normalizedHex);
                            $_SESSION['flash_success'] = "Color '{$name}' created successfully.";
                            header("Location: /colors?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int) ($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid color ID specified for deletion.");
                        }

                        $colorRecord = ColorRepository::findById($id);
                        if (!$colorRecord) {
                            throw new Exception("Color not found.");
                        }

                        ColorRepository::delete($id);

                        $_SESSION['flash_success'] = "Color '{$colorRecord['name']}' deleted successfully.";
                        header("Location: /colors", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                if (!empty($_POST['id'])) {
                    $selectedColor = ColorRepository::findById((int) $_POST['id']);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            if ($editId > 0) {
                $selectedColor = ColorRepository::findById($editId);
                if (!$selectedColor && empty($error)) {
                    $error = "Requested color was not found.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $allColors = ColorRepository::getAll();
        $selectedId = !empty($selectedColor) ? (int)$selectedColor['id'] : 0;

        $grid = new GridController([
            'id'                => 'colors-grid',
            'action'            => '/colors',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'id',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/colors?edit=' . (int)$row['id'];
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

        // Color Swatch + Name column
        $grid->addColumn('name', 'Color Name', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val, $row) {
                $safeName = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                $hex = htmlspecialchars((string)($row['hex_code'] ?? '#CCCCCC'), ENT_QUOTES, 'UTF-8');
                return '<div style="display: flex; align-items: center; gap: 0.65rem;">' .
                       '<span style="display: inline-block; width: 22px; height: 22px; border-radius: 5px; background-color: ' . $hex . '; border: 1px solid rgba(255, 255, 255, 0.25); box-shadow: 0 1px 3px rgba(0,0,0,0.4); flex-shrink: 0;"></span>' .
                       '<strong style="font-size: 0.95rem; color: #ffffff;">' . $safeName . '</strong>' .
                       '</div>';
            },
        ]);

        // Hex Code column
        $grid->addColumn('hex_code', 'Hex Code', [
            'sortable'   => true,
            'filterable' => true,
            'width'      => '150px',
            'formatter'  => function($val) {
                $hex = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                return '<code style="font-family: var(--font-mono, monospace); font-size: 0.88rem; background: rgba(0,0,0,0.35); padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1); color: var(--color-primary, #e10600); font-weight: 600;">' . $hex . '</code>';
            },
        ]);

        $grid->setData($allColors);
        $grid->process();

        // 6. Render View
        require dirname(__DIR__) . '/views/colors.php';
    }
}
