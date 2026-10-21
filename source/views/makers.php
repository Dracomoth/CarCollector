<?php
$pageTitle = 'Makers - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Makers Catalog</h1>
                <p class="text-muted">Manage model car manufacturers, diecast makers, descriptions, and official maker logos.</p>
            </div>
            <div class="page-header-actions">
                <a href="/makers" class="btn btn-outline btn-sm maker-new-trigger" id="btn-header-new-maker">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Maker
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
        <div class="makers-layout brands-layout">
            <!-- --------------------------------------------------------------- -->
            <!-- Left Column: Maker Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="makers-form-col brands-form-col">
                <div class="card maker-card brand-card">
                    <div class="card-header-row">
                        <div class="maker-card-header-info brand-card-header-info">
                            <h2 class="card-title" id="maker-form-title">
                                <?= !empty($selectedMaker) ? 'Edit: ' . htmlspecialchars($selectedMaker['name'], ENT_QUOTES, 'UTF-8') : 'Maker Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title in small font size (date-only) -->
                            <div class="maker-top-timestamps brand-top-timestamps" id="maker-top-timestamps">
                                <span id="maker-top-timestamps-content">
                                    <?php if (!empty($selectedMaker)): ?>
                                        <?php
                                        $createdDate = !empty($selectedMaker['created']) ? substr((string)$selectedMaker['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedMaker['modified']) ? substr((string)$selectedMaker['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="maker-timestamps-empty brand-timestamps-empty">New maker &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="maker-id-badge" style="<?= empty($selectedMaker) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="maker-id-badge-text">#<?= !empty($selectedMaker) ? (int)$selectedMaker['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/makers" enctype="multipart/form-data" id="maker-main-form" class="form maker-form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="maker-id-input" value="<?= !empty($selectedMaker) ? (int)$selectedMaker['id'] : '' ?>">

                        <!-- Group Box 1: Maker Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Maker Information</legend>
                            
                            <!-- Maker Name -->
                            <div class="form-group">
                                <label for="maker-name">Maker Name <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="maker-name" 
                                       name="name" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedMaker['name'] ?? $_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Hot Wheels, Matchbox, Maisto, AutoArt">
                            </div>

                            <!-- Maker Description (Higher fixed-height textbox) -->
                            <div class="form-group">
                                <label for="maker-description">Description</label>
                                <textarea id="maker-description" 
                                          name="description" 
                                          rows="4" 
                                          class="form-control"
                                          placeholder="Maker background, origin, scale notes, casting quality..."><?= htmlspecialchars($selectedMaker['description'] ?? $_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </fieldset>

                        <!-- Group Box 2: Maker Logo -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Maker Logo</legend>

                            <div class="form-group">
                                <div class="maker-logo-dropzone brand-logo-dropzone" id="maker-logo-dropzone" tabindex="0" role="button" aria-label="Upload Maker Logo">
                                    <input type="file" 
                                           id="maker-logo-input" 
                                           name="logo" 
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif" 
                                           class="maker-logo-hidden-input brand-logo-hidden-input">
                                    
                                    <div class="maker-logo-view brand-logo-view" id="maker-logo-view">
                                        <!-- Always present placeholder with icon -->
                                        <div class="maker-logo-placeholder brand-logo-placeholder" id="maker-logo-placeholder" style="<?= !empty($selectedMaker['logo_path']) ? 'display: none;' : '' ?>">
                                            <svg class="maker-logo-icon brand-logo-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            <span class="maker-logo-upload-title brand-logo-upload-title">Upload Maker Logo</span>
                                            <span class="maker-logo-upload-sub brand-logo-upload-sub">PNG, JPG, WEBP, SVG &bull; Click to Browse</span>
                                        </div>

                                        <!-- Image preview container with fixed dimensions -->
                                        <div class="maker-logo-current-wrap brand-logo-current-wrap" id="current-logo-wrap" style="<?= empty($selectedMaker['logo_path']) ? 'display: none;' : '' ?>">
                                            <img src="<?= htmlspecialchars(ImageHelper::versioned($selectedMaker['logo_path'] ?? '', $selectedMaker['modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" 
                                                 alt="Maker Logo" 
                                                 class="maker-logo-preview-image brand-logo-preview-image" 
                                                 id="maker-logo-preview-img">
                                        </div>
                                        <span class="maker-logo-hint brand-logo-hint" id="maker-logo-hint" style="<?= empty($selectedMaker['logo_path']) ? 'display: none;' : '' ?>">Click or drop to replace logo</span>
                                    </div>
                                </div>

                                <!-- Hidden input for removal flag on submit -->
                                <input type="hidden" name="remove_logo" id="maker-remove-logo-input" value="0">

                                <!-- Browse and Remove Action Buttons -->
                                <div class="maker-logo-btn-row brand-logo-btn-row">
                                    <button type="button" class="btn brand-media-btn brand-browse-btn" id="btn-browse-logo">
                                        Browse
                                    </button>
                                    <button type="button" 
                                            class="btn brand-media-btn brand-remove-btn <?= empty($selectedMaker['logo_path']) ? 'is-disabled' : '' ?>" 
                                            id="btn-remove-logo" 
                                            <?= empty($selectedMaker['logo_path']) ? 'disabled' : '' ?>>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="maker-action-buttons brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block maker-save-btn brand-save-btn" id="btn-maker-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-maker-save-text"><?= !empty($selectedMaker) ? 'Save Changes' : 'Save Maker' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="maker-sub-actions brand-sub-actions">
                                <a href="/makers" class="btn btn-outline brand-sub-btn brand-new-btn maker-new-trigger" id="btn-form-new-maker" title="Clear form to create a new maker">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedMaker) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-maker-delete"
                                        <?= empty($selectedMaker) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedMaker) ? 'Select a maker from the grid to delete' : 'Delete Maker' ?>"
                                        <?= !empty($selectedMaker) ? 'onclick="handleDeleteMaker(' . (int)$selectedMaker['id'] . ', \'' . htmlspecialchars(addslashes($selectedMaker['name']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/makers" id="maker-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-maker-id-input" value="<?= !empty($selectedMaker) ? (int)$selectedMaker['id'] : '' ?>">
                    </form>
                </div>
            </aside>

            <!-- --------------------------------------------------------------- -->
            <!-- Right Column: Reusable Grid Controller Presentation -->
            <!-- --------------------------------------------------------------- -->
            <section class="makers-grid-col brands-grid-col">
                <div class="card makers-grid-card brands-grid-card">
                    <div class="card-header-row">
                        <div>
                            <h2 class="card-title">Makers Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and paginate through all registered model car manufacturers / makers.</p>
                        </div>
                        <div class="makers-count-badge brands-count-badge">
                            <span class="badge badge-primary"><?= count($allMakers) ?> Total Makers</span>
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

    <!-- Client-side script for interactive maker selection, logo preview & delete prompt -->
    <script>
        (function() {
            var dropzone = document.getElementById('maker-logo-dropzone');
            var fileInput = document.getElementById('maker-logo-input');
            var previewImg = document.getElementById('maker-logo-preview-img');
            var currentWrap = document.getElementById('current-logo-wrap');
            var placeholder = document.getElementById('maker-logo-placeholder');
            var hint = document.getElementById('maker-logo-hint');
            var removeInput = document.getElementById('maker-remove-logo-input');
            var browseBtn = document.getElementById('btn-browse-logo');
            var removeBtn = document.getElementById('btn-remove-logo');
            var idBadge = document.getElementById('maker-id-badge');
            var idBadgeText = document.getElementById('maker-id-badge-text');

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
            window.handleDeleteMaker = function(makerId, makerName) {
                if (!makerId) return;
                var msg = 'Are you sure you want to permanently delete maker "' + makerName + '" and its logo?';
                if (confirm(msg)) {
                    var form = document.getElementById('maker-delete-form');
                    var idInput = document.getElementById('delete-maker-id-input');
                    if (form && idInput) {
                        idInput.value = makerId;
                        form.submit();
                    }
                }
            };

            // -----------------------------------------------------------------
            // Instant Client-Side Row Selection (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function selectMaker(maker, rowEl) {
                if (!maker || !maker.id) return;

                // 1. Update Title
                var titleEl = document.getElementById('maker-form-title');
                if (titleEl) {
                    titleEl.textContent = 'Edit: ' + (maker.name || '');
                }

                // 2. Update Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('maker-top-timestamps');
                if (timestampsEl) {
                    var createdDate = (maker.created || '').split(' ')[0] || (maker.created || '').substring(0, 10);
                    var modifiedDate = (maker.modified || '').split(' ')[0] || (maker.modified || '').substring(0, 10);
                    timestampsEl.innerHTML = 'Created: ' + createdDate + ' &bull; Last Modified: ' + modifiedDate;
                    timestampsEl.style.display = 'block';
                }

                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + maker.id;
                    idBadge.style.visibility = 'visible';
                }

                // 3. Update Form Inputs
                var idInput = document.getElementById('maker-id-input');
                if (idInput) idInput.value = maker.id;

                var nameInput = document.getElementById('maker-name');
                if (nameInput) nameInput.value = maker.name || '';

                var descInput = document.getElementById('maker-description');
                if (descInput) descInput.value = maker.description || '';

                // 4. Reset File Input & Remove flag
                if (fileInput) fileInput.value = '';
                if (removeInput) removeInput.value = '0';

                // 5. Update Logo Preview & Remove button state
                if (maker.logo_path) {
                    if (previewImg) previewImg.src = getVersionedImageUrl(maker.logo_path, maker.modified);
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
                var saveText = document.getElementById('btn-maker-save-text');
                if (saveText) saveText.textContent = 'Save Changes';

                // 7. Update Delete Button
                var deleteBtn = document.getElementById('btn-maker-delete');
                var deleteIdInput = document.getElementById('delete-maker-id-input');
                if (deleteIdInput) deleteIdInput.value = maker.id;

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.remove('btn-outline', 'disabled');
                    deleteBtn.classList.add('btn-danger');
                    deleteBtn.setAttribute('title', 'Delete ' + (maker.name || 'Maker'));
                    deleteBtn.onclick = function() {
                        handleDeleteMaker(maker.id, maker.name || '');
                    };
                }

                // 8. Update Grid Row Selection Highlighting
                var gridEl = document.getElementById('makers-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                    if (rowEl) {
                        rowEl.classList.add('is-selected');
                    } else {
                        var targetRow = gridEl.querySelector('tr.grid-data-row[data-id="' + maker.id + '"]');
                        if (targetRow) targetRow.classList.add('is-selected');
                    }
                }

                // 9. Update browser address bar without reloading
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', maker.id);
                    window.history.pushState({ makerId: maker.id }, '', currentUrl.pathname + currentUrl.search);
                }
            }

            // -----------------------------------------------------------------
            // Reset Form to New Maker (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function resetMakerForm() {
                // 1. Reset Title
                var titleEl = document.getElementById('maker-form-title');
                if (titleEl) titleEl.textContent = 'Maker Details';

                // 2. Reset Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('maker-top-timestamps');
                if (timestampsEl) {
                    timestampsEl.innerHTML = '<span class="maker-timestamps-empty brand-timestamps-empty">New maker &bull; Not saved yet</span>';
                    timestampsEl.style.display = 'block';
                }

                if (idBadge) {
                    idBadge.style.visibility = 'hidden';
                }

                // 3. Reset Form Inputs
                var idInput = document.getElementById('maker-id-input');
                if (idInput) idInput.value = '';

                var nameInput = document.getElementById('maker-name');
                if (nameInput) {
                    nameInput.value = '';
                    nameInput.focus();
                }

                var descInput = document.getElementById('maker-description');
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
                var saveText = document.getElementById('btn-maker-save-text');
                if (saveText) saveText.textContent = 'Save Maker';

                // 6. Reset Delete Button
                var deleteBtn = document.getElementById('btn-maker-delete');
                var deleteIdInput = document.getElementById('delete-maker-id-input');
                if (deleteIdInput) deleteIdInput.value = '';

                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline', 'disabled');
                    deleteBtn.setAttribute('title', 'Select a maker from the grid to delete');
                    deleteBtn.onclick = null;
                }

                // 7. Remove selection from all grid rows
                var gridEl = document.getElementById('makers-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                }

                // 8. Update URL to /makers without page reload
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('edit');
                    window.history.pushState({}, '', currentUrl.pathname + (currentUrl.search ? currentUrl.search : ''));
                }
            }

            // -----------------------------------------------------------------
            // Listen to Grid Row Selection Event from GridController
            // -----------------------------------------------------------------
            var gridContainer = document.getElementById('makers-grid');
            if (gridContainer) {
                gridContainer.addEventListener('grid:row-select', function(e) {
                    // Prevent default full page navigation!
                    e.preventDefault();
                    var makerData = e.detail.data;
                    selectMaker(makerData, e.detail.row);
                });
            }

            // Wire all "New Maker" buttons to client-side reset
            document.querySelectorAll('.maker-new-trigger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetMakerForm();
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
                            var makerData = JSON.parse(targetRow.getAttribute('data-row') || '{}');
                            selectMaker(makerData, targetRow);
                            return;
                        } catch(err) {}
                    }
                }
                resetMakerForm();
            });
        })();
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
