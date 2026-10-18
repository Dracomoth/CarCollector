<?php
/**
 * Makers Controller
 * 
 * Manages model car manufacturer / maker catalog data:
 * - Maker details: id, name, description, logo_path, created, modified
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Logo handling:
 *     On create: saves image to images/makers/<id>_logo.<ext>
 *     On edit: deletes old image from logo_path and saves new image as <id>_logo.<ext>
 *     On delete: removes image from logo_path
 * - Enforces screen access and write permissions
 */

require_once dirname(__DIR__) . '/repositories/MakerRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class MakersController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'makers')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Makers screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedMaker = null;
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/images/makers';

        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // ---------------------------------------------------------------------
        // 3. Handle POST Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                // Enforce write permission for modifications
                if (!PermissionService::canWrite($currentUser, 'makers')) {
                    throw new Exception("Permission denied: You have read-only access to the Makers catalog.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int) ($_POST['id'] ?? 0);
                        $name = trim($_POST['name'] ?? '');
                        $description = trim($_POST['description'] ?? '');
                        $removeLogo = !empty($_POST['remove_logo']);

                        if ($name === '') {
                            throw new Exception("Maker name is required.");
                        }

                        // Check name uniqueness
                        if (MakerRepository::nameExists($name, $id > 0 ? $id : null)) {
                            throw new Exception("A maker named '{$name}' already exists.");
                        }

                        // Determine if a new file was uploaded
                        $hasUploadedLogo = isset($_FILES['logo']) 
                            && $_FILES['logo']['error'] === UPLOAD_ERR_OK 
                            && is_uploaded_file($_FILES['logo']['tmp_name']);

                        if ($hasUploadedLogo) {
                            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                            if (!in_array($ext, $allowedExts, true)) {
                                throw new Exception("Invalid logo file format ({$ext}). Allowed: " . implode(', ', $allowedExts));
                            }

                            if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                                throw new Exception("Uploaded logo exceeds maximum allowed size (5 MB).");
                            }
                        }

                        if ($id > 0) {
                            // -------------------------------------------------
                            // UPDATE EXISTING MAKER
                            // -------------------------------------------------
                            $existing = MakerRepository::findById($id);
                            if (!$existing) {
                                throw new Exception("Maker not found for update.");
                            }

                            $logoPath = $existing['logo_path'];

                            if ($hasUploadedLogo) {
                                // Delete old logo from disk if it exists
                                if (!empty($existing['logo_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['logo_path']);
                                }

                                // Save new logo with format <id>_logo.<ext>
                                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                                $filename = "{$id}_logo.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                                    throw new Exception("Failed to save uploaded logo to {$targetPath}.");
                                }
                                $logoPath = "/images/makers/{$filename}";
                            } elseif ($removeLogo) {
                                if (!empty($existing['logo_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['logo_path']);
                                }
                                $logoPath = null;
                            }

                            MakerRepository::update($id, [
                                'name'        => $name,
                                'description' => $description !== '' ? $description : null,
                                'logo_path'   => $logoPath,
                            ]);

                            $_SESSION['flash_success'] = "Maker '{$name}' updated successfully.";
                            header("Location: /makers?edit={$id}", true, 302);
                            exit;

                        } else {
                            // -------------------------------------------------
                            // CREATE NEW MAKER
                            // -------------------------------------------------
                            // Insert first to obtain the auto-generated ID
                            $newId = MakerRepository::create([
                                'name'        => $name,
                                'description' => $description !== '' ? $description : null,
                                'logo_path'   => null,
                            ]);

                            if ($hasUploadedLogo) {
                                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                                $filename = "{$newId}_logo.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                                    $logoPath = "/images/makers/{$filename}";
                                    MakerRepository::updateLogoPath($newId, $logoPath);
                                }
                            }

                            $_SESSION['flash_success'] = "Maker '{$name}' created successfully.";
                            header("Location: /makers?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int) ($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid maker ID specified for deletion.");
                        }

                        $maker = MakerRepository::findById($id);
                        if (!$maker) {
                            throw new Exception("Maker not found.");
                        }

                        // On delete: delete whatever is indicated on logo_path
                        if (!empty($maker['logo_path'])) {
                            self::deleteDiskFile($projectRoot, $maker['logo_path']);
                        }

                        MakerRepository::delete($id);

                        $_SESSION['flash_success'] = "Maker '{$maker['name']}' deleted successfully.";
                        header("Location: /makers", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                // If update failed, retain selected maker
                if (!empty($_POST['id'])) {
                    $selectedMaker = MakerRepository::findById((int) $_POST['id']);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            if ($editId > 0) {
                $selectedMaker = MakerRepository::findById($editId);
                if (!$selectedMaker && empty($error)) {
                    $error = "Requested maker was not found.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : null;
        $allMakers = MakerRepository::getAll($currentUserId);

        $selectedId = !empty($selectedMaker) ? (int)$selectedMaker['id'] : 0;

        $grid = new GridController([
            'id'                => 'makers-grid',
            'action'            => '/makers',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'name',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/makers?edit=' . (int)$row['id'];
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
            'width'      => '75px',
            'formatter'  => function($val) {
                return '<span class="text-muted text-sm">#' . (int)$val . '</span>';
            },
        ]);

        // Name column (only column with filter)
        $grid->addColumn('name', 'Name', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                $safeName = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                return '<span class="maker-grid-name brand-grid-name"><strong>' . $safeName . '</strong></span>';
            },
        ]);

        // Models count column (in badges format)
        $grid->addColumn('models_count', 'Models', [
            'sortable'   => true,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '95px',
            'formatter'  => function($val) {
                $count = (int)$val;
                $badgeClass = $count > 0 ? 'badge-primary' : 'badge-secondary';
                return '<span class="badge ' . $badgeClass . '" style="min-width: 28px; font-weight: 700;">' . $count . '</span>';
            },
        ]);

        $grid->setData($allMakers);
        $grid->process();

        // 6. Render View
        require dirname(__DIR__) . '/views/makers.php';
    }

    /**
     * Helper to safely remove file on disk based on relative or absolute path
     */
    protected static function deleteDiskFile(string $projectRoot, string $logoPath): void {
        $cleanPath = ltrim($logoPath, '/\\');
        $fullPath = $projectRoot . '/' . $cleanPath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
