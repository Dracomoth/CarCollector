<?php
$pageTitle = 'Models Collection - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Models Collection</h1>
                <p class="text-muted">Manage your personal collection of scale car models, diecasts, specifications, and photography.</p>
            </div>
            <div class="page-header-actions">
                <a href="/models" class="btn btn-outline btn-sm model-new-trigger" id="btn-header-new-model">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Model
                </a>
            </div>
        </div>

        <!-- System Flash Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- =================================================================== -->
        <!-- Two-Column Master / Detail Layout: Form on Left, Grid on Right -->
        <!-- =================================================================== -->
        <div class="models-layout brands-layout">
            <!-- --------------------------------------------------------------- -->
            <!-- Left Column: Model Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="models-form-col brands-form-col">
                <div class="card model-card brand-card">
                    <div class="card-header-row">
                        <div class="model-card-header-info brand-card-header-info">
                            <h2 class="card-title" id="model-form-title">
                                <?= !empty($selectedModel) ? 'Edit: ' . htmlspecialchars($selectedModel['model'], ENT_QUOTES, 'UTF-8') : 'Model Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title (date-only) -->
                            <div class="model-top-timestamps brand-top-timestamps" id="model-top-timestamps">
                                <span id="model-top-timestamps-content">
                                    <?php if (!empty($selectedModel)): ?>
                                        <?php
                                        $createdDate = !empty($selectedModel['created']) ? substr((string)$selectedModel['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedModel['modified']) ? substr((string)$selectedModel['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="model-timestamps-empty brand-timestamps-empty">New model &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="model-id-badge" style="<?= empty($selectedModel) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="model-id-badge-text">#<?= !empty($selectedModel) ? (int)$selectedModel['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/models" enctype="multipart/form-data" id="model-main-form" class="form model-form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="model-id-input" value="<?= !empty($selectedModel) ? (int)$selectedModel['id'] : '' ?>">

                        <!-- Group Box 1: Core Vehicle Identification -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Identification</legend>
                            
                            <!-- Brand & Maker row -->
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="model-brand-id">Brand <span class="required text-danger">*</span></label>
                                    <select id="model-brand-id" name="brand_id" required class="form-control">
                                        <option value="">— Select Brand —</option>
                                        <?php foreach ($brandsList as $b): ?>
                                            <option value="<?= (int)$b['id'] ?>" <?= (!empty($selectedModel) && (int)$selectedModel['brand_id'] === (int)$b['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="model-maker-id">Maker (Diecast)</label>
                                    <select id="model-maker-id" name="maker_id" class="form-control">
                                        <option value="">— None / Unknown —</option>
                                        <?php foreach ($makersList as $mk): ?>
                                            <option value="<?= (int)$mk['id'] ?>" <?= (!empty($selectedModel) && (int)($selectedModel['maker_id'] ?? 0) === (int)$mk['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mk['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Model Name -->
                            <div class="form-group">
                                <label for="model-name">Model Name <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="model-name" 
                                       name="model" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedModel['model'] ?? $_POST['model'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Porsche 911 GT3 RS, '70 Dodge Charger R/T">
                            </div>

                            <!-- Scale & Years row -->
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="model-scale-id">Scale <span class="required text-danger">*</span></label>
                                    <select id="model-scale-id" name="scale_id" required class="form-control">
                                        <option value="">— Scale —</option>
                                        <?php foreach ($scalesList as $sc): ?>
                                            <option value="<?= (int)$sc['id'] ?>" <?= (!empty($selectedModel) && (int)$selectedModel['scale_id'] === (int)$sc['id']) ? 'selected' : ((empty($selectedModel) && $sc['scale'] === '1:64') ? 'selected' : '') ?>>
                                                <?= htmlspecialchars($sc['scale'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="model-release-year">Toy Year</label>
                                    <input type="number" 
                                           id="model-release-year" 
                                           name="year" 
                                           class="form-control"
                                           min="1900" 
                                           max="2099" 
                                           value="<?= htmlspecialchars($selectedModel['year'] ?? $_POST['year'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="e.g. 2024">
                                </div>

                                <div class="form-group">
                                    <label for="model-car-year">Car Year</label>
                                    <input type="number" 
                                           id="model-car-year" 
                                           name="model_year" 
                                           class="form-control"
                                           min="1880" 
                                           max="2099" 
                                           value="<?= htmlspecialchars($selectedModel['model_year'] ?? $_POST['model_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="e.g. 1970">
                                </div>
                            </div>
                        </fieldset>

                        <!-- Group Box 2: Series, Serial & Specifications -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Series & Details</legend>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="model-series">Series</label>
                                    <input type="text" 
                                           id="model-series" 
                                           name="series" 
                                           class="form-control"
                                           value="<?= htmlspecialchars($selectedModel['series'] ?? $_POST['series'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="e.g. Mainline">
                                </div>

                                <div class="form-group">
                                    <label for="model-series-num">Series #</label>
                                    <input type="text" 
                                           id="model-series-num" 
                                           name="series_number" 
                                           class="form-control"
                                           value="<?= htmlspecialchars($selectedModel['series_number'] ?? $_POST['series_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="e.g. 04/250">
                                </div>

                                <div class="form-group">
                                    <label for="model-serial-num">Serial / Toy #</label>
                                    <input type="text" 
                                           id="model-serial-num" 
                                           name="serial_number" 
                                           class="form-control"
                                           value="<?= htmlspecialchars($selectedModel['serial_number'] ?? $_POST['serial_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="e.g. HF667">
                                </div>
                            </div>

                            <!-- Color & Condition Row -->
                            <div class="form-row-2">
                                <!-- Interactive Color Picker Dropdown with actual swatches -->
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="hidden" name="color_id" id="model-color-id" value="<?= !empty($selectedModel['color_id']) ? (int)$selectedModel['color_id'] : '' ?>">
                                    
                                    <div class="model-color-picker-widget" id="model-color-picker-widget">
                                        <button type="button" class="form-control model-color-picker-trigger" id="model-color-picker-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="model-color-swatch-box" id="model-color-trigger-swatch" style="<?= !empty($selectedModel['color_hex']) ? 'background-color: ' . htmlspecialchars($selectedModel['color_hex'], ENT_QUOTES, 'UTF-8') . ';' : 'background-color: transparent;' ?>"></span>
                                            <span class="model-color-trigger-text" id="model-color-trigger-text">
                                                <?= !empty($selectedModel['color_name']) ? htmlspecialchars($selectedModel['color_name'], ENT_QUOTES, 'UTF-8') : '— Select Color —' ?>
                                            </span>
                                            <svg class="model-color-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>

                                        <!-- Popover list of available colors with square swatches -->
                                        <div class="model-color-picker-popover" id="model-color-picker-popover" role="listbox">
                                            <div class="model-color-picker-item <?= empty($selectedModel['color_id']) ? 'is-selected' : '' ?>" data-color-id="" data-color-name="— None / Unspecified —" data-color-hex="">
                                                <span class="model-color-swatch-box is-empty"></span>
                                                <span class="model-color-item-name">— None / Unspecified —</span>
                                            </div>
                                            <?php foreach ($colorsList as $c): ?>
                                                <?php $isColorSel = (!empty($selectedModel) && (int)($selectedModel['color_id'] ?? 0) === (int)$c['id']); ?>
                                                <div class="model-color-picker-item <?= $isColorSel ? 'is-selected' : '' ?>" 
                                                     data-color-id="<?= (int)$c['id'] ?>" 
                                                     data-color-name="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                     data-color-hex="<?= htmlspecialchars($c['hex_code'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <span class="model-color-swatch-box" style="background-color: <?= htmlspecialchars($c['hex_code'], ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                    <span class="model-color-item-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="model-color-item-hex"><?= htmlspecialchars($c['hex_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="model-condition-id">Condition</label>
                                    <select id="model-condition-id" name="condition_id" class="form-control">
                                        <option value="">— None / Unspecified —</option>
                                        <?php foreach ($conditionsList as $cond): ?>
                                            <option value="<?= (int)$cond['id'] ?>" <?= (!empty($selectedModel) && (int)($selectedModel['condition_id'] ?? 0) === (int)$cond['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cond['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Link / URL -->
                            <div class="form-group">
                                <label for="model-link">External Reference Link</label>
                                <div class="input-with-action">
                                    <input type="url" 
                                           id="model-link" 
                                           name="link" 
                                           class="form-control"
                                           value="<?= htmlspecialchars($selectedModel['link'] ?? $_POST['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="https://hotwheels.fandom.com/wiki/...">
                                    <a href="<?= !empty($selectedModel['link']) ? htmlspecialchars($selectedModel['link'], ENT_QUOTES, 'UTF-8') : '#' ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       class="btn btn-outline btn-sm input-link-btn <?= empty($selectedModel['link']) ? 'is-disabled' : '' ?>" 
                                       id="btn-model-link-preview" 
                                       title="Open link in new tab">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Description Textarea (fixed height) -->
                            <div class="form-group">
                                <label for="model-description">Notes & Description</label>
                                <textarea id="model-description" 
                                          name="description" 
                                          rows="3" 
                                          class="form-control model-description-area"
                                          placeholder="Casting details, wheel variants, tampo notes, package condition..."><?= htmlspecialchars($selectedModel['description'] ?? $_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </fieldset>

                        <!-- Group Box 3: Model Photography & Camera Capture -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Model Photography</legend>

                            <div class="form-group">
                                <div class="brand-logo-dropzone model-image-dropzone" id="model-image-dropzone" tabindex="0" role="button" aria-label="Upload Model Photo">
                                    <!-- Standard File Picker -->
                                    <input type="file" 
                                           id="model-image-input" 
                                           name="image" 
                                           accept="image/png,image/jpeg,image/webp,image/gif" 
                                           class="brand-logo-hidden-input">

                                    <!-- Camera Direct Capture (environment rear camera) -->
                                    <input type="file" 
                                           id="model-camera-input" 
                                           name="camera_image" 
                                           accept="image/*" 
                                           capture="environment" 
                                           class="brand-logo-hidden-input">
                                    
                                    <div class="brand-logo-view model-image-view" id="model-image-view">
                                        <!-- Always present placeholder with car photo icon -->
                                        <div class="brand-logo-placeholder" id="model-image-placeholder" style="<?= !empty($selectedModel['image_path']) ? 'display: none;' : '' ?>">
                                            <svg class="brand-logo-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                                <circle cx="12" cy="13" r="4"></circle>
                                            </svg>
                                            <span class="brand-logo-upload-title">Model Photo</span>
                                            <span class="brand-logo-upload-sub">Click to Browse or Take Camera Photo</span>
                                        </div>

                                        <!-- Image preview container -->
                                        <div class="brand-logo-current-wrap model-image-current-wrap" id="current-image-wrap" style="<?= empty($selectedModel['image_path']) ? 'display: none;' : '' ?>">
                                            <img src="<?= htmlspecialchars(ImageHelper::versioned($selectedModel['image_path'] ?? '', $selectedModel['modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" 
                                                 alt="Model Photo" 
                                                 class="brand-logo-preview-image model-image-preview-image" 
                                                 id="model-image-preview-img">
                                        </div>
                                        <span class="brand-logo-hint" id="model-image-hint" style="<?= empty($selectedModel['image_path']) ? 'display: none;' : '' ?>">Click or use Camera to replace photo</span>
                                    </div>
                                </div>

                                <!-- Hidden input for removal flag on submit -->
                                <input type="hidden" name="remove_image" id="model-remove-image-input" value="0">

                                <!-- Photo Controls Row: Browse, Camera, Remove -->
                                <div class="model-photo-btn-row">
                                    <button type="button" class="btn brand-media-btn brand-browse-btn" id="btn-browse-photo">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                        Browse
                                    </button>
                                    
                                    <!-- Mobile camera direct snap button -->
                                    <button type="button" class="btn brand-media-btn model-camera-btn" id="btn-camera-photo" title="Open camera to snap photo">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                        Take Photo
                                    </button>

                                    <button type="button" 
                                            class="btn brand-media-btn brand-remove-btn <?= empty($selectedModel['image_path']) ? 'is-disabled' : '' ?>" 
                                            id="btn-remove-photo" 
                                            <?= empty($selectedModel['image_path']) ? 'disabled' : '' ?>>
                                        Remove
                                    </button>
                                </div>
                                <div id="camera-upload-status" class="model-upload-status text-xs" style="display: none;"></div>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-model-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-model-save-text"><?= !empty($selectedModel) ? 'Save Changes' : 'Save Model' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/models" class="btn btn-outline brand-sub-btn brand-new-btn model-new-trigger" id="btn-form-new-model" title="Clear form to create a new model">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedModel) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-model-delete"
                                        <?= empty($selectedModel) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedModel) ? 'Select a model from the grid to delete' : 'Delete Model' ?>"
                                        <?= !empty($selectedModel) ? 'onclick="handleDeleteModel(' . (int)$selectedModel['id'] . ', \'' . htmlspecialchars(addslashes($selectedModel['model']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Hidden Delete Form -->
                    <form method="POST" action="/models" id="model-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-model-id-input" value="<?= !empty($selectedModel) ? (int)$selectedModel['id'] : '' ?>">
                    </form>
                </div>
            </aside>

            <!-- --------------------------------------------------------------- -->
            <!-- Right Column: Reusable Grid Controller Presentation -->
            <!-- --------------------------------------------------------------- -->
            <section class="models-grid-col brands-grid-col">
                <div class="card models-grid-card brands-grid-card">
                    <div class="card-header-row">
                        <div>
                            <h2 class="card-title">My Collection Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and browse your personal collection of scale car models.</p>
                        </div>
                        <div class="models-count-badge brands-count-badge">
                            <span class="badge badge-primary"><?= count($allModels) ?> Total Models</span>
                        </div>
                    </div>

                    <!-- Render Grid Controller Component -->
                    <?php if (!empty($grid)): ?>
                        <?= $grid->render() ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Client-side script for interactive model selection, photo preview & camera upload -->
    <script>
        (function() {
            var dropzone = document.getElementById('model-image-dropzone');
            var fileInput = document.getElementById('model-image-input');
            var cameraInput = document.getElementById('model-camera-input');
            var previewImg = document.getElementById('model-image-preview-img');
            var currentWrap = document.getElementById('current-image-wrap');
            var placeholder = document.getElementById('model-image-placeholder');
            var hint = document.getElementById('model-image-hint');
            var removeInput = document.getElementById('model-remove-image-input');
            var browseBtn = document.getElementById('btn-browse-photo');
            var cameraBtn = document.getElementById('btn-camera-photo');
            var removeBtn = document.getElementById('btn-remove-photo');
            var idBadge = document.getElementById('model-id-badge');
            var idBadgeText = document.getElementById('model-id-badge-text');
            var linkInput = document.getElementById('model-link');
            var linkPreviewBtn = document.getElementById('btn-model-link-preview');
            var statusEl = document.getElementById('camera-upload-status');

            // -----------------------------------------------------------------
            // Link preview updater
            // -----------------------------------------------------------------
            if (linkInput && linkPreviewBtn) {
                linkInput.addEventListener('input', function() {
                    var val = this.value.trim();
                    if (val && (val.indexOf('http://') === 0 || val.indexOf('https://') === 0)) {
                        linkPreviewBtn.href = val;
                        linkPreviewBtn.classList.remove('is-disabled');
                    } else {
                        linkPreviewBtn.href = '#';
                        linkPreviewBtn.classList.add('is-disabled');
                    }
                });
            }

            // -----------------------------------------------------------------
            // Dropzone & File chooser
            // -----------------------------------------------------------------
            if (dropzone && fileInput) {
                dropzone.addEventListener('click', function(e) {
                    fileInput.click();
                });

                dropzone.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        fileInput.click();
                    }
                });

                ['dragenter', 'dragover'].forEach(function(eventName) {
                    dropzone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('is-dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(function(eventName) {
                    dropzone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('is-dragover');
                    }, false);
                });

                dropzone.addEventListener('drop', function(e) {
                    var dt = e.dataTransfer;
                    var files = dt.files;
                    if (files && files.length > 0) {
                        fileInput.files = files;
                        showPreview(files[0]);
                    }
                });

                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        showPreview(this.files[0]);
                    }
                });
            }

            if (browseBtn && fileInput) {
                browseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    fileInput.click();
                });
            }

            // -----------------------------------------------------------------
            // Camera Direct Capture & Immediate Upload
            // -----------------------------------------------------------------
            if (cameraBtn && cameraInput) {
                cameraBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    cameraInput.click();
                });

                cameraInput.addEventListener('change', function() {
                    if (!this.files || this.files.length === 0) return;
                    var file = this.files[0];
                    showPreview(file);

                    // Check if existing model (id > 0) -> perform immediate background upload!
                    var currentId = document.getElementById('model-id-input').value;
                    if (currentId && parseInt(currentId, 10) > 0) {
                        uploadCameraPhotoImmediately(parseInt(currentId, 10), file);
                    }
                });
            }

            function uploadCameraPhotoImmediately(modelId, file) {
                if (!statusEl) return;
                statusEl.style.display = 'block';
                statusEl.style.color = '#38bdf8';
                statusEl.textContent = 'Uploading photo from camera...';

                var formData = new FormData();
                formData.append('action', 'upload_camera_ajax');
                formData.append('id', modelId);
                formData.append('camera_image', file);

                fetch('/models', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (data && data.success) {
                        statusEl.style.color = '#4ade80';
                        statusEl.textContent = data.message || 'Photo saved!';
                        if (previewImg && data.image_path) {
                            previewImg.src = data.image_path + '?t=' + Date.now();
                        }
                        setTimeout(function() {
                            statusEl.style.display = 'none';
                        }, 4000);
                    } else {
                        throw new Error((data && data.error) ? data.error : 'Upload failed');
                    }
                })
                .catch(function(err) {
                    statusEl.style.color = '#f87171';
                    statusEl.textContent = 'Upload error: ' + err.message;
                });
            }

            // Remove button
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (fileInput) fileInput.value = '';
                    if (cameraInput) cameraInput.value = '';
                    if (removeInput) removeInput.value = '1';
                    if (previewImg) previewImg.src = '';
                    if (currentWrap) currentWrap.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'flex';
                    if (hint) hint.style.display = 'none';
                    removeBtn.disabled = true;
                    removeBtn.classList.add('is-disabled');
                });
            }

            function showPreview(file) {
                if (!file.type.match('image.*')) {
                    alert('Please select an image file (PNG, JPG, WEBP).');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg) previewImg.src = e.target.result;
                    if (currentWrap) currentWrap.style.display = 'flex';
                    if (placeholder) placeholder.style.display = 'none';
                    if (hint) {
                        hint.style.display = 'block';
                        hint.textContent = 'Selected: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    }
                    if (removeInput) removeInput.value = '0';
                    if (removeBtn) {
                        removeBtn.disabled = false;
                        removeBtn.classList.remove('is-disabled');
                    }
                };
                reader.readAsDataURL(file);
            }

            // -----------------------------------------------------------------
            // Interactive Color Picker Widget
            // -----------------------------------------------------------------
            var colorWidget = document.getElementById('model-color-picker-widget');
            var colorTrigger = document.getElementById('model-color-picker-trigger');
            var colorPopover = document.getElementById('model-color-picker-popover');
            var colorIdInput = document.getElementById('model-color-id');
            var triggerSwatch = document.getElementById('model-color-trigger-swatch');
            var triggerText = document.getElementById('model-color-trigger-text');

            if (colorTrigger && colorPopover) {
                colorTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var isOpen = colorPopover.classList.contains('is-open');
                    if (isOpen) {
                        colorPopover.classList.remove('is-open');
                        colorTrigger.setAttribute('aria-expanded', 'false');
                    } else {
                        colorPopover.classList.add('is-open');
                        colorTrigger.setAttribute('aria-expanded', 'true');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (colorWidget && !colorWidget.contains(e.target)) {
                        colorPopover.classList.remove('is-open');
                        colorTrigger.setAttribute('aria-expanded', 'false');
                    }
                });

                colorPopover.querySelectorAll('.model-color-picker-item').forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var cid = this.getAttribute('data-color-id') || '';
                        var cname = this.getAttribute('data-color-name') || '';
                        var chex = this.getAttribute('data-color-hex') || '';

                        setColorSelection(cid, cname, chex);
                        colorPopover.classList.remove('is-open');
                        colorTrigger.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            function setColorSelection(id, name, hex) {
                if (colorIdInput) colorIdInput.value = id;
                if (triggerText) triggerText.textContent = name || '— Select Color —';
                if (triggerSwatch) {
                    if (hex) {
                        triggerSwatch.style.backgroundColor = hex;
                        triggerSwatch.classList.remove('is-empty');
                    } else {
                        triggerSwatch.style.backgroundColor = 'transparent';
                        triggerSwatch.classList.add('is-empty');
                    }
                }
                if (colorPopover) {
                    colorPopover.querySelectorAll('.model-color-picker-item').forEach(function(it) {
                        if (it.getAttribute('data-color-id') == id) {
                            it.classList.add('is-selected');
                        } else {
                            it.classList.remove('is-selected');
                        }
                    });
                }
            }

            // Delete prompt helper
            window.handleDeleteModel = function(modelId, modelName) {
                if (!modelId) return;
                var msg = 'Are you sure you want to permanently delete model "' + modelName + '" and its photo?';
                if (confirm(msg)) {
                    var form = document.getElementById('model-delete-form');
                    var idInput = document.getElementById('delete-model-id-input');
                    if (form && idInput) {
                        idInput.value = modelId;
                        form.submit();
                    }
                }
            };

            // -----------------------------------------------------------------
            // Instant Client-Side Row Selection (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function selectModel(m, rowEl) {
                if (!m || !m.id) return;

                // 1. Title & ID badge
                var titleEl = document.getElementById('model-form-title');
                if (titleEl) titleEl.textContent = 'Edit: ' + (m.model || 'Model');

                var timestampsEl = document.getElementById('model-top-timestamps');
                if (timestampsEl) {
                    var createdDate = (m.created || '').split(' ')[0] || (m.created || '').substring(0, 10);
                    var modifiedDate = (m.modified || '').split(' ')[0] || (m.modified || '').substring(0, 10);
                    timestampsEl.innerHTML = 'Created: ' + createdDate + ' &bull; Last Modified: ' + modifiedDate;
                    timestampsEl.style.display = 'block';
                }

                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + m.id;
                    idBadge.style.visibility = 'visible';
                }

                // 2. Form fields
                var idInput = document.getElementById('model-id-input');
                if (idInput) idInput.value = m.id;

                var brandSelect = document.getElementById('model-brand-id');
                if (brandSelect) brandSelect.value = m.brand_id || '';

                var makerSelect = document.getElementById('model-maker-id');
                if (makerSelect) makerSelect.value = m.maker_id || '';

                var nameInput = document.getElementById('model-name');
                if (nameInput) nameInput.value = m.model || '';

                var scaleSelect = document.getElementById('model-scale-id');
                if (scaleSelect) scaleSelect.value = m.scale_id || '';

                var releaseYearInput = document.getElementById('model-release-year');
                if (releaseYearInput) releaseYearInput.value = m.year || '';

                var carYearInput = document.getElementById('model-car-year');
                if (carYearInput) carYearInput.value = m.model_year || '';

                var seriesInput = document.getElementById('model-series');
                if (seriesInput) seriesInput.value = m.series || '';

                var seriesNumInput = document.getElementById('model-series-num');
                if (seriesNumInput) seriesNumInput.value = m.series_number || '';

                var serialNumInput = document.getElementById('model-serial-num');
                if (serialNumInput) serialNumInput.value = m.serial_number || '';

                var conditionSelect = document.getElementById('model-condition-id');
                if (conditionSelect) conditionSelect.value = m.condition_id || '';

                var linkIn = document.getElementById('model-link');
                if (linkIn) linkIn.value = m.link || '';
                if (linkPreviewBtn) {
                    if (m.link && (m.link.indexOf('http://') === 0 || m.link.indexOf('https://') === 0)) {
                        linkPreviewBtn.href = m.link;
                        linkPreviewBtn.classList.remove('is-disabled');
                    } else {
                        linkPreviewBtn.href = '#';
                        linkPreviewBtn.classList.add('is-disabled');
                    }
                }

                var descInput = document.getElementById('model-description');
                if (descInput) descInput.value = m.description || '';

                // 3. Color Picker selection
                setColorSelection(m.color_id || '', m.color_name || '— None / Unspecified —', m.color_hex || '');

                // 4. Photo Preview
                if (fileInput) fileInput.value = '';
                if (cameraInput) cameraInput.value = '';
                if (removeInput) removeInput.value = '0';

                if (m.image_path) {
                    if (previewImg) previewImg.src = getVersionedImageUrl(m.image_path, m.modified);
                    if (currentWrap) currentWrap.style.display = 'flex';
                    if (placeholder) placeholder.style.display = 'none';
                    if (hint) {
                        hint.style.display = 'block';
                        hint.textContent = 'Click or use Camera to replace photo';
                    }
                    if (removeBtn) {
                        removeBtn.disabled = false;
                        removeBtn.classList.remove('is-disabled');
                    }
                } else {
                    if (previewImg) previewImg.src = '';
                    if (currentWrap) currentWrap.style.display = 'none';
                    if (placeholder) placeholder.style.display = 'flex';
                    if (hint) hint.style.display = 'none';
                    if (removeBtn) {
                        removeBtn.disabled = true;
                        removeBtn.classList.add('is-disabled');
                    }
                }

                // 5. Button states
                var saveText = document.getElementById('btn-model-save-text');
                if (saveText) saveText.textContent = 'Save Changes';

                var deleteBtn = document.getElementById('btn-model-delete');
                var deleteIdInput = document.getElementById('delete-model-id-input');
                if (deleteIdInput) deleteIdInput.value = m.id;

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.remove('btn-outline', 'disabled');
                    deleteBtn.classList.add('btn-danger');
                    deleteBtn.setAttribute('title', 'Delete ' + (m.model || 'Model'));
                    deleteBtn.onclick = function() {
                        handleDeleteModel(m.id, m.model || '');
                    };
                }

                // 6. Grid row highlighting
                var gridEl = document.getElementById('models-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                    if (rowEl) {
                        rowEl.classList.add('is-selected');
                    } else {
                        var targetRow = gridEl.querySelector('tr.grid-data-row[data-id="' + m.id + '"]');
                        if (targetRow) targetRow.classList.add('is-selected');
                    }
                }

                // 7. Update browser URL
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', m.id);
                    window.history.pushState({ modelId: m.id }, '', currentUrl.pathname + currentUrl.search);
                }
            }

            // -----------------------------------------------------------------
            // Reset Form to New Model (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function resetModelForm() {
                var titleEl = document.getElementById('model-form-title');
                if (titleEl) titleEl.textContent = 'Model Details';

                var timestampsEl = document.getElementById('model-top-timestamps');
                if (timestampsEl) {
                    timestampsEl.innerHTML = '<span class="model-timestamps-empty brand-timestamps-empty">New model &bull; Not saved yet</span>';
                    timestampsEl.style.display = 'block';
                }

                if (idBadge) idBadge.style.visibility = 'hidden';

                var idInput = document.getElementById('model-id-input');
                if (idInput) idInput.value = '';

                var brandSelect = document.getElementById('model-brand-id');
                if (brandSelect) brandSelect.value = '';

                var makerSelect = document.getElementById('model-maker-id');
                if (makerSelect) makerSelect.value = '';

                var nameInput = document.getElementById('model-name');
                if (nameInput) {
                    nameInput.value = '';
                    nameInput.focus();
                }

                var scaleSelect = document.getElementById('model-scale-id');
                if (scaleSelect) {
                    // Default to 1:64 if available
                    for (var i = 0; i < scaleSelect.options.length; i++) {
                        if (scaleSelect.options[i].text === '1:64') {
                            scaleSelect.selectedIndex = i;
                            break;
                        }
                    }
                }

                var releaseYearInput = document.getElementById('model-release-year');
                if (releaseYearInput) releaseYearInput.value = '';

                var carYearInput = document.getElementById('model-car-year');
                if (carYearInput) carYearInput.value = '';

                var seriesInput = document.getElementById('model-series');
                if (seriesInput) seriesInput.value = '';

                var seriesNumInput = document.getElementById('model-series-num');
                if (seriesNumInput) seriesNumInput.value = '';

                var serialNumInput = document.getElementById('model-serial-num');
                if (serialNumInput) serialNumInput.value = '';

                var conditionSelect = document.getElementById('model-condition-id');
                if (conditionSelect) conditionSelect.value = '';

                var linkIn = document.getElementById('model-link');
                if (linkIn) linkIn.value = '';
                if (linkPreviewBtn) {
                    linkPreviewBtn.href = '#';
                    linkPreviewBtn.classList.add('is-disabled');
                }

                var descInput = document.getElementById('model-description');
                if (descInput) descInput.value = '';

                setColorSelection('', '— Select Color —', '');

                if (fileInput) fileInput.value = '';
                if (cameraInput) cameraInput.value = '';
                if (removeInput) removeInput.value = '0';
                if (previewImg) previewImg.src = '';
                if (currentWrap) currentWrap.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                if (hint) hint.style.display = 'none';
                if (removeBtn) {
                    removeBtn.disabled = true;
                    removeBtn.classList.add('is-disabled');
                }

                var saveText = document.getElementById('btn-model-save-text');
                if (saveText) saveText.textContent = 'Save Model';

                var deleteBtn = document.getElementById('btn-model-delete');
                var deleteIdInput = document.getElementById('delete-model-id-input');
                if (deleteIdInput) deleteIdInput.value = '';

                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline', 'disabled');
                    deleteBtn.setAttribute('title', 'Select a model from the grid to delete');
                    deleteBtn.onclick = null;
                }

                var gridEl = document.getElementById('models-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                }

                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('edit');
                    window.history.pushState({}, '', currentUrl.pathname + (currentUrl.search ? currentUrl.search : ''));
                }
            }

            // Grid selection event listener
            var gridContainer = document.getElementById('models-grid');
            if (gridContainer) {
                gridContainer.addEventListener('grid:row-select', function(e) {
                    e.preventDefault();
                    var modelData = e.detail.data;
                    selectModel(modelData, e.detail.row);
                });
            }

            document.querySelectorAll('.model-new-trigger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetModelForm();
                });
            });

            window.addEventListener('popstate', function(e) {
                var params = new URLSearchParams(window.location.search);
                var editId = params.get('edit');
                if (editId && gridContainer) {
                    var targetRow = gridContainer.querySelector('tr.grid-data-row[data-id="' + editId + '"]');
                    if (targetRow) {
                        try {
                            var modelData = JSON.parse(targetRow.getAttribute('data-row') || '{}');
                            selectModel(modelData, targetRow);
                            return;
                        } catch(err) {}
                    }
                }
                resetModelForm();
            });
        })();
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
