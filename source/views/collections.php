<?php
$pageTitle = 'Collections - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Custom Collections</h1>
                <p class="text-muted">Create custom collections, curating your scale models into personalized showcases.</p>
            </div>
            <div class="page-header-actions">
                <a href="/collections" class="btn btn-outline btn-sm brand-new-trigger" id="btn-header-new-collection">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Collection
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
        <div class="collections-layout brands-layout">
            <!-- --------------------------------------------------------------- -->
            <!-- Left Column: Collection Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="collections-form-col brands-form-col">
                <div class="card collection-card brand-card">
                    <div class="card-header-row">
                        <div class="brand-card-header-info">
                            <h2 class="card-title" id="collection-form-title">
                                <?= !empty($selectedCollection) ? 'Edit: ' . htmlspecialchars($selectedCollection['name'], ENT_QUOTES, 'UTF-8') : 'Collection Details' ?>
                            </h2>
                            <!-- Timestamps directly under title -->
                            <div class="brand-top-timestamps" id="collection-top-timestamps">
                                <span id="collection-top-timestamps-content">
                                    <?php if (!empty($selectedCollection)): ?>
                                        <?php
                                        $createdDate = !empty($selectedCollection['created']) ? substr((string)$selectedCollection['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedCollection['modified']) ? substr((string)$selectedCollection['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="brand-timestamps-empty">New collection &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="collection-id-badge" style="<?= empty($selectedCollection) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="collection-id-badge-text">#<?= !empty($selectedCollection) ? (int)$selectedCollection['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/collections" enctype="multipart/form-data" id="collection-main-form" class="form collection-form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="collection-id-input" value="<?= !empty($selectedCollection) ? (int)$selectedCollection['id'] : '' ?>">

                        <!-- Group Box 1: Collection Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Collection Information</legend>
                            
                            <!-- Collection Name -->
                            <div class="form-group">
                                <label for="collection-name">Collection Name <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="collection-name" 
                                       name="name" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedCollection['name'] ?? $_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Le Mans Winners, 90s Supercars, JDM Legends">
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <label for="collection-description">Description</label>
                                <textarea id="collection-description" 
                                          name="description" 
                                          rows="3" 
                                          class="form-control"
                                          placeholder="Theme, purpose, historical notes..."><?= htmlspecialchars($selectedCollection['description'] ?? $_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </fieldset>

                        <!-- Group Box 2: Collection Logo -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Collection Logo</legend>

                            <div class="form-group">
                                <div class="brand-logo-dropzone" id="collection-logo-dropzone" tabindex="0" role="button" aria-label="Upload Collection Logo">
                                    <input type="file" 
                                           id="collection-logo-input" 
                                           name="logo" 
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif" 
                                           class="brand-logo-hidden-input">
                                    
                                    <div class="brand-logo-view" id="collection-logo-view">
                                        <!-- Always present placeholder with icon -->
                                        <div class="brand-logo-placeholder" id="collection-logo-placeholder" style="<?= !empty($selectedCollection['logo_path']) ? 'display: none;' : '' ?>">
                                            <svg class="brand-logo-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <span class="brand-logo-upload-title">Upload Collection Logo</span>
                                            <span class="brand-logo-upload-sub">PNG, JPG, WEBP, SVG &bull; Click to Browse</span>
                                        </div>

                                        <!-- Image preview container with fixed dimensions -->
                                        <div class="brand-logo-current-wrap" id="collection-logo-current-wrap" style="<?= empty($selectedCollection['logo_path']) ? 'display: none;' : '' ?>">
                                            <img src="<?= !empty($selectedCollection['logo_path']) ? htmlspecialchars($selectedCollection['logo_path'], ENT_QUOTES, 'UTF-8') : '' ?>" 
                                                 alt="Collection Logo" 
                                                 class="brand-logo-preview-image" 
                                                 id="collection-logo-preview-img">
                                        </div>
                                        <span class="brand-logo-hint" id="collection-logo-hint" style="<?= empty($selectedCollection['logo_path']) ? 'display: none;' : '' ?>">Click or drop to replace logo</span>
                                    </div>
                                </div>

                                <!-- Hidden input for removal flag on submit -->
                                <input type="hidden" name="remove_logo" id="collection-remove-logo-input" value="0">

                                <!-- Browse and Remove Action Buttons (matching other forms) -->
                                <div class="brand-logo-btn-row">
                                    <button type="button" class="btn brand-media-btn brand-browse-btn" id="btn-browse-logo">
                                        Browse
                                    </button>
                                    <button type="button" 
                                            class="btn brand-media-btn brand-remove-btn <?= empty($selectedCollection['logo_path']) ? 'is-disabled' : '' ?>" 
                                            id="btn-remove-logo" 
                                            <?= empty($selectedCollection['logo_path']) ? 'disabled' : '' ?>>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Group Box 3: Models in this Collection -->
                        <fieldset class="form-group-box collection-models-box">
                            <legend class="form-group-legend">Models in Collection</legend>

                            <div class="collection-models-header">
                                <span class="collection-models-header-title">
                                    <span>Included Models</span>
                                    <span class="badge badge-primary text-xs" id="collection-models-count"><?= count($collectionModels) ?></span>
                                </span>
                                <?php if (!empty($selectedCollection)): ?>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-models-modal">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        Add Models
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Save collection first to add models">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        Add Models
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="collection-models-list" id="collection-models-list">
                                <?php if (empty($selectedCollection)): ?>
                                    <div class="collection-models-empty">
                                        Save this collection first to start adding scale models.
                                    </div>
                                <?php elseif (empty($collectionModels)): ?>
                                    <div class="collection-models-empty" id="empty-collection-message">
                                        No models have been added to this collection yet.<br>Click <strong>"Add Models"</strong> above to select from your collection.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($collectionModels as $m): ?>
                                        <div class="collection-model-item" id="model-item-<?= (int)$m['id'] ?>">
                                            <div class="collection-model-item-info">
                                                <?php if (!empty($m['image_path'])): ?>
                                                    <img src="<?= htmlspecialchars($m['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($m['model'], ENT_QUOTES, 'UTF-8') ?>" class="collection-model-thumb" loading="lazy">
                                                <?php else: ?>
                                                    <div class="collection-model-thumb-fallback">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path><path d="M5 12h14"></path>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="collection-model-texts">
                                                    <span class="collection-model-name" title="<?= htmlspecialchars($m['model'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($m['model'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="collection-model-sub">
                                                        <?= htmlspecialchars($m['brand_name'] ?? 'Unknown Brand', ENT_QUOTES, 'UTF-8') ?>
                                                        <?php if (!empty($m['maker_name'])): ?> &bull; <?= htmlspecialchars($m['maker_name'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                                        <?php if (!empty($m['scale_name'])): ?> &bull; <?= htmlspecialchars($m['scale_name'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if ($canWrite): ?>
                                                <button type="button" 
                                                        class="btn-remove-model" 
                                                        data-model-id="<?= (int)$m['id'] ?>" 
                                                        data-model-name="<?= htmlspecialchars($m['model'], ENT_QUOTES, 'UTF-8') ?>"
                                                        title="Remove from collection">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-collection-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-collection-save-text"><?= !empty($selectedCollection) ? 'Save Changes' : 'Save Collection' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/collections" class="btn btn-outline brand-sub-btn brand-new-btn brand-new-trigger" id="btn-form-new-collection" title="Clear form to create a new collection">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedCollection) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-collection-delete"
                                        <?= empty($selectedCollection) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedCollection) ? 'Select a collection from the grid to delete' : 'Delete Collection' ?>"
                                        <?= !empty($selectedCollection) ? 'onclick="handleDeleteCollection(' . (int)$selectedCollection['id'] . ', \'' . htmlspecialchars(addslashes($selectedCollection['name']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/collections" id="collection-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-collection-id-input" value="<?= !empty($selectedCollection) ? (int)$selectedCollection['id'] : '' ?>">
                    </form>
                </div>
            </aside>

            <!-- --------------------------------------------------------------- -->
            <!-- Right Column: Collections Data Grid -->
            <!-- --------------------------------------------------------------- -->
            <section class="collections-grid-col brands-grid-col">
                <div class="card grid-card">
                    <?= $grid->render() ?>
                </div>
            </section>
        </div>
    </main>

    <!-- ======================================================================= -->
    <!-- Modal: Add Models to Collection -->
    <!-- ======================================================================= -->
    <div class="collection-modal-backdrop" id="add-models-backdrop" aria-hidden="true">
        <div class="collection-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <!-- Modal Header -->
            <div class="collection-modal-header">
                <div class="collection-modal-header-info">
                    <h3 id="modal-title">Add Models to Collection</h3>
                    <p>Select owned scale models to include in <strong class="text-contrast" id="modal-collection-name"><?= htmlspecialchars($selectedCollection['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>
                <button type="button" class="collection-modal-close" id="btn-close-modal" aria-label="Close modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Modal Filters Toolbar -->
            <div class="collection-modal-toolbar">
                <div class="collection-modal-search">
                    <input type="text" 
                           id="modal-search-input" 
                           class="form-control" 
                           placeholder="Search by model name or series..." 
                           autocomplete="off">
                </div>
                <div class="collection-modal-filter">
                    <select id="modal-filter-brand" class="form-control">
                        <option value="">All Brands</option>
                        <?php foreach ($allBrands as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="collection-modal-filter">
                    <select id="modal-filter-maker" class="form-control">
                        <option value="">All Makers</option>
                        <?php foreach ($allMakers as $mk): ?>
                            <option value="<?= (int)$mk['id'] ?>"><?= htmlspecialchars($mk['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="btn-modal-reset-filters" title="Reset all filters">
                    Reset
                </button>
            </div>

            <!-- Modal Body: Cards Grid -->
            <div class="collection-modal-body">
                <div class="collection-modal-grid" id="modal-models-grid">
                    <!-- Populated dynamically via JavaScript -->
                </div>
            </div>

            <!-- Modal Footer: Selection Counter, Pagination, and Action Buttons -->
            <div class="collection-modal-footer">
                <div class="collection-modal-pagination" id="modal-pagination">
                    <!-- Dynamic page buttons -->
                </div>

                <div class="collection-modal-actions">
                    <span id="modal-selected-summary" class="text-sm text-muted">
                        <strong id="modal-selected-count" class="text-contrast">0</strong> models selected
                    </span>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-submit-add-models" disabled>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add to Collection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- Interactive Client Scripts -->
    <!-- ======================================================================= -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var currentCollectionId = <?= !empty($selectedCollection) ? (int)$selectedCollection['id'] : 0 ?>;

        // ---------------------------------------------------------------------
        // 1. Logo Dropzone & Preview Handling
        // ---------------------------------------------------------------------
        var dropzone = document.getElementById('collection-logo-dropzone');
        var fileInput = document.getElementById('collection-logo-input');
        var previewImg = document.getElementById('collection-logo-preview-img');
        var currentWrap = document.getElementById('collection-logo-current-wrap');
        var placeholder = document.getElementById('collection-logo-placeholder');
        var hint = document.getElementById('collection-logo-hint');
        var removeInput = document.getElementById('collection-remove-logo-input');
        var browseBtn = document.getElementById('btn-browse-logo');
        var removeBtn = document.getElementById('btn-remove-logo');

        if (dropzone && fileInput) {
            // Click dropzone triggers file picker
            dropzone.addEventListener('click', function(e) {
                fileInput.click();
            });

            // Keyboard enter/space triggers file picker
            dropzone.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });

            // Drag & drop highlight
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

            // Handle dropped file
            dropzone.addEventListener('drop', function(e) {
                var dt = e.dataTransfer;
                var files = dt ? dt.files : null;
                if (files && files.length > 0) {
                    fileInput.files = files;
                    showPreview(files[0]);
                }
            });

            // Handle file picker selection
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    showPreview(this.files[0]);
                }
            });
        }

        // Browse button explicitly opens file chooser
        if (browseBtn && fileInput) {
            browseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
        }

        // Remove button clears preview, sets remove flag, and resets file input
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (fileInput) fileInput.value = '';
                if (removeInput) removeInput.value = '1';
                if (previewImg) previewImg.src = '';
                if (currentWrap) currentWrap.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                if (hint) {
                    hint.style.display = 'block';
                    hint.textContent = 'Logo marked for removal';
                }
                removeBtn.disabled = true;
                removeBtn.classList.add('is-disabled');
            });
        }

        function showPreview(file) {
            if (!file.type.match('image.*')) {
                alert('Please select an image file (PNG, JPG, WEBP, SVG, GIF).');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
                if (placeholder) placeholder.style.display = 'none';
                if (currentWrap) currentWrap.style.display = 'flex';
                if (hint) {
                    hint.style.display = 'block';
                    hint.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                }
                if (removeInput) removeInput.value = '0';
                if (removeBtn) {
                    removeBtn.disabled = false;
                    removeBtn.classList.remove('is-disabled');
                }
            };
            reader.readAsDataURL(file);
        }

        // ---------------------------------------------------------------------
        // 2. Delete Collection Confirmation
        // ---------------------------------------------------------------------
        window.handleDeleteCollection = function(id, name) {
            if (!id) return;
            if (confirm('Are you sure you want to delete collection "' + name + '"?\nModels in this collection will NOT be deleted, only disassociated.')) {
                var input = document.getElementById('delete-collection-id-input');
                if (input) input.value = id;
                var form = document.getElementById('collection-delete-form');
                if (form) form.submit();
            }
        };

        // ---------------------------------------------------------------------
        // 3. Remove Model from Collection (AJAX)
        // ---------------------------------------------------------------------
        var modelsList = document.getElementById('collection-models-list');
        if (modelsList) {
            modelsList.addEventListener('click', function(e) {
                var btn = e.target.closest('.btn-remove-model');
                if (!btn) return;
                e.preventDefault();

                var modelId = btn.getAttribute('data-model-id');
                var modelName = btn.getAttribute('data-model-name') || 'this model';

                if (!confirm('Remove "' + modelName + '" from this collection?')) {
                    return;
                }

                btn.disabled = true;

                var formData = new FormData();
                formData.append('action', 'remove_model_ajax');
                formData.append('collection_id', currentCollectionId);
                formData.append('model_id', modelId);

                fetch('/collections', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        var itemEl = document.getElementById('model-item-' + modelId);
                        if (itemEl) {
                            itemEl.remove();
                        }
                        updateModelsCount();
                    } else {
                        alert(data.error || 'Failed to remove model.');
                        btn.disabled = false;
                    }
                })
                .catch(function(err) {
                    alert('Network error while removing model.');
                    btn.disabled = false;
                });
            });
        }

        function updateModelsCount() {
            var items = modelsList.querySelectorAll('.collection-model-item');
            var countEl = document.getElementById('collection-models-count');
            if (countEl) countEl.textContent = items.length;

            if (items.length === 0) {
                modelsList.innerHTML = '<div class="collection-models-empty" id="empty-collection-message">No models have been added to this collection yet.<br>Click <strong>"Add Models"</strong> above to select from your collection.</div>';
            }
        }

        // ---------------------------------------------------------------------
        // 4. Add Models Modal / Popup Flow
        // ---------------------------------------------------------------------
        var modalBackdrop = document.getElementById('add-models-backdrop');
        var btnOpenModal = document.getElementById('btn-open-add-models-modal');
        var btnCloseModal = document.getElementById('btn-close-modal');
        var btnCancelModal = document.getElementById('btn-cancel-modal');
        var btnSubmitAdd = document.getElementById('btn-submit-add-models');
        var searchInput = document.getElementById('modal-search-input');
        var filterBrand = document.getElementById('modal-filter-brand');
        var filterMaker = document.getElementById('modal-filter-maker');
        var btnResetFilters = document.getElementById('btn-modal-reset-filters');
        var gridContainer = document.getElementById('modal-models-grid');
        var paginationContainer = document.getElementById('modal-pagination');
        var selectedCountEl = document.getElementById('modal-selected-count');

        var selectedModelIds = new Set();
        var currentPage = 1;
        var searchTimer = null;

        function openModal() {
            if (!modalBackdrop) return;
            selectedModelIds.clear();
            updateSelectedSummary();
            currentPage = 1;
            modalBackdrop.classList.add('is-active');
            modalBackdrop.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            loadAvailableModels();
        }

        function closeModal() {
            if (!modalBackdrop) return;
            modalBackdrop.classList.remove('is-active');
            modalBackdrop.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (btnOpenModal) btnOpenModal.addEventListener('click', openModal);
        if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
        if (btnCancelModal) btnCancelModal.addEventListener('click', closeModal);

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === modalBackdrop) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('is-active')) {
                closeModal();
            }
        });

        // Filter event listeners
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    currentPage = 1;
                    loadAvailableModels();
                }, 250);
            });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    currentPage = 1;
                    loadAvailableModels();
                }
            });
        }

        if (filterBrand) {
            filterBrand.addEventListener('change', function() {
                currentPage = 1;
                loadAvailableModels();
            });
        }

        if (filterMaker) {
            filterMaker.addEventListener('change', function() {
                currentPage = 1;
                loadAvailableModels();
            });
        }

        if (btnResetFilters) {
            btnResetFilters.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (filterBrand) filterBrand.value = '';
                if (filterMaker) filterMaker.value = '';
                currentPage = 1;
                loadAvailableModels();
            });
        }

        function loadAvailableModels() {
            if (!gridContainer) return;
            gridContainer.innerHTML = '<div class="modal-empty-state"><p>Loading available models...</p></div>';

            var params = new URLSearchParams({
                action: 'get_available_models_ajax',
                collection_id: currentCollectionId,
                page: currentPage,
                search: searchInput ? searchInput.value.trim() : '',
                brand_id: filterBrand ? filterBrand.value : '',
                maker_id: filterMaker ? filterMaker.value : '',
            });

            fetch('/collections?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    renderAvailableGrid(data.items, data.total_items);
                    renderPagination(data.current_page, data.total_pages);
                } else {
                    gridContainer.innerHTML = '<div class="modal-empty-state"><p class="text-danger">' + (data.error || 'Failed to load models') + '</p></div>';
                }
            })
            .catch(function(err) {
                gridContainer.innerHTML = '<div class="modal-empty-state"><p class="text-danger">Failed to load models. Please try again.</p></div>';
            });
        }

        function renderAvailableGrid(items, totalItems) {
            if (!items || items.length === 0) {
                gridContainer.innerHTML = '<div class="modal-empty-state">' +
                    '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path><path d="M5 12h14"></path></svg>' +
                    '<p><strong>No available models found.</strong><br><span class="text-xs">All matching models are already in this collection or no models match your filter.</span></p>' +
                    '</div>';
                return;
            }

            var html = '';
            items.forEach(function(m) {
                var isSel = selectedModelIds.has(m.id);
                var imgHtml = m.image_path ? 
                    '<img src="' + escapeHtml(m.image_path) + '" alt="' + escapeHtml(m.model) + '" class="modal-model-card-img" loading="lazy">' :
                    '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path><path d="M5 12h14"></path></svg>';

                html += '<div class="modal-model-card ' + (isSel ? 'is-selected' : '') + '" data-id="' + m.id + '">' +
                    '<div class="modal-model-card-img-wrap">' +
                        imgHtml +
                        '<div class="modal-model-card-check">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                        '</div>' +
                    '</div>' +
                    '<div class="modal-model-card-body">' +
                        '<div class="modal-model-card-title" title="' + escapeHtml(m.model) + '">' + escapeHtml(m.model) + '</div>' +
                        '<div class="modal-model-card-meta">' +
                            '<span>' + escapeHtml(m.brand_name || 'Brand') + '</span>' +
                            (m.maker_name ? ' &bull; <span>' + escapeHtml(m.maker_name) + '</span>' : '') +
                            (m.scale_name ? ' &bull; <span>' + escapeHtml(m.scale_name) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });

            gridContainer.innerHTML = html;

            // Card click toggle
            var cards = gridContainer.querySelectorAll('.modal-model-card');
            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    var id = parseInt(card.getAttribute('data-id'), 10);
                    if (selectedModelIds.has(id)) {
                        selectedModelIds.delete(id);
                        card.classList.remove('is-selected');
                    } else {
                        selectedModelIds.add(id);
                        card.classList.add('is-selected');
                    }
                    updateSelectedSummary();
                });
            });
        }

        function updateSelectedSummary() {
            var count = selectedModelIds.size;
            if (selectedCountEl) selectedCountEl.textContent = count;
            if (btnSubmitAdd) {
                btnSubmitAdd.disabled = (count === 0);
            }
        }

        function renderPagination(page, totalPages) {
            if (!paginationContainer) return;
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            var html = '';
            // Prev
            html += '<button type="button" class="modal-page-btn" data-page="' + (page - 1) + '" ' + (page <= 1 ? 'disabled' : '') + '>&lsaquo;</button>';
            
            // Numeric pages (up to 5 buttons)
            var start = Math.max(1, page - 2);
            var end = Math.min(totalPages, start + 4);
            if (end - start < 4) {
                start = Math.max(1, end - 4);
            }

            for (var i = start; i <= end; i++) {
                html += '<button type="button" class="modal-page-btn ' + (i === page ? 'is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
            }

            // Next
            html += '<button type="button" class="modal-page-btn" data-page="' + (page + 1) + '" ' + (page >= totalPages ? 'disabled' : '') + '>&rsaquo;</button>';

            paginationContainer.innerHTML = html;

            var btns = paginationContainer.querySelectorAll('.modal-page-btn');
            btns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetPage = parseInt(btn.getAttribute('data-page'), 10);
                    if (targetPage >= 1 && targetPage <= totalPages && targetPage !== page) {
                        currentPage = targetPage;
                        loadAvailableModels();
                    }
                });
            });
        }

        // Submit Add Models to Collection
        if (btnSubmitAdd) {
            btnSubmitAdd.addEventListener('click', function() {
                if (selectedModelIds.size === 0) return;
                btnSubmitAdd.disabled = true;
                btnSubmitAdd.textContent = 'Adding...';

                var formData = new FormData();
                formData.append('action', 'add_models_ajax');
                formData.append('collection_id', currentCollectionId);
                selectedModelIds.forEach(function(id) {
                    formData.append('model_ids[]', id);
                });

                fetch('/collections', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        closeModal();
                        renderCollectionModels(data.models);
                    } else {
                        alert(data.error || 'Failed to add models.');
                        btnSubmitAdd.disabled = false;
                        btnSubmitAdd.textContent = 'Add to Collection';
                    }
                })
                .catch(function(err) {
                    alert('Network error while adding models to collection.');
                    btnSubmitAdd.disabled = false;
                    btnSubmitAdd.textContent = 'Add to Collection';
                });
            });
        }

        function renderCollectionModels(models) {
            if (!modelsList) return;
            if (!models || models.length === 0) {
                modelsList.innerHTML = '<div class="collection-models-empty" id="empty-collection-message">No models have been added to this collection yet.<br>Click <strong>"Add Models"</strong> above to select from your collection.</div>';
                var countEl = document.getElementById('collection-models-count');
                if (countEl) countEl.textContent = '0';
                return;
            }

            var html = '';
            models.forEach(function(m) {
                var imgHtml = m.image_path ?
                    '<img src="' + escapeHtml(m.image_path) + '" alt="' + escapeHtml(m.model) + '" class="collection-model-thumb" loading="lazy">' :
                    '<div class="collection-model-thumb-fallback"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path><path d="M5 12h14"></path></svg></div>';

                html += '<div class="collection-model-item" id="model-item-' + m.id + '">' +
                    '<div class="collection-model-item-info">' +
                        imgHtml +
                        '<div class="collection-model-texts">' +
                            '<span class="collection-model-name" title="' + escapeHtml(m.model) + '">' + escapeHtml(m.model) + '</span>' +
                            '<span class="collection-model-sub">' +
                                escapeHtml(m.brand_name || 'Brand') +
                                (m.maker_name ? ' &bull; ' + escapeHtml(m.maker_name) : '') +
                                (m.scale_name ? ' &bull; ' + escapeHtml(m.scale_name) : '') +
                            '</span>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="btn-remove-model" data-model-id="' + m.id + '" data-model-name="' + escapeHtml(m.model) + '" title="Remove from collection">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<polyline points="3 6 5 6 21 6"></polyline>' +
                            '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
                        '</svg>' +
                    '</button>' +
                '</div>';
            });

            modelsList.innerHTML = html;
            var countEl = document.getElementById('collection-models-count');
            if (countEl) countEl.textContent = models.length;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
    </script>

<?php require __DIR__ . '/layout/footer.php'; ?>
