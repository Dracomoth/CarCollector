<?php
/**
 * Collections Controller
 * 
 * Manages user-specific custom collections:
 * - Collection details: id, user_id, name, description, logo_path, created, modified
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Logo handling:
 *     File naming: <id>_<user_id>_logo.<ext>
 *     On create: inserts record, saves file to images/collections/<id>_<user_id>_logo.<ext>, updates logo_path
 *     On edit: cleans up old file and saves new image
 *     On delete: removes logo file from disk
 * - Collection Models Section & Popup:
 *     Lists models in the collection with small cards and delete button
 *     "Add Model" popup with paginated models list, filters (name, brand, maker), and Add/Cancel actions
 * - Enforces screen access and write permissions
 */

require_once dirname(__DIR__) . '/repositories/CollectionRepository.php';
require_once dirname(__DIR__) . '/repositories/ModelRepository.php';
require_once dirname(__DIR__) . '/repositories/BrandRepository.php';
require_once dirname(__DIR__) . '/repositories/MakerRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once dirname(__DIR__) . '/services/ImageHelper.php';
require_once __DIR__ . '/GridController.php';

class CollectionsController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'collections')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Collections screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedCollection = null;
        $collectionModels = [];
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/images/collections';

        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // ---------------------------------------------------------------------
        // 3. Handle AJAX Endpoints (Available Models Filter, Add & Remove)
        // ---------------------------------------------------------------------
        if ($action === 'get_available_models_ajax') {
            header('Content-Type: application/json');
            try {
                $collectionId = (int)($_GET['collection_id'] ?? 0);
                $searchTerm = trim($_GET['search'] ?? ($_GET['name'] ?? ''));
                $filters = [
                    'search'   => $searchTerm,
                    'name'     => $searchTerm,
                    'brand_id' => !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null,
                    'maker_id' => !empty($_GET['maker_id']) ? (int)$_GET['maker_id'] : null,
                ];

                $allAvailable = CollectionRepository::getAvailableModels($collectionId, $currentUserId, $filters);
                
                // Pagination for popup cards (12 per page)
                $perPage = 12;
                $page = max(1, (int)($_GET['page'] ?? 1));
                $totalItems = count($allAvailable);
                $totalPages = max(1, (int)ceil($totalItems / $perPage));
                if ($page > $totalPages) {
                    $page = $totalPages;
                }
                $offset = ($page - 1) * $perPage;
                $pagedItems = array_slice($allAvailable, $offset, $perPage);

                echo json_encode([
                    'success'      => true,
                    'items'        => $pagedItems,
                    'total_items'  => $totalItems,
                    'total_pages'  => $totalPages,
                    'current_page' => $page,
                ]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        if ($requestMethod === 'POST' && ($action === 'add_models_ajax' || $action === 'add_models')) {
            $isAjax = ($action === 'add_models_ajax') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            try {
                if (!PermissionService::canWrite($currentUser, 'collections')) {
                    throw new Exception("Permission denied: You have read-only access to Collections.");
                }

                $collectionId = (int)($_POST['collection_id'] ?? 0);
                if ($collectionId <= 0) {
                    throw new Exception("Please save the collection before adding models.");
                }

                $modelIds = $_POST['model_ids'] ?? [];
                if (!is_array($modelIds)) {
                    $modelIds = [$modelIds];
                }
                $modelIds = array_map('intval', array_filter($modelIds));

                if (empty($modelIds)) {
                    throw new Exception("No models were selected to add.");
                }

                $addedCount = CollectionRepository::addModelsToCollection($collectionId, $modelIds, $currentUserId);
                $updatedModels = CollectionRepository::getModelsInCollection($collectionId, $currentUserId);

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'added'   => $addedCount,
                        'message' => "Added {$addedCount} model(s) to collection.",
                        'models'  => $updatedModels,
                    ]);
                    exit;
                }

                $_SESSION['flash_success'] = "Added {$addedCount} model(s) to collection.";
                header("Location: /collections?edit={$collectionId}", true, 302);
                exit;
            } catch (Throwable $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                $error = $e->getMessage();
            }
        }

        if ($requestMethod === 'POST' && ($action === 'remove_model_ajax' || $action === 'remove_model')) {
            $isAjax = ($action === 'remove_model_ajax') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            try {
                if (!PermissionService::canWrite($currentUser, 'collections')) {
                    throw new Exception("Permission denied: You have read-only access to Collections.");
                }

                $collectionId = (int)($_POST['collection_id'] ?? 0);
                $modelId = (int)($_POST['model_id'] ?? 0);

                if ($collectionId <= 0 || $modelId <= 0) {
                    throw new Exception("Invalid collection or model ID.");
                }

                CollectionRepository::removeModelFromCollection($collectionId, $modelId, $currentUserId);
                $updatedModels = CollectionRepository::getModelsInCollection($collectionId, $currentUserId);

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Model removed from collection.',
                        'models'  => $updatedModels,
                    ]);
                    exit;
                }

                $_SESSION['flash_success'] = "Model removed from collection.";
                header("Location: /collections?edit={$collectionId}", true, 302);
                exit;
            } catch (Throwable $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                $error = $e->getMessage();
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle Standard Form Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                if (!PermissionService::canWrite($currentUser, 'collections')) {
                    throw new Exception("Permission denied: You have read-only access to Collections.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int)($_POST['id'] ?? 0);
                        $name = trim($_POST['name'] ?? '');
                        $description = trim($_POST['description'] ?? '');
                        $removeLogo = !empty($_POST['remove_logo']);

                        if ($name === '') {
                            throw new Exception("Collection name is required.");
                        }

                        if (CollectionRepository::nameExists($name, $currentUserId, $id > 0 ? $id : null)) {
                            throw new Exception("A collection named '{$name}' already exists in your collection.");
                        }

                        // Edit / Update existing collection
                        if ($id > 0) {
                            $existing = CollectionRepository::findById($id, $currentUserId);
                            if (!$existing) {
                                throw new Exception("Collection not found or access denied.");
                            }

                            $logoPath = $existing['logo_path'];

                            // Check logo removal
                            if ($removeLogo && !empty($existing['logo_path'])) {
                                self::deleteDiskFile($projectRoot, $existing['logo_path']);
                                $logoPath = null;
                            }

                            // Handle logo upload: format <id>_<user_id>_logo.<ext>
                            if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                                if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true)) {
                                    throw new Exception("Unsupported logo format. Allowed: PNG, JPG, WEBP, SVG, GIF.");
                                }

                                if ($_FILES['logo']['size'] > 10 * 1024 * 1024) {
                                    throw new Exception("Logo exceeds maximum allowed size (10 MB).");
                                }

                                if (!empty($existing['logo_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['logo_path']);
                                }

                                $filename = "{$id}_{$currentUserId}_logo.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                                    throw new Exception("Failed to upload logo image.");
                                }
                                $logoPath = "/images/collections/{$filename}";
                            }

                            CollectionRepository::update($id, [
                                'name'        => $name,
                                'description' => $description,
                                'logo_path'   => $logoPath,
                            ], $currentUserId);

                            $_SESSION['flash_success'] = "Collection '{$name}' updated successfully.";
                            header("Location: /collections?edit={$id}", true, 302);
                            exit;
                        } else {
                            // Create new collection
                            $newId = CollectionRepository::create([
                                'user_id'     => $currentUserId,
                                'name'        => $name,
                                'description' => $description,
                                'logo_path'   => null,
                            ]);

                            // Handle logo upload for new collection
                            if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true) && $_FILES['logo']['size'] <= 10 * 1024 * 1024) {
                                    $filename = "{$newId}_{$currentUserId}_logo.{$ext}";
                                    $targetPath = $uploadDir . '/' . $filename;
                                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                                        $logoPath = "/images/collections/{$filename}";
                                        CollectionRepository::updateLogoPath($newId, $logoPath, $currentUserId);
                                    }
                                }
                            }

                            $_SESSION['flash_success'] = "Collection '{$name}' created successfully.";
                            header("Location: /collections?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid collection ID specified.");
                        }

                        $collection = CollectionRepository::findById($id, $currentUserId);
                        if (!$collection) {
                            throw new Exception("Collection not found or access denied.");
                        }

                        if (!empty($collection['logo_path'])) {
                            self::deleteDiskFile($projectRoot, $collection['logo_path']);
                        }

                        CollectionRepository::delete($id, $currentUserId);

                        $_SESSION['flash_success'] = "Collection '{$collection['name']}' deleted successfully.";
                        header("Location: /collections", true, 302);
                        exit;
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
                if (!empty($_POST['id'])) {
                    $selectedCollection = CollectionRepository::findById((int)$_POST['id'], $currentUserId);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int)$_GET['edit'];
            if ($editId > 0) {
                $selectedCollection = CollectionRepository::findById($editId, $currentUserId);
                if (!$selectedCollection && empty($error)) {
                    $error = "Requested collection was not found or access denied.";
                }
            }
        }

        // Load models in the currently selected collection
        if ($selectedCollection) {
            $collectionModels = CollectionRepository::getModelsInCollection((int)$selectedCollection['id'], $currentUserId);
        }

        // Load brands and makers for the popup filters
        $allBrands = BrandRepository::getAll($currentUserId);
        $allMakers = MakerRepository::getAll($currentUserId);

        // ---------------------------------------------------------------------
        // 6. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $allCollections = CollectionRepository::getAllByUser($currentUserId);
        $selectedId = !empty($selectedCollection) ? (int)$selectedCollection['id'] : 0;

        $grid = new GridController([
            'id'                => 'collections-grid',
            'action'            => '/collections',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'name',
            'default_order'     => 'asc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/collections?edit=' . (int)$row['id'];
            },
            'row_class'         => function($row) use ($selectedId) {
                return ((int)$row['id'] === $selectedId) ? 'is-selected' : '';
            },
        ]);

        // Logo Column
        $grid->addColumn('logo_path', 'Logo', [
            'sortable'   => false,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '64px',
            'formatter'  => function($val, $row) {
                if (!empty($val)) {
                    $versionedUrl = ImageHelper::versioned($val, $row['modified'] ?? null);
                    return '<div class="table-logo-cell"><img src="' . htmlspecialchars($versionedUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') . '" class="table-thumb-logo" loading="lazy"></div>';
                }
                $initial = strtoupper(substr($row['name'] ?? 'C', 0, 1));
                return '<div class="table-logo-cell"><div class="table-logo-fallback" title="No logo">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</div></div>';
            },
        ]);

        // Name Column
        $grid->addColumn('name', 'Collection Name', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                return '<strong class="text-contrast">' . htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8') . '</strong>';
            },
        ]);

        // Models Count Column
        $grid->addColumn('model_count', 'Models', [
            'sortable'   => true,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '115px',
            'cell_class' => 'text-nowrap',
            'formatter'  => function($val) {
                $count = (int)$val;
                return '<span class="badge badge-primary text-xs" style="white-space: nowrap; display: inline-block;">' . $count . ' ' . ($count === 1 ? 'model' : 'models') . '</span>';
            },
        ]);

        $grid->setData($allCollections);
        $grid->process();

        $canWrite = PermissionService::canWrite($currentUser, 'collections');

        require dirname(__DIR__) . '/views/collections.php';
    }

    /**
     * Safely delete a file from disk.
     */
    private static function deleteDiskFile(string $projectRoot, ?string $relativePath): void {
        if (empty($relativePath)) {
            return;
        }
        $fullPath = $projectRoot . '/' . ltrim($relativePath, '/\\');
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
