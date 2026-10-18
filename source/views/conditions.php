<?php
$pageTitle = 'Conditions - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Conditions Metadata</h1>
                <p class="text-muted">Manage car model preservation conditions (Junkyard, Scratches, Mint Loose, MIB, MOMC).</p>
            </div>
            <div class="page-header-actions">
                <a href="/conditions" class="btn btn-outline btn-sm condition-new-trigger" id="btn-header-new-condition">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Condition
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
            <!-- Left Column: Condition Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="brands-form-col">
                <div class="card brand-card">
                    <div class="card-header-row">
                        <div class="brand-card-header-info">
                            <h2 class="card-title" id="condition-form-title">
                                <?= !empty($selectedCondition) ? 'Edit: ' . htmlspecialchars($selectedCondition['name'], ENT_QUOTES, 'UTF-8') : 'Condition Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title in small font size, date-only -->
                            <div class="brand-top-timestamps" id="condition-top-timestamps">
                                <span id="condition-top-timestamps-content">
                                    <?php if (!empty($selectedCondition)): ?>
                                        <?php
                                        $createdDate = !empty($selectedCondition['created']) ? substr((string)$selectedCondition['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedCondition['modified']) ? substr((string)$selectedCondition['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="brand-timestamps-empty">New condition &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="condition-id-badge" style="<?= empty($selectedCondition) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="condition-id-badge-text">#<?= !empty($selectedCondition) ? (int)$selectedCondition['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/conditions" id="condition-main-form" class="form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="condition-id-input" value="<?= !empty($selectedCondition) ? (int)$selectedCondition['id'] : '' ?>">

                        <!-- Group Box: Condition Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Condition Information</legend>
                            
                            <!-- Condition Name -->
                            <div class="form-group">
                                <label for="condition-name">Condition Text <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="condition-name" 
                                       name="name" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedCondition['name'] ?? $_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Mint In Box (MIB), Mint Loose, Junkyard"
                                       autocomplete="off"
                                       style="font-size: 0.95rem; font-weight: 600;">
                                <span class="text-muted text-xs" style="display: block; margin-top: 0.35rem;">Collector condition grading (e.g. MIB, MOMC, Mint Loose, Junkyard).</span>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-condition-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-condition-save-text"><?= !empty($selectedCondition) ? 'Save Changes' : 'Save Condition' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/conditions" class="btn btn-outline brand-sub-btn brand-new-btn condition-new-trigger" id="btn-form-new-condition" title="Clear form to create a new condition">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedCondition) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-condition-delete"
                                        <?= empty($selectedCondition) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedCondition) ? 'Select a condition from the grid to delete' : 'Delete Condition' ?>"
                                        <?= !empty($selectedCondition) ? 'onclick="handleDeleteCondition(' . (int)$selectedCondition['id'] . ', \'' . htmlspecialchars(addslashes($selectedCondition['name']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/conditions" id="condition-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-condition-id-input" value="<?= !empty($selectedCondition) ? (int)$selectedCondition['id'] : '' ?>">
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
                            <h2 class="card-title">Conditions Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and manage all registered model preservation conditions.</p>
                        </div>
                        <div class="brands-count-badge">
                            <span class="badge badge-primary"><?= count($allConditions) ?> Total Conditions</span>
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

    <!-- Client-side script for interactive condition selection & delete prompt -->
    <script>
        (function() {
            var idBadge = document.getElementById('condition-id-badge');
            var idBadgeText = document.getElementById('condition-id-badge-text');

            // Expose delete confirmation helper
            window.handleDeleteCondition = function(conditionId, conditionName) {
                if (!conditionId) return;
                var msg = 'Are you sure you want to delete condition "' + conditionName + '"?';
                if (confirm(msg)) {
                    var form = document.getElementById('condition-delete-form');
                    var idInput = document.getElementById('delete-condition-id-input');
                    if (form && idInput) {
                        idInput.value = conditionId;
                        form.submit();
                    }
                }
            };

            // -----------------------------------------------------------------
            // Instant Client-Side Row Selection (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function selectCondition(item, rowEl) {
                if (!item || !item.id) return;

                // 1. Update Title
                var titleEl = document.getElementById('condition-form-title');
                if (titleEl) {
                    titleEl.textContent = 'Edit: ' + (item.name || '');
                }

                // 2. Update Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('condition-top-timestamps');
                if (timestampsEl) {
                    var createdDate = (item.created || '').split(' ')[0] || (item.created || '').substring(0, 10);
                    var modifiedDate = (item.modified || '').split(' ')[0] || (item.modified || '').substring(0, 10);
                    timestampsEl.innerHTML = 'Created: ' + createdDate + ' &bull; Last Modified: ' + modifiedDate;
                    timestampsEl.style.display = 'block';
                }

                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + item.id;
                    idBadge.style.visibility = 'visible';
                }

                // 3. Update Form Inputs
                var idInput = document.getElementById('condition-id-input');
                if (idInput) idInput.value = item.id;

                var nameInput = document.getElementById('condition-name');
                if (nameInput) nameInput.value = item.name || '';

                // 4. Update Save Button Text
                var saveText = document.getElementById('btn-condition-save-text');
                if (saveText) saveText.textContent = 'Save Changes';

                // 5. Update Delete Button
                var deleteBtn = document.getElementById('btn-condition-delete');
                var deleteIdInput = document.getElementById('delete-condition-id-input');
                if (deleteIdInput) deleteIdInput.value = item.id;

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.remove('btn-outline', 'disabled');
                    deleteBtn.classList.add('btn-danger');
                    deleteBtn.setAttribute('title', 'Delete Condition ' + (item.name || ''));
                    deleteBtn.onclick = function() {
                        handleDeleteCondition(item.id, item.name || '');
                    };
                }

                // 6. Update Grid Row Selection Highlighting
                var gridEl = document.getElementById('conditions-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                    if (rowEl) {
                        rowEl.classList.add('is-selected');
                    } else {
                        var targetRow = gridEl.querySelector('tr.grid-data-row[data-id="' + item.id + '"]');
                        if (targetRow) targetRow.classList.add('is-selected');
                    }
                }

                // 7. Update browser address bar without reloading
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', item.id);
                    window.history.pushState({ conditionId: item.id }, '', currentUrl.pathname + currentUrl.search);
                }
            }

            // -----------------------------------------------------------------
            // Reset Form to New Condition (NO FULL PAGE RELOAD)
            // -----------------------------------------------------------------
            function resetConditionForm() {
                // 1. Reset Title
                var titleEl = document.getElementById('condition-form-title');
                if (titleEl) titleEl.textContent = 'Condition Details';

                // 2. Reset Top Timestamps & ID Badge
                var timestampsEl = document.getElementById('condition-top-timestamps');
                if (timestampsEl) {
                    timestampsEl.innerHTML = '<span class="brand-timestamps-empty">New condition &bull; Not saved yet</span>';
                    timestampsEl.style.display = 'block';
                }

                if (idBadge) {
                    idBadge.style.visibility = 'hidden';
                }

                // 3. Reset Form Inputs
                var idInput = document.getElementById('condition-id-input');
                if (idInput) idInput.value = '';

                var nameInput = document.getElementById('condition-name');
                if (nameInput) {
                    nameInput.value = '';
                    nameInput.focus();
                }

                // 4. Reset Save Button Text
                var saveText = document.getElementById('btn-condition-save-text');
                if (saveText) saveText.textContent = 'Save Condition';

                // 5. Reset Delete Button
                var deleteBtn = document.getElementById('btn-condition-delete');
                var deleteIdInput = document.getElementById('delete-condition-id-input');
                if (deleteIdInput) deleteIdInput.value = '';

                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-outline', 'disabled');
                    deleteBtn.setAttribute('title', 'Select a condition from the grid to delete');
                    deleteBtn.onclick = null;
                }

                // 6. Remove selection from all grid rows
                var gridEl = document.getElementById('conditions-grid');
                if (gridEl) {
                    gridEl.querySelectorAll('tr.grid-data-row').forEach(function(r) {
                        r.classList.remove('is-selected');
                    });
                }

                // 7. Update URL to /conditions without page reload
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('edit');
                    window.history.pushState({}, '', currentUrl.pathname + (currentUrl.search ? currentUrl.search : ''));
                }
            }

            // -----------------------------------------------------------------
            // Listen to Grid Row Selection Event from GridController
            // -----------------------------------------------------------------
            var gridContainer = document.getElementById('conditions-grid');
            if (gridContainer) {
                gridContainer.addEventListener('grid:row-select', function(e) {
                    e.preventDefault();
                    var condData = e.detail.data;
                    selectCondition(condData, e.detail.row);
                });
            }

            // Wire all "New Condition" buttons to client-side reset
            document.querySelectorAll('.condition-new-trigger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetConditionForm();
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
                            var condData = JSON.parse(targetRow.getAttribute('data-row') || '{}');
                            selectCondition(condData, targetRow);
                            return;
                        } catch(err) {}
                    }
                }
                resetConditionForm();
            });
        })();
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
