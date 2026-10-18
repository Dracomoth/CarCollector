<?php
$pageTitle = 'Scales - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Scales Metadata</h1>
                <p class="text-muted">Manage standard scale ratios (1:8, 1:18, 1:64, etc.) used across your scale car collection.</p>
            </div>
            <div class="page-header-actions">
                <a href="/scales" class="btn btn-outline btn-sm scale-new-trigger" id="btn-header-new-scale">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Scale
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
            <!-- Left Column: Scale Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="brands-form-col">
                <div class="card brand-card">
                    <div class="card-header-row">
                        <div class="brand-card-header-info">
                            <h2 class="card-title" id="scale-form-title">
                                <?= !empty($selectedScale) ? 'Edit: ' . htmlspecialchars($selectedScale['scale'], ENT_QUOTES, 'UTF-8') : 'Scale Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title in small font size, date-only -->
                            <div class="brand-top-timestamps" id="scale-top-timestamps">
                                <span id="scale-top-timestamps-content">
                                    <?php if (!empty($selectedScale)): ?>
                                        <?php
                                        $createdDate = !empty($selectedScale['created']) ? substr((string)$selectedScale['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedScale['modified']) ? substr((string)$selectedScale['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="brand-timestamps-empty">New scale &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="scale-id-badge" style="<?= empty($selectedScale) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="scale-id-badge-text">#<?= !empty($selectedScale) ? (int)$selectedScale['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/scales" id="scale-main-form" class="form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="scale-id-input" value="<?= !empty($selectedScale) ? (int)$selectedScale['id'] : '' ?>">

                        <!-- Group Box: Scale Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Scale Information</legend>
                            
                            <!-- Scale Ratio -->
                            <div class="form-group">
                                <label for="scale-ratio">Scale Ratio <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="scale-ratio" 
                                       name="scale" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedScale['scale'] ?? $_POST['scale'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. 1:64, 1:18, 1:43, 1:24"
                                       pattern="^\d+:\d+$"
                                       title="Format ratio like 1:64, 1:18, 1:43"
                                       autocomplete="off"
                                       style="font-family: var(--font-heading); font-size: 1.1rem; letter-spacing: 0.05em; font-weight: 700;">
                                <span class="text-muted text-xs" style="display: block; margin-top: 0.35rem;">Standard ratio format (e.g. 1:64 for pocket scale, 1:18 for large display).</span>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-scale-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-scale-save-text"><?= !empty($selectedScale) ? 'Save Changes' : 'Save Scale' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/scales" class="btn btn-outline brand-sub-btn brand-new-btn scale-new-trigger" id="btn-form-new-scale" title="Clear form to create a new scale">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedScale) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-scale-delete"
                                        <?= empty($selectedScale) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedScale) ? 'Select a scale from the grid to delete' : 'Delete Scale' ?>"
                                        <?= !empty($selectedScale) ? 'onclick="handleDeleteScale(' . (int)$selectedScale['id'] . ', \'' . htmlspecialchars(addslashes($selectedScale['scale']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/scales" id="scale-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-scale-id-input" value="<?= !empty($selectedScale) ? (int)$selectedScale['id'] : '' ?>">
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
                            <h2 class="card-title">Scales Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and manage all registered model car scale ratios.</p>
                        </div>
                        <div class="brands-count-badge">
                            <span class="badge badge-primary"><?= count($allScales) ?> Total Scales</span>
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

    <!-- Client-side script for interactive scale selection & delete prompt -->
    <script>
        (function() {
            var idBadge = document.getElementById('scale-id-badge');
            var idBadgeText = document.getElementById('scale-id-badge-text');

            // Expose delete confirmation helper
            window.handleDeleteScale = function(scaleId, scaleRatio) {
                if (!scaleId) return;
                var msg = 'Are you sure you want to delete scale ratio "' + scaleRatio + '"?';
                if (confirm(msg)) {
                    var form = document.getElementById('scale-delete-form');
                    var idInput = document.getElementById('delete-scale-id-input');
                    if (form && idInput) {
                        idInput.value = scaleId;
                        form.submit();
                    }
                }
            };

            // -----------------------------------------------------------------
            // Instant Client-Side Row Selection (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function selectScale(scaleItem, rowEl) {
                if (!scaleItem || !scaleItem.id) return;

                // 1. Update Title
                var titleEl = document.getElementById('scale-form-title');
                if (titleEl) {
                    titleEl.textContent = 'Edit: ' + (scaleItem.scale || '');
                }

                // 2. Update Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('scale-top-timestamps');
                if (timestampsEl) {
                    var createdDate = (scaleItem.created || '').split(' ')[0] || (scaleItem.created || '').substring(0, 10);
                    var modifiedDate = (scaleItem.modified || '').split(' ')[0] || (scaleItem.modified || '').substring(0, 10);
                    timestampsEl.innerHTML = 'Created: ' + createdDate + ' &bull; Last Modified: ' + modifiedDate;
                    timestampsEl.style.display = 'block';
                }

                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + scaleItem.id;
                    idBadge.style.visibility = 'visible';
                }

                // 3. Update Form Inputs
                var idInput = document.getElementById('scale-id-input');
                if (idInput) idInput.value = scaleItem.id;

                var ratioInput = document.getElementById('scale-ratio');
                if (ratioInput) ratioInput.value = scaleItem.scale || '';

                // 4. Update Save Button Text
                var saveText = document.getElementById('btn-scale-save-text');
                if (saveText) saveText.textContent = 'Save Changes';

                // 5. Update Delete Button
                var deleteBtn = document.getElementById('btn-scale-delete');
                var deleteIdInput = document.getElementById('delete-scale-id-input');
                if (deleteIdInput) deleteIdInput.value = scaleItem.id;

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.remove('btn-outline', 'disabled');
                    deleteBtn.classList.add('btn-danger');
                    deleteBtn.setAttribute('title', 'Delete Scale ' + (scaleItem.scale || ''));
                    deleteBtn.onclick = function() {
                        handleDeleteScale(scaleItem.id, scaleItem.scale || '');
                    };
                }

                // 6. Update Grid Row Selection Highlighting
                var gridEl = document.getElementById('scales-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                    if (rowEl) {
                        rowEl.classList.add('is-selected');
                    } else {
                        var targetRow = gridEl.querySelector('tr.grid-data-row[data-id="' + scaleItem.id + '"]');
                        if (targetRow) targetRow.classList.add('is-selected');
                    }
                }

                // 7. Update browser address bar without reloading
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', scaleItem.id);
                    window.history.pushState({ scaleId: scaleItem.id }, '', currentUrl.pathname + currentUrl.search);
                }
            }

            // -----------------------------------------------------------------
            // Reset Form to New Scale (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function resetScaleForm() {
                // 1. Reset Title
                var titleEl = document.getElementById('scale-form-title');
                if (titleEl) titleEl.textContent = 'Scale Details';

                // 2. Reset Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('scale-top-timestamps');
                if (timestampsEl) {
                    timestampsEl.innerHTML = '<span class="brand-timestamps-empty">New scale &bull; Not saved yet</span>';
                    timestampsEl.style.display = 'block';
                }

                if (idBadge) {
                    idBadge.style.visibility = 'hidden';
                }

                // 3. Reset Form Inputs
                var idInput = document.getElementById('scale-id-input');
                if (idInput) idInput.value = '';

                var ratioInput = document.getElementById('scale-ratio');
                if (ratioInput) {
                    ratioInput.value = '';
                    ratioInput.focus();
                }

                // 4. Reset Save Button Text
                var saveText = document.getElementById('btn-scale-save-text');
                if (saveText) saveText.textContent = 'Save Scale';

                // 5. Reset Delete Button
                var deleteBtn = document.getElementById('btn-scale-delete');
                var deleteIdInput = document.getElementById('delete-scale-id-input');
                if (deleteIdInput) deleteIdInput.value = '';

                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline', 'disabled');
                    deleteBtn.setAttribute('title', 'Select a scale from the grid to delete');
                    deleteBtn.onclick = null;
                }

                // 6. Remove selection from all grid rows
                var gridEl = document.getElementById('scales-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                }

                // 7. Update URL to /scales without page reload
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('edit');
                    window.history.pushState({}, '', currentUrl.pathname + (currentUrl.search ? currentUrl.search : ''));
                }
            }

            // -----------------------------------------------------------------
            // Listen to Grid Row Selection Event from GridController
            // -----------------------------------------------------------------
            var gridContainer = document.getElementById('scales-grid');
            if (gridContainer) {
                gridContainer.addEventListener('grid:row-select', function(e) {
                    e.preventDefault();
                    var scaleData = e.detail.data;
                    selectScale(scaleData, e.detail.row);
                });
            }

            // Wire all "New Scale" buttons to client-side reset
            document.querySelectorAll('.scale-new-trigger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetScaleForm();
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
                            var scaleData = JSON.parse(targetRow.getAttribute('data-row') || '{}');
                            selectScale(scaleData, targetRow);
                            return;
                        } catch(err) {}
                    }
                }
                resetScaleForm();
            });
        })();
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
