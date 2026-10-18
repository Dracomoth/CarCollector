<?php
/**
 * Models Controller
 * 
 * Manages user-specific model car collections:
 * - Model details: id, user_id, brand_id, year, scale_id, series, series_number,
 *                  serial_number, maker_id, model, model_year, color_id, condition_id,
 *                  link, description, image_path, created, modified
 * - Two-column master/detail layout: Form on the left, Data Grid on the right
 * - Image handling:
 *     File naming: <id>_<user_id>_image.<ext>
 *     Mobile camera direct capture & upload support
 *     On edit: replaces image file and cleans up previous file
 *     On delete: removes image from disk
 * - Enforces screen access and write permissions
 */

require_once dirname(__DIR__) . '/repositories/ModelRepository.php';
require_once dirname(__DIR__) . '/repositories/BrandRepository.php';
require_once dirname(__DIR__) . '/repositories/MakerRepository.php';
require_once dirname(__DIR__) . '/repositories/ScaleRepository.php';
require_once dirname(__DIR__) . '/repositories/ColorRepository.php';
require_once dirname(__DIR__) . '/repositories/ConditionRepository.php';
require_once dirname(__DIR__) . '/services/PermissionService.php';
require_once __DIR__ . '/GridController.php';

class ModelsController {
    public static function handle(): void {
        // 1. Enforce authentication
        if (empty($_SESSION['user'])) {
            header('Location: /login', true, 302);
            exit;
        }

        $currentUser = $_SESSION['user'];
        $currentUserId = (int)$currentUser['id'];

        // 2. Enforce screen read access
        if (!PermissionService::canAccess($currentUser, 'models')) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You do not have permission to access the Models screen.</p><p><a href='/'>Return to Dashboard</a></p></body></html>";
            exit;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $selectedModel = null;
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/images/models';

        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // ---------------------------------------------------------------------
        // 3. Handle AJAX Camera Upload Action
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST' && $action === 'upload_camera_ajax') {
            header('Content-Type: application/json');
            try {
                if (!PermissionService::canWrite($currentUser, 'models')) {
                    throw new Exception("Permission denied: Read-only access.");
                }

                $modelId = (int)($_POST['id'] ?? 0);
                if ($modelId <= 0) {
                    throw new Exception("Camera direct upload requires an existing model record.");
                }

                $existing = ModelRepository::findById($modelId, $currentUserId);
                if (!$existing) {
                    throw new Exception("Model record not found or access denied.");
                }

                $fileKey = isset($_FILES['camera_image']) ? 'camera_image' : (isset($_FILES['image']) ? 'image' : null);
                if (!$fileKey || empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("No photo was received from the camera.");
                }

                $file = $_FILES[$fileKey];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext === '' || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $ext = 'jpg'; // Mobile cameras default to JPEG
                }

                if ($file['size'] > 12 * 1024 * 1024) {
                    throw new Exception("Photo exceeds maximum allowed size (12 MB).");
                }

                // Delete old image if exists
                if (!empty($existing['image_path'])) {
                    self::deleteDiskFile($projectRoot, $existing['image_path']);
                }

                $filename = "{$modelId}_{$currentUserId}_image.{$ext}";
                $targetPath = $uploadDir . '/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    throw new Exception("Failed to save captured photo to disk.");
                }

                $imagePath = "/images/models/{$filename}";
                ModelRepository::updateImagePath($modelId, $imagePath, $currentUserId);

                echo json_encode([
                    'success'    => true,
                    'image_path' => $imagePath,
                    'message'    => 'Photo captured and uploaded successfully!'
                ]);
                exit;

            } catch (Throwable $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error'   => $e->getMessage()
                ]);
                exit;
            }
        }

        // ---------------------------------------------------------------------
        // 4. Handle Standard POST Actions (Save & Delete)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'POST') {
            try {
                if (!PermissionService::canWrite($currentUser, 'models')) {
                    throw new Exception("Permission denied: You have read-only access to Models.");
                }

                switch ($action) {
                    case 'save':
                        $id = (int)($_POST['id'] ?? 0);
                        $brandId = (int)($_POST['brand_id'] ?? 0);
                        $model = trim($_POST['model'] ?? '');
                        $scaleId = (int)($_POST['scale_id'] ?? 0);
                        $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
                        $modelYear = !empty($_POST['model_year']) ? (int)$_POST['model_year'] : null;
                        $makerId = !empty($_POST['maker_id']) ? (int)$_POST['maker_id'] : null;
                        $series = trim($_POST['series'] ?? '');
                        $seriesNumber = trim($_POST['series_number'] ?? '');
                        $serialNumber = trim($_POST['serial_number'] ?? '');
                        $colorId = !empty($_POST['color_id']) ? (int)$_POST['color_id'] : null;
                        $conditionId = !empty($_POST['condition_id']) ? (int)$_POST['condition_id'] : null;
                        $link = trim($_POST['link'] ?? '');
                        $description = trim($_POST['description'] ?? '');
                        $removeImage = !empty($_POST['remove_image']);

                        if ($brandId <= 0) {
                            throw new Exception("Please select a car brand.");
                        }
                        if ($model === '') {
                            throw new Exception("Model name is required.");
                        }
                        if ($scaleId <= 0) {
                            throw new Exception("Please select a scale.");
                        }

                        // Determine if a new image was uploaded (via file picker or camera)
                        $fileKey = null;
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['image']['tmp_name'])) {
                            $fileKey = 'image';
                        } elseif (isset($_FILES['camera_image']) && $_FILES['camera_image']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['camera_image']['tmp_name'])) {
                            $fileKey = 'camera_image';
                        }

                        $hasUploadedImage = ($fileKey !== null);

                        if ($hasUploadedImage) {
                            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                            if ($ext === '' || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                                $ext = 'jpg';
                            }
                            if ($_FILES[$fileKey]['size'] > 12 * 1024 * 1024) {
                                throw new Exception("Uploaded image exceeds maximum allowed size (12 MB).");
                            }
                        }

                        if ($id > 0) {
                            // -------------------------------------------------
                            // UPDATE EXISTING MODEL
                            // -------------------------------------------------
                            $existing = ModelRepository::findById($id, $currentUserId);
                            if (!$existing) {
                                throw new Exception("Model not found or access denied.");
                            }

                            $imagePath = $existing['image_path'];

                            if ($hasUploadedImage) {
                                if (!empty($existing['image_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['image_path']);
                                }

                                $filename = "{$id}_{$currentUserId}_image.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                                    throw new Exception("Failed to save uploaded image.");
                                }
                                $imagePath = "/images/models/{$filename}";
                            } elseif ($removeImage) {
                                if (!empty($existing['image_path'])) {
                                    self::deleteDiskFile($projectRoot, $existing['image_path']);
                                }
                                $imagePath = null;
                            }

                            ModelRepository::update($id, [
                                'brand_id'      => $brandId,
                                'year'          => $year,
                                'scale_id'      => $scaleId,
                                'series'        => $series !== '' ? $series : null,
                                'series_number' => $seriesNumber !== '' ? $seriesNumber : null,
                                'serial_number' => $serialNumber !== '' ? $serialNumber : null,
                                'maker_id'      => $makerId,
                                'model'         => $model,
                                'model_year'    => $modelYear,
                                'color_id'      => $colorId,
                                'condition_id'  => $conditionId,
                                'link'          => $link !== '' ? $link : null,
                                'description'   => $description !== '' ? $description : null,
                                'image_path'    => $imagePath,
                            ], $currentUserId);

                            $_SESSION['flash_success'] = "Model '{$model}' updated successfully.";
                            header("Location: /models?edit={$id}", true, 302);
                            exit;

                        } else {
                            // -------------------------------------------------
                            // CREATE NEW MODEL
                            // -------------------------------------------------
                            $newId = ModelRepository::create([
                                'user_id'       => $currentUserId,
                                'brand_id'      => $brandId,
                                'year'          => $year,
                                'scale_id'      => $scaleId,
                                'series'        => $series !== '' ? $series : null,
                                'series_number' => $seriesNumber !== '' ? $seriesNumber : null,
                                'serial_number' => $serialNumber !== '' ? $serialNumber : null,
                                'maker_id'      => $makerId,
                                'model'         => $model,
                                'model_year'    => $modelYear,
                                'color_id'      => $colorId,
                                'condition_id'  => $conditionId,
                                'link'          => $link !== '' ? $link : null,
                                'description'   => $description !== '' ? $description : null,
                                'image_path'    => null,
                            ]);

                            if ($hasUploadedImage) {
                                $filename = "{$newId}_{$currentUserId}_image.{$ext}";
                                $targetPath = $uploadDir . '/' . $filename;

                                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                                    $imagePath = "/images/models/{$filename}";
                                    ModelRepository::updateImagePath($newId, $imagePath, $currentUserId);
                                }
                            }

                            $_SESSION['flash_success'] = "Model '{$model}' created successfully in your collection.";
                            header("Location: /models?edit={$newId}", true, 302);
                            exit;
                        }

                    case 'delete':
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            throw new Exception("Invalid model ID specified for deletion.");
                        }

                        $modelRecord = ModelRepository::findById($id, $currentUserId);
                        if (!$modelRecord) {
                            throw new Exception("Model not found or access denied.");
                        }

                        if (!empty($modelRecord['image_path'])) {
                            self::deleteDiskFile($projectRoot, $modelRecord['image_path']);
                        }

                        ModelRepository::delete($id, $currentUserId);

                        $_SESSION['flash_success'] = "Model '{$modelRecord['model']}' deleted successfully.";
                        header("Location: /models", true, 302);
                        exit;

                    default:
                        throw new Exception("Invalid action requested.");
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
                if (!empty($_POST['id'])) {
                    $selectedModel = ModelRepository::findById((int)$_POST['id'], $currentUserId);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 5. Handle GET Actions (Edit Selection)
        // ---------------------------------------------------------------------
        if ($requestMethod === 'GET' && isset($_GET['edit'])) {
            $editId = (int)$_GET['edit'];
            if ($editId > 0) {
                $selectedModel = ModelRepository::findById($editId, $currentUserId);
                if (!$selectedModel && empty($error)) {
                    $error = "Requested model was not found in your collection.";
                }
            }
        }

        // ---------------------------------------------------------------------
        // 6. Fetch Catalog & Metadata Lists for Dropdowns & Color Picker
        // ---------------------------------------------------------------------
        $brandsList = BrandRepository::getAll();
        $makersList = MakerRepository::getAll();
        $scalesList = ScaleRepository::getAll();
        $colorsList = ColorRepository::getAll();
        $conditionsList = ConditionRepository::getAll();

        // ---------------------------------------------------------------------
        // 7. Build and Configure Grid Controller
        // ---------------------------------------------------------------------
        $allModels = ModelRepository::getAllByUser($currentUserId);
        $selectedId = !empty($selectedModel) ? (int)$selectedModel['id'] : 0;

        $grid = new GridController([
            'id'                => 'models-grid',
            'action'            => '/models',
            'per_page'          => 25,
            'allowed_per_page'  => [10, 25, 50, 100],
            'default_sort'      => 'id',
            'default_order'     => 'desc',
            'show_search'       => false,
            'show_reset_button' => false,
            'filter_position'   => 'toolbar',
            'row_url'           => function($row) {
                return '/models?edit=' . (int)$row['id'];
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
            'width'      => '65px',
            'formatter'  => function($val) {
                return '<span class="text-muted text-sm">#' . (int)$val . '</span>';
            },
        ]);

        // Second column: Model Name with Condition badge (no series or number)
        $grid->addColumn('model', 'Model', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val, $row) {
                $safeName = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                $conditionBadge = '';
                if (!empty($row['condition_name'])) {
                    $safeCondition = htmlspecialchars((string)$row['condition_name'], ENT_QUOTES, 'UTF-8');
                    $conditionBadge = ' <span class="badge model-condition-badge" style="margin-left: 0.4rem; font-size: 0.65rem; padding: 0.15rem 0.45rem; font-weight: 600; vertical-align: middle; color: #ff4d47; background-color: rgba(225, 6, 0, 0.16); border: 1px solid rgba(225, 6, 0, 0.45);">' . $safeCondition . '</span>';
                }
                return '<div class="model-grid-title-cell"><strong>' . $safeName . '</strong>' . $conditionBadge . '</div>';
            },
        ]);

        // Brand Column
        $grid->addColumn('brand_name', 'Brand', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                return htmlspecialchars((string)($val ?: '—'), ENT_QUOTES, 'UTF-8');
            },
        ]);

        // Maker Column (as plain text)
        $grid->addColumn('maker_name', 'Maker', [
            'sortable'   => true,
            'filterable' => true,
            'formatter'  => function($val) {
                return htmlspecialchars((string)($val ?: '—'), ENT_QUOTES, 'UTF-8');
            },
        ]);

        // Scale Column
        $grid->addColumn('scale_name', 'Scale', [
            'sortable'   => true,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '75px',
            'formatter'  => function($val) {
                return '<span class="badge badge-secondary text-xs">' . htmlspecialchars((string)($val ?: '—'), ENT_QUOTES, 'UTF-8') . '</span>';
            },
        ]);

        // Year Column
        $grid->addColumn('year', 'Year', [
            'sortable'   => true,
            'filterable' => false,
            'align'      => 'center',
            'width'      => '70px',
            'formatter'  => function($val) {
                return !empty($val) ? htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') : '—';
            },
        ]);

        // Color Column with actual color swatch
        $grid->addColumn('color_name', 'Color', [
            'sortable'   => true,
            'filterable' => false,
            'formatter'  => function($val, $row) {
                if (empty($val)) {
                    return '<span class="text-muted text-sm">—</span>';
                }
                $safeName = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                $hex = !empty($row['color_hex']) ? htmlspecialchars((string)$row['color_hex'], ENT_QUOTES, 'UTF-8') : '#888888';
                return '<span class="model-color-tag"><span class="model-color-swatch-sm" style="background-color: ' . $hex . ';"></span> ' . $safeName . '</span>';
            },
        ]);

        $grid->setData($allModels);
        $grid->process();

        // 8. Render View
        require dirname(__DIR__) . '/views/models.php';
    }

    /**
     * Helper to safely remove file on disk based on relative or absolute path
     */
    protected static function deleteDiskFile(string $projectRoot, string $imagePath): void {
        $cleanPath = ltrim($imagePath, '/\\');
        $fullPath = $projectRoot . '/' . $cleanPath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
