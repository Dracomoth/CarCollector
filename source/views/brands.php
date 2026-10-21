<?php
$pageTitle = 'Brands - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Brands Catalog</h1>
                <p class="text-muted">Manage car manufacturers, diecast brands, descriptions, and official brand logos.</p>
            </div>
            <div class="page-header-actions">
                <a href="/brands" class="btn btn-outline btn-sm brand-new-trigger" id="btn-header-new-brand">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Brand
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
        <div class="brands-layout">
            <!-- --------------------------------------------------------------- -->
            <!-- Left Column: Brand Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="brands-form-col">
                <div class="card brand-card">
                    <div class="card-header-row">
                        <div class="brand-card-header-info">
                            <h2 class="card-title" id="brand-form-title">
                                <?= !empty($selectedBrand) ? 'Edit: ' . htmlspecialchars($selectedBrand['name'], ENT_QUOTES, 'UTF-8') : 'Brand Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title in small font size -->
                            <div class="brand-top-timestamps" id="brand-top-timestamps">
                                <span id="brand-top-timestamps-content">
                                    <?php if (!empty($selectedBrand)): ?>
                                        <?php
                                        $createdDate = !empty($selectedBrand['created']) ? substr((string)$selectedBrand['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedBrand['modified']) ? substr((string)$selectedBrand['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="brand-timestamps-empty">New brand &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="brand-id-badge" style="<?= empty($selectedBrand) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="brand-id-badge-text">#<?= !empty($selectedBrand) ? (int)$selectedBrand['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/brands" enctype="multipart/form-data" id="brand-main-form" class="form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="brand-id-input" value="<?= !empty($selectedBrand) ? (int)$selectedBrand['id'] : '' ?>">

                        <!-- Group Box 1: Brand Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Brand Information</legend>
                            
                            <!-- Brand Name -->
                            <div class="form-group">
                                <label for="brand-name">Brand Name <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="brand-name" 
                                       name="name" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedBrand['name'] ?? $_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Porsche, Ferrari, Hot Wheels">
                            </div>

                            <!-- Brand Description (Higher fixed-height textbox) -->
                            <div class="form-group">
                                <label for="brand-description">Description</label>
                                <textarea id="brand-description" 
                                          name="description" 
                                          rows="4" 
                                          class="form-control"
                                          placeholder="Brand background, origin, scale notes..."><?= htmlspecialchars($selectedBrand['description'] ?? $_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </fieldset>

                        <!-- Group Box 2: Brand Logo -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Brand Logo</legend>

                            <div class="form-group">
                                <div class="brand-logo-dropzone" id="brand-logo-dropzone" tabindex="0" role="button" aria-label="Upload Brand Logo">
                                    <input type="file" 
                                           id="brand-logo-input" 
                                           name="logo" 
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif" 
                                           class="brand-logo-hidden-input">
                                    
                                    <div class="brand-logo-view" id="brand-logo-view">
                                        <!-- Always present placeholder with icon -->
                                        <div class="brand-logo-placeholder" id="brand-logo-placeholder" style="<?= !empty($selectedBrand['logo_path']) ? 'display: none;' : '' ?>">
                                            <svg class="brand-logo-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <span class="brand-logo-upload-title">Upload Brand Logo</span>
                                            <span class="brand-logo-upload-sub">PNG, JPG, WEBP, SVG &bull; Click to Browse</span>
                                        </div>

                                        <!-- Image preview container with fixed dimensions -->
                                        <div class="brand-logo-current-wrap" id="current-logo-wrap" style="<?= empty($selectedBrand['logo_path']) ? 'display: none;' : '' ?>">
                                            <img src="<?= htmlspecialchars(ImageHelper::versioned($selectedBrand['logo_path'] ?? '', $selectedBrand['modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" 
                                                 alt="Brand Logo" 
                                                 class="brand-logo-preview-image" 
                                                 id="brand-logo-preview-img">
                                        </div>
                                        <span class="brand-logo-hint" id="brand-logo-hint" style="<?= empty($selectedBrand['logo_path']) ? 'display: none;' : '' ?>">Click or drop to replace logo</span>
                                    </div>
                                </div>

                                <!-- Hidden input for removal flag on submit -->
                                <input type="hidden" name="remove_logo" id="brand-remove-logo-input" value="0">

                                <!-- Browse and Remove Action Buttons (replacing removal checkbox) -->
                                <div class="brand-logo-btn-row">
                                    <button type="button" class="btn brand-media-btn brand-browse-btn" id="btn-browse-logo">
                                        Browse
                                    </button>
                                    <button type="button" 
                                            class="btn brand-media-btn brand-remove-btn <?= empty($selectedBrand['logo_path']) ? 'is-disabled' : '' ?>" 
                                            id="btn-remove-logo" 
                                            <?= empty($selectedBrand['logo_path']) ? 'disabled' : '' ?>>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-brand-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-brand-save-text"><?= !empty($selectedBrand) ? 'Save Changes' : 'Save Brand' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/brands" class="btn btn-outline brand-sub-btn brand-new-btn brand-new-trigger" id="btn-form-new-brand" title="Clear form to create a new brand">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedBrand) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-brand-delete"
                                        <?= empty($selectedBrand) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedBrand) ? 'Select a brand from the grid to delete' : 'Delete Brand' ?>"
                                        <?= !empty($selectedBrand) ? 'onclick="handleDeleteBrand(' . (int)$selectedBrand['id'] . ', \'' . htmlspecialchars(addslashes($selectedBrand['name']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/brands" id="brand-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-brand-id-input" value="<?= !empty($selectedBrand) ? (int)$selectedBrand['id'] : '' ?>">
                    </form>
                </div>
            </aside>

            <!-- --------------------------------------------------------------- -->
            <!-- Right Column: Reusable Grid Controller Presentation -->
            <!-- --------------------------------------------------------------- -->
            <section class="brands-grid-col">
                <div class="card brands-grid-card">
                    <div class="card-header-row">
                        <div>
                            <h2 class="card-title">Brands Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and paginate through all registered car brands.</p>
                        </div>
                        <div class="brands-count-badge">
                            <span class="badge badge-primary"><?= count($allBrands) ?> Total Brands</span>
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

    <!-- Client-side script for interactive brand selection, logo preview & delete prompt -->
    <script>
        (function() {
            var dropzone = document.getElementById('brand-logo-dropzone');
            var fileInput = document.getElementById('brand-logo-input');
            var previewImg = document.getElementById('brand-logo-preview-img');
            var currentWrap = document.getElementById('current-logo-wrap');
            var placeholder = document.getElementById('brand-logo-placeholder');
            var hint = document.getElementById('brand-logo-hint');
            var removeInput = document.getElementById('brand-remove-logo-input');
            var browseBtn = document.getElementById('btn-browse-logo');
            var removeBtn = document.getElementById('btn-remove-logo');
            var idBadge = document.getElementById('brand-id-badge');
            var idBadgeText = document.getElementById('brand-id-badge-text');

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
                    var files = dt.files;
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
                    if (hint) hint.style.display = 'none';
                    removeBtn.disabled = true;
                    removeBtn.classList.add('is-disabled');
                });
            }

            function showPreview(file) {
                if (!file.type.match('image.*')) {
                    alert('Please select an image file (PNG, JPG, WEBP, SVG).');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg) {
                        previewImg.src = e.target.result;
                    }
                    if (currentWrap) {
                        currentWrap.style.display = 'flex';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    if (hint) {
                        hint.style.display = 'block';
                        hint.textContent = 'Selected: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    }
                    if (removeInput) {
                        removeInput.value = '0';
                    }
                    if (removeBtn) {
                        removeBtn.disabled = false;
                        removeBtn.classList.remove('is-disabled');
                    }
                };
                reader.readAsDataURL(file);
            }

            // Expose delete confirmation helper
            window.handleDeleteBrand = function(brandId, brandName) {
                if (!brandId) return;
                var msg = 'Are you sure you want to permanently delete brand "' + brandName + '" and its logo?';
                if (confirm(msg)) {
                    var form = document.getElementById('brand-delete-form');
                    var idInput = document.getElementById('delete-brand-id-input');
                    if (form && idInput) {
                        idInput.value = brandId;
                        form.submit();
                    }
                }
            };

            // -----------------------------------------------------------------
            // Instant Client-Side Row Selection (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function selectBrand(brand, rowEl) {
                if (!brand || !brand.id) return;

                // 1. Update Title
                var titleEl = document.getElementById('brand-form-title');
                if (titleEl) {
                    titleEl.textContent = 'Edit: ' + (brand.name || '');
                }

                // 2. Update Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('brand-top-timestamps');
                if (timestampsEl) {
                    var createdDate = (brand.created || '').split(' ')[0] || (brand.created || '').substring(0, 10);
                    var modifiedDate = (brand.modified || '').split(' ')[0] || (brand.modified || '').substring(0, 10);
                    timestampsEl.innerHTML = 'Created: ' + createdDate + ' &bull; Last Modified: ' + modifiedDate;
                    timestampsEl.style.display = 'block';
                }

                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + brand.id;
                    idBadge.style.visibility = 'visible';
                }

                // 3. Update Form Inputs
                var idInput = document.getElementById('brand-id-input');
                if (idInput) idInput.value = brand.id;

                var nameInput = document.getElementById('brand-name');
                if (nameInput) nameInput.value = brand.name || '';

                var descInput = document.getElementById('brand-description');
                if (descInput) descInput.value = brand.description || '';

                // 4. Reset File Input & Remove flag
                if (fileInput) fileInput.value = '';
                if (removeInput) removeInput.value = '0';

                // 5. Update Logo Preview & Remove button state
                if (brand.logo_path) {
                    if (previewImg) previewImg.src = getVersionedImageUrl(brand.logo_path, brand.modified);
                    if (currentWrap) currentWrap.style.display = 'flex';
                    if (placeholder) placeholder.style.display = 'none';
                    if (hint) {
                        hint.style.display = 'block';
                        hint.textContent = 'Click or drop to replace logo';
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

                // 6. Update Save Button Text
                var saveText = document.getElementById('btn-brand-save-text');
                if (saveText) saveText.textContent = 'Save Changes';

                // 7. Update Delete Button
                var deleteBtn = document.getElementById('btn-brand-delete');
                var deleteIdInput = document.getElementById('delete-brand-id-input');
                if (deleteIdInput) deleteIdInput.value = brand.id;

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.remove('btn-outline', 'disabled');
                    deleteBtn.classList.add('btn-danger');
                    deleteBtn.setAttribute('title', 'Delete ' + (brand.name || 'Brand'));
                    deleteBtn.onclick = function() {
                        handleDeleteBrand(brand.id, brand.name || '');
                    };
                }

                // 8. Update Grid Row Selection Highlighting
                var gridEl = document.getElementById('brands-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                    if (rowEl) {
                        rowEl.classList.add('is-selected');
                    } else {
                        var targetRow = gridEl.querySelector('tr.grid-data-row[data-id="' + brand.id + '"]');
                        if (targetRow) targetRow.classList.add('is-selected');
                    }
                }

                // 9. Update browser address bar without reloading
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', brand.id);
                    window.history.pushState({ brandId: brand.id }, '', currentUrl.pathname + currentUrl.search);
                }
            }

            // -----------------------------------------------------------------
            // Reset Form to New Brand (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function resetBrandForm() {
                // 1. Reset Title
                var titleEl = document.getElementById('brand-form-title');
                if (titleEl) titleEl.textContent = 'Brand Details';

                // 2. Reset Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('brand-top-timestamps');
                if (timestampsEl) {
                    timestampsEl.innerHTML = '<span class="brand-timestamps-empty">New brand &bull; Not saved yet</span>';
                    timestampsEl.style.display = 'block';
                }

                if (idBadge) {
                    idBadge.style.visibility = 'hidden';
                }

                // 3. Reset Form Inputs
                var idInput = document.getElementById('brand-id-input');
                if (idInput) idInput.value = '';

                var nameInput = document.getElementById('brand-name');
                if (nameInput) {
                    nameInput.value = '';
                    nameInput.focus();
                }

                var descInput = document.getElementById('brand-description');
                if (descInput) descInput.value = '';

                // 4. Reset File Input & Logo Dropzone
                if (fileInput) fileInput.value = '';
                if (removeInput) removeInput.value = '0';
                if (previewImg) previewImg.src = '';
                if (currentWrap) currentWrap.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                if (hint) hint.style.display = 'none';
                if (removeBtn) {
                    removeBtn.disabled = true;
                    removeBtn.classList.add('is-disabled');
                }

                // 5. Reset Save Button Text
                var saveText = document.getElementById('btn-brand-save-text');
                if (saveText) saveText.textContent = 'Save Brand';

                // 6. Reset Delete Button
                var deleteBtn = document.getElementById('btn-brand-delete');
                var deleteIdInput = document.getElementById('delete-brand-id-input');
                if (deleteIdInput) deleteIdInput.value = '';

                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline', 'disabled');
                    deleteBtn.setAttribute('title', 'Select a brand from the grid to delete');
                    deleteBtn.onclick = null;
                }

                // 7. Remove selection from all grid rows
                var gridEl = document.getElementById('brands-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                }

                // 8. Update URL to /brands without page reload
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('edit');
                    window.history.pushState({}, '', currentUrl.pathname + (currentUrl.search ? currentUrl.search : ''));
                }
            }

            // -----------------------------------------------------------------
            // Listen to Grid Row Selection Event from GridController
            // -----------------------------------------------------------------
            var gridContainer = document.getElementById('brands-grid');
            if (gridContainer) {
                gridContainer.addEventListener('grid:row-select', function(e) {
                    // Prevent default full page navigation!
                    e.preventDefault();
                    var brandData = e.detail.data;
                    selectBrand(brandData, e.detail.row);
                });
            }

            // Wire all "New Brand" buttons to client-side reset
            document.querySelectorAll('.brand-new-trigger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetBrandForm();
                });
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function(e) {
                var params = new URLSearchParams(window.location.search);
                var editId = params.get('edit');
                if (editId && gridContainer) {
                    var targetRow = gridContainer.querySelector('tr.grid-data-row[data-id="' + editId + '"]');
                    if (targetRow) {
                        try {
                            var brandData = JSON.parse(targetRow.getAttribute('data-row') || '{}');
                            selectBrand(brandData, targetRow);
                            return;
                        } catch(err) {}
                    }
                }
                resetBrandForm();
            });
        })();
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
