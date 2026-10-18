<?php
/**
 * Brands Controller
 * 
 * Manages car brand catalog data:
 * - Brand details: id, name, description, logo_path, created, modified
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Logo handling:
 *     On create: saves image to images/brands/<id>_logo.<ext>
 *     On edit: deletes old image from logo_path and saves new image as <id>_logo.<ext>
 *     On delete: removes image from logo_path
 * - Enforces screen access and write permissions
 */

require_once dirname(__DIR__) . '/repositories/BrandRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class BrandsController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'brands')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Brands screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedBrand = null;
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/images/brands';

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
                if (!PermissionService::canWrite($currentUser, 'brands')) {
                    throw new Exception("Permission denied: You have read-only access to the Brands catalog.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int) ($_POST['id'] ?? 0);
                        $name = trim($_POST['name'] ?? '');
                        $description = trim($_POST['description'] ?? '');
                        $removeLogo = !empty($_POST['remove_logo']);

                        if ($name === '') {
                            throw new Exception("Brand name is required.");
                        }

                        // Check name uniqueness
                        if (BrandRepository::nameExists($name, $id > 0 ? $id : null)) {
                            throw new Exception("A brand named '{$name}' already exists.");
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
                            // UPDATE EXISTING BRAND
                            // -------------------------------------------------
                            $existing = BrandRepository::findById($id);
                            if (!$existing) {
                                throw new Exception("Brand not found for update.");
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
                                $logoPath = "/images/brands/{$filename}";
                            } elseif ($removeLogo) {
                                if (!empty($existing['logo_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['logo_path']);
                                }
                                $logoPath = null;
                            }

                            BrandRepository::update($id, [
                                'name'        => $name,
                                'description' => $description !== '' ? $description : null,
                                'logo_path'   => $logoPath,
                            ]);

                            $_SESSION['flash_success'] = "Brand '{$name}' updated successfully.";
                            header("Location: /brands?edit={$id}", true, 302);
                            exit;

                        } else {
                            // -------------------------------------------------
                            // CREATE NEW BRAND
                            // -------------------------------------------------
                            // Insert first to obtain the auto-generated ID
                            $newId = BrandRepository::create([
                                'name'        => $name,
                                'description' => $description !== '' ? $description : null,
                                'logo_path'   => null,
                            ]);

                            if ($hasUploadedLogo) {
                                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                                $filename = "{$newId}_logo.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                                    $logoPath = "/images/brands/{$filename}";
                                    BrandRepository::updateLogoPath($newId, $logoPath);
                                }
                            }

                            $_SESSION['flash_success'] = "Brand '{$name}' created successfully.";
                            header("Location: /brands?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int) ($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid brand ID specified for deletion.");
                        }

                        $brand = BrandRepository::findById($id);
                        if (!$brand) {
                            throw new Exception("Brand not found.");
                        }

                        // On delete: delete whatever is indicated on logo_path
                        if (!empty($brand['logo_path'])) {
                            self::deleteDiskFile($projectRoot, $brand['logo_path']);
                        }

                        BrandRepository::delete($id);

                        $_SESSION['flash_success'] = "Brand '{$brand['name']}' deleted successfully.";
                        header("Location: /brands", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                // If update failed, retain selected brand
                if (!empty($_POST['id'])) {
                    $selectedBrand = BrandRepository::findById((int) $_POST['id']);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            if ($editId > 0) {
                $selectedBrand = BrandRepository::findById($editId);
                if (!$selectedBrand && empty($error)) {
                    $error = "Requested brand was not found.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : null;
        $allBrands = BrandRepository::getAll($currentUserId);

        $selectedId = !empty($selectedBrand) ? (int)$selectedBrand['id'] : 0;

        $grid = new GridController([
            'id'                => 'brands-grid',
            'action'            => '/brands',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'name',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/brands?edit=' . (int)$row['id'];
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
                return '<span class="brand-grid-name"><strong>' . $safeName . '</strong></span>';
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

        $grid->setData($allBrands);
        $grid->process();

        // 6. Render View
        require dirname(__DIR__) . '/views/brands.php';
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
