<?php
$pageTitle = 'Colors - CarCollector';
$currentColorHex = !empty($selectedColor['hex_code']) ? $selectedColor['hex_code'] : ($_POST['hex_code'] ?? '#FFFFFF');
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <!-- Page Title Header -->
        <div class="page-header">
            <div>
                <h1>Colors Metadata</h1>
                <p class="text-muted">Manage car model paint colors and accurate hex codes for catalog categorization.</p>
            </div>
            <div class="page-header-actions">
                <a href="/colors" class="btn btn-outline btn-sm color-new-trigger" id="btn-header-new-color">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Color
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
            <!-- Left Column: Color Edit / Create Form -->
            <!-- --------------------------------------------------------------- -->
            <aside class="brands-form-col">
                <div class="card brand-card">
                    <div class="card-header-row">
                        <div class="brand-card-header-info">
                            <h2 class="card-title" id="color-form-title">
                                <?= !empty($selectedColor) ? 'Edit: ' . htmlspecialchars($selectedColor['name'], ENT_QUOTES, 'UTF-8') : 'Color Details' ?>
                            </h2>
                            <!-- Timestamps directly under the title in small font size, date-only -->
                            <div class="brand-top-timestamps" id="color-top-timestamps">
                                <span id="color-top-timestamps-content">
                                    <?php if (!empty($selectedColor)): ?>
                                        <?php
                                        $createdDate = !empty($selectedColor['created']) ? substr((string)$selectedColor['created'], 0, 10) : '';
                                        $modifiedDate = !empty($selectedColor['modified']) ? substr((string)$selectedColor['modified'], 0, 10) : '';
                                        ?>
                                        Created: <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?> &bull; Last Modified: <?= htmlspecialchars($modifiedDate, ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="brand-timestamps-empty">New color &bull; Not saved yet</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div id="color-id-badge" style="<?= empty($selectedColor) ? 'visibility: hidden;' : '' ?>">
                            <span class="badge badge-primary text-xs" id="color-id-badge-text">#<?= !empty($selectedColor) ? (int)$selectedColor['id'] : '0' ?></span>
                        </div>
                    </div>

                    <form method="POST" action="/colors" id="color-main-form" class="form brand-form">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="color-id-input" value="<?= !empty($selectedColor) ? (int)$selectedColor['id'] : '' ?>">

                        <!-- Group Box: Color Information -->
                        <fieldset class="form-group-box">
                            <legend class="form-group-legend">Color Information</legend>
                            
                            <!-- Color Name -->
                            <div class="form-group">
                                <label for="color-name">Color Name <span class="required text-danger">*</span></label>
                                <input type="text" 
                                       id="color-name" 
                                       name="name" 
                                       required 
                                       class="form-control"
                                       value="<?= htmlspecialchars($selectedColor['name'] ?? $_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="e.g. Red, Candy Apple, Charcoal, Gold"
                                       autocomplete="off"
                                       style="font-size: 0.95rem; font-weight: 600;">
                                <span class="text-muted text-xs" style="display: block; margin-top: 0.35rem;">Collector model car paint or trim color name.</span>
                            </div>

                            <!-- Hex Code & Color Picker -->
                            <div class="form-group" style="margin-top: 1rem;">
                                <label for="color-hex">Hex Code <span class="required text-danger">*</span></label>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                                    <!-- Interactive Color Swatch Preview Button -->
                                    <button type="button" 
                                            id="btn-color-swatch-preview"
                                            title="Click to open color picker popup"
                                            aria-label="Color Preview Swatch"
                                            style="width: 42px; height: 38px; border-radius: 6px; border: 2px solid rgba(255, 255, 255, 0.25); background-color: <?= htmlspecialchars($currentColorHex, ENT_QUOTES, 'UTF-8') ?>; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.4); padding: 0; transition: transform 0.15s ease, border-color 0.15s ease;">
                                    </button>

                                    <!-- Direct Hex Code Textbox Input -->
                                    <div style="position: relative; flex: 1;">
                                        <input type="text" 
                                               id="color-hex" 
                                               name="hex_code" 
                                               required 
                                               maxlength="7"
                                               class="form-control"
                                               value="<?= htmlspecialchars($currentColorHex, ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="#FF0000"
                                               autocomplete="off"
                                               style="font-family: var(--font-mono, monospace); font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    </div>

                                    <!-- Color Picker Button -->
                                    <button type="button" 
                                            class="btn btn-outline" 
                                            id="btn-open-picker"
                                            title="Open color picker popup with HEX selection"
                                            style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 0.85rem; height: 38px; flex-shrink: 0;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
                                            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
                                            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
                                            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
                                            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.12a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.56-2.5 5.56-5.55C22 6.01 17.5 2 12 2z"></path>
                                        </svg>
                                        <span>Pick Color</span>
                                    </button>
                                </div>
                                <span class="text-muted text-xs" style="display: block; margin-top: 0.35rem;">Write a hex code directly (e.g. #FF0000) or click 'Pick Color' to open the color picker popup.</span>
                            </div>
                        </fieldset>

                        <!-- Form Actions: Save button on top, New and Delete buttons underneath -->
                        <div class="brand-action-buttons">
                            <!-- 1. Primary Save Button -->
                            <button type="submit" class="btn btn-primary btn-block brand-save-btn" id="btn-color-save">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span id="btn-color-save-text"><?= !empty($selectedColor) ? 'Save Changes' : 'Save Color' ?></span>
                            </button>

                            <!-- 2. Under it: New and Delete buttons -->
                            <div class="brand-sub-actions">
                                <a href="/colors" class="btn btn-outline brand-sub-btn brand-new-btn color-new-trigger" id="btn-form-new-color" title="Clear form to create a new color">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    New
                                </a>

                                <button type="button" 
                                        class="btn brand-sub-btn brand-delete-btn <?= empty($selectedColor) ? 'btn-outline disabled' : 'btn-danger' ?>" 
                                        id="btn-color-delete"
                                        <?= empty($selectedColor) ? 'disabled' : '' ?>
                                        title="<?= empty($selectedColor) ? 'Select a color from the grid to delete' : 'Delete Color' ?>"
                                        <?= !empty($selectedColor) ? 'onclick="handleDeleteColor(' . (int)$selectedColor['id'] . ', \'' . htmlspecialchars(addslashes($selectedColor['name']), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
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
                    <form method="POST" action="/colors" id="color-delete-form" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-color-id-input" value="<?= !empty($selectedColor) ? (int)$selectedColor['id'] : '' ?>">
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
                            <h2 class="card-title">Colors Directory</h2>
                            <p class="text-muted text-sm">Filter, sort, and manage all registered model car colors.</p>
                        </div>
                        <div class="brands-count-badge">
                            <span class="badge badge-primary"><?= count($allColors) ?> Total Colors</span>
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

    <!-- ======================================================================= -->
    <!-- Custom Color Picker Modal Dialog (HEX by default + Accept/Cancel)       -->
    <!-- ======================================================================= -->
    <div class="color-picker-modal-backdrop" id="color-picker-modal" aria-hidden="true" style="display: none;">
        <div class="color-picker-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="color-picker-modal-title">
            <div class="color-picker-modal-header">
                <div class="color-picker-modal-title-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary, #e10600);">
                        <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
                        <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
                        <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
                        <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.12a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.56-2.5 5.56-5.55C22 6.01 17.5 2 12 2z"></path>
                    </svg>
                    <h3 id="color-picker-modal-title" class="color-picker-modal-title">Color Picker</h3>
                </div>
                <button type="button" class="color-picker-modal-close" id="btn-modal-close" aria-label="Close color picker" title="Cancel and close">&times;</button>
            </div>

            <div class="color-picker-modal-body">
                <!-- 1. HEX Input Section (Front and Center by default) -->
                <div class="color-picker-hex-row">
                    <div class="color-picker-hex-input-group">
                        <label for="modal-hex-input" class="color-picker-field-label">HEX Color Code</label>
                        <div class="color-picker-hex-field-wrap">
                            <span class="color-picker-hex-hash">#</span>
                            <input type="text" 
                                   id="modal-hex-input" 
                                   maxlength="6" 
                                   class="form-control color-picker-hex-input" 
                                   placeholder="FF0000"
                                   autocomplete="off"
                                   spellcheck="false">
                        </div>
                    </div>

                    <!-- Side-by-side Swatch Comparison: Original vs New -->
                    <div class="color-picker-preview-box">
                        <div class="color-picker-preview-col">
                            <span class="color-picker-preview-label">Original</span>
                            <div class="color-picker-preview-swatch" id="modal-swatch-original" title="Original Color"></div>
                        </div>
                        <div class="color-picker-preview-col">
                            <span class="color-picker-preview-label">New</span>
                            <div class="color-picker-preview-swatch" id="modal-swatch-new" title="New Selected Color"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. Visual 2D Saturation / Value Gradient Canvas -->
                <div class="color-picker-canvas-wrap" id="canvas-container">
                    <canvas id="color-picker-canvas" width="330" height="135" class="color-picker-canvas"></canvas>
                    <div class="color-picker-cursor" id="color-picker-cursor"></div>
                </div>

                <!-- 3. Hue Rainbow Spectrum Slider -->
                <div class="color-picker-slider-wrap">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <span class="color-picker-field-label" style="margin-bottom: 0;">Hue Spectrum</span>
                        <span id="modal-hue-degrees" class="text-muted text-xs" style="font-family: var(--font-mono, monospace);">0°</span>
                    </div>
                    <input type="range" id="color-picker-hue" min="0" max="360" value="0" class="color-picker-hue-slider" aria-label="Hue Spectrum Slider">
                </div>

                <!-- 4. Quick Collector Standard Palettes -->
                <div class="color-picker-presets-wrap">
                    <span class="color-picker-field-label">Collector Paint Presets (Quick Select)</span>
                    <div class="color-picker-presets-grid" id="color-picker-presets">
                        <!-- Populated dynamically with the 21 standard car collector colors -->
                    </div>
                </div>
            </div>

            <!-- 5. Modal Footer: Cancel and Accept Buttons -->
            <div class="color-picker-modal-footer">
                <button type="button" class="btn btn-outline" id="btn-modal-cancel" title="Discard changes and keep original color">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btn-modal-accept" title="Apply this color to the form">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Accept
                </button>
            </div>
        </div>
    </div>

    <!-- Modal CSS Styles -->
    <style>
    /* Color Picker Modal Backdrop */
    .color-picker-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(9, 12, 16, 0.82);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        animation: fadeInModal 0.16s ease-out;
    }

    @keyframes fadeInModal {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .color-picker-modal-dialog {
        background: var(--color-surface, #121620);
        border: 1px solid var(--color-border, #222a38);
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.08);
        width: 100%;
        max-width: 380px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: scaleInModal 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes scaleInModal {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .color-picker-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid var(--color-border, #222a38);
        background: var(--color-surface-elevated, #181d2a);
    }

    .color-picker-modal-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .color-picker-modal-title {
        font-family: var(--font-heading, sans-serif);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--color-text, #f1f5f9);
        margin: 0;
        letter-spacing: 0.5px;
    }

    .color-picker-modal-close {
        background: transparent;
        border: none;
        color: var(--color-text-muted, #8b99ae);
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        transition: color 0.15s ease, background 0.15s ease;
    }

    .color-picker-modal-close:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.1);
    }

    .color-picker-modal-body {
        padding: 1.15rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }

    /* HEX Row */
    .color-picker-hex-row {
        display: flex;
        gap: 0.85rem;
        align-items: flex-end;
    }

    .color-picker-hex-input-group {
        flex: 1;
    }

    .color-picker-field-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--color-text-muted, #8b99ae);
        margin-bottom: 0.35rem;
    }

    .color-picker-hex-field-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .color-picker-hex-hash {
        position: absolute;
        left: 0.85rem;
        font-family: var(--font-mono, monospace);
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--color-primary, #e10600);
        pointer-events: none;
        user-select: none;
    }

    .color-picker-hex-input {
        padding-left: 2rem !important;
        font-family: var(--font-mono, monospace) !important;
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        letter-spacing: 1px !important;
        text-transform: uppercase !important;
        height: 42px !important;
    }

    .color-picker-preview-box {
        display: flex;
        gap: 0.45rem;
    }

    .color-picker-preview-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.2rem;
    }

    .color-picker-preview-label {
        font-size: 0.65rem;
        color: var(--color-text-dim, #546274);
        text-transform: uppercase;
        font-weight: 600;
    }

    .color-picker-preview-swatch {
        width: 44px;
        height: 42px;
        border-radius: 6px;
        border: 2px solid rgba(255, 255, 255, 0.25);
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.4), 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    /* 2D Canvas Spectrum */
    .color-picker-canvas-wrap {
        position: relative;
        width: 100%;
        height: 135px;
        border-radius: 8px;
        overflow: hidden;
        cursor: crosshair;
        user-select: none;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15), 0 2px 6px rgba(0, 0, 0, 0.4);
    }

    .color-picker-canvas {
        width: 100%;
        height: 100%;
        display: block;
    }

    .color-picker-cursor {
        position: absolute;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        box-shadow: 0 0 3px rgba(0, 0, 0, 0.9), inset 0 0 2px rgba(0, 0, 0, 0.8);
        pointer-events: none;
        transform: translate(-50%, -50%);
        top: 0;
        left: 0;
    }

    /* Hue Slider */
    .color-picker-slider-wrap {
        display: flex;
        flex-direction: column;
    }

    .color-picker-hue-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 13px;
        border-radius: 7px;
        background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
        outline: none;
        cursor: pointer;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
    }

    .color-picker-hue-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #121620;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.7);
        cursor: pointer;
        transition: transform 0.1s ease;
    }

    .color-picker-hue-slider::-webkit-slider-thumb:hover {
        transform: scale(1.15);
    }

    .color-picker-hue-slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #121620;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.7);
        cursor: pointer;
    }

    /* Presets Grid */
    .color-picker-presets-wrap {
        display: flex;
        flex-direction: column;
    }

    .color-picker-presets-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.35rem;
    }

    .color-picker-preset-item {
        aspect-ratio: 1;
        border-radius: 5px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        cursor: pointer;
        transition: transform 0.12s ease, border-color 0.12s ease;
        padding: 0;
    }

    .color-picker-preset-item:hover {
        transform: scale(1.18);
        border-color: #ffffff;
        z-index: 2;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
    }

    /* Modal Footer */
    .color-picker-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-top: 1px solid var(--color-border, #222a38);
        background: var(--color-surface-elevated, #181d2a);
    }
    </style>

    <!-- Client-Side Instant Selection & Custom Color Picker Modal Script -->
    <script>
    (function() {
        // Main Form Elements cache
        var formTitle = document.getElementById('color-form-title');
        var idBadge = document.getElementById('color-id-badge');
        var idBadgeText = document.getElementById('color-id-badge-text');
        var timestampsContent = document.getElementById('color-top-timestamps-content');
        var colorIdInput = document.getElementById('color-id-input');
        var colorNameInput = document.getElementById('color-name');
        var colorHexInput = document.getElementById('color-hex');
        var colorSwatchBtn = document.getElementById('btn-color-swatch-preview');
        var btnOpenPicker = document.getElementById('btn-open-picker');
        var saveBtnText = document.getElementById('btn-color-save-text');
        var deleteBtn = document.getElementById('btn-color-delete');
        var deleteIdInput = document.getElementById('delete-color-id-input');
        var formCard = document.querySelector('.brand-card');

        // Modal Elements cache
        var modalBackdrop = document.getElementById('color-picker-modal');
        var btnModalClose = document.getElementById('btn-modal-close');
        var btnModalCancel = document.getElementById('btn-modal-cancel');
        var btnModalAccept = document.getElementById('btn-modal-accept');
        var modalHexInput = document.getElementById('modal-hex-input');
        var modalSwatchOriginal = document.getElementById('modal-swatch-original');
        var modalSwatchNew = document.getElementById('modal-swatch-new');
        var canvasContainer = document.getElementById('canvas-container');
        var canvas = document.getElementById('color-picker-canvas');
        var cursor = document.getElementById('color-picker-cursor');
        var hueSlider = document.getElementById('color-picker-hue');
        var hueDegreesText = document.getElementById('modal-hue-degrees');
        var presetsContainer = document.getElementById('color-picker-presets');

        // State variables
        var originalColorHex = '#FFFFFF';
        var currentColorHex = '#FFFFFF';
        var currentH = 0;
        var currentS = 1;
        var currentV = 1;
        var isDraggingCanvas = false;

        // 21 Standard Collector Colors for Quick Presets
        var PRESETS = [
            { name: 'White', hex: '#FFFFFF' },
            { name: 'Silver', hex: '#C0C0C0' },
            { name: 'Chrome', hex: '#E5E4E2' },
            { name: 'Grey', hex: '#808080' },
            { name: 'Charcoal', hex: '#36454F' },
            { name: 'Black', hex: '#000000' },
            { name: 'Brown', hex: '#8B4513' },
            { name: 'Tan', hex: '#D2B48C' },
            { name: 'Gold', hex: '#FFD700' },
            { name: 'Yellow', hex: '#FFFF00' },
            { name: 'Orange', hex: '#FFA500' },
            { name: 'Red', hex: '#FF0000' },
            { name: 'Burgundy', hex: '#800020' },
            { name: 'Purple', hex: '#800080' },
            { name: 'Pink', hex: '#FFC0CB' },
            { name: 'Navy', hex: '#000080' },
            { name: 'Blue', hex: '#0000FF' },
            { name: 'Teal', hex: '#008080' },
            { name: 'Lime', hex: '#00FF00' },
            { name: 'Green', hex: '#008000' },
            { name: 'Olive', hex: '#808000' }
        ];

        // Populate Presets Grid
        if (presetsContainer) {
            presetsContainer.innerHTML = '';
            PRESETS.forEach(function(item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'color-picker-preset-item';
                btn.style.backgroundColor = item.hex;
                btn.title = item.name + ' (' + item.hex + ')';
                btn.setAttribute('aria-label', item.name);
                btn.addEventListener('click', function() {
                    applyNewHex(item.hex, true);
                });
                presetsContainer.appendChild(btn);
            });
        }

        // =====================================================================
        // Color Math Utility Functions
        // =====================================================================
        function cleanHex(hex) {
            if (!hex) return '#FFFFFF';
            hex = hex.trim();
            if (hex.charAt(0) !== '#') hex = '#' + hex;
            if (hex.length === 4) {
                hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
            }
            return hex.toUpperCase();
        }

        function isValidHex(hex) {
            return /^#[0-9A-F]{6}$/i.test(hex);
        }

        function hexToRgb(hex) {
            hex = cleanHex(hex).replace('#', '');
            var num = parseInt(hex, 16);
            return {
                r: (num >> 16) & 255,
                g: (num >> 8) & 255,
                b: num & 255
            };
        }

        function rgbToHex(r, g, b) {
            var clamp = function(v) { return Math.max(0, Math.min(255, Math.round(v))); };
            var rH = clamp(r).toString(16).padStart(2, '0');
            var gH = clamp(g).toString(16).padStart(2, '0');
            var bH = clamp(b).toString(16).padStart(2, '0');
            return ('#' + rH + gH + bH).toUpperCase();
        }

        function rgbToHsv(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            var max = Math.max(r, g, b), min = Math.min(r, g, b);
            var h, s, v = max;
            var d = max - min;
            s = max === 0 ? 0 : d / max;
            if (max === min) {
                h = 0;
            } else {
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }
            return { h: Math.round(h * 360), s: s, v: v };
        }

        function hsvToRgb(h, s, v) {
            var r, g, b;
            var i = Math.floor((h / 360) * 6);
            var f = (h / 360) * 6 - i;
            var p = v * (1 - s);
            var q = v * (1 - f * s);
            var t = v * (1 - (1 - f) * s);
            switch (i % 6) {
                case 0: r = v; g = t; b = p; break;
                case 1: r = q; g = v; b = p; break;
                case 2: r = p; g = v; b = t; break;
                case 3: r = p; g = q; b = v; break;
                case 4: r = t; g = p; b = v; break;
                case 5: r = v; g = p; b = q; break;
            }
            return { r: r * 255, g: g * 255, b: b * 255 };
        }

        // =====================================================================
        // Canvas & Spectrum Rendering
        // =====================================================================
        function drawCanvas(hue) {
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var width = canvas.width;
            var height = canvas.height;

            // 1. Pure Hue background
            ctx.fillStyle = 'hsl(' + hue + ', 100%, 50%)';
            ctx.fillRect(0, 0, width, height);

            // 2. Horizontal White Gradient (left = 100% white, right = transparent)
            var gradWhite = ctx.createLinearGradient(0, 0, width, 0);
            gradWhite.addColorStop(0, 'rgba(255, 255, 255, 1)');
            gradWhite.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctx.fillStyle = gradWhite;
            ctx.fillRect(0, 0, width, height);

            // 3. Vertical Black Gradient (top = transparent, bottom = 100% black)
            var gradBlack = ctx.createLinearGradient(0, 0, 0, height);
            gradBlack.addColorStop(0, 'rgba(0, 0, 0, 0)');
            gradBlack.addColorStop(1, 'rgba(0, 0, 0, 1)');
            ctx.fillStyle = gradBlack;
            ctx.fillRect(0, 0, width, height);
        }

        function updateCursorPosition() {
            if (!canvas || !cursor) return;
            var rect = canvas.getBoundingClientRect();
            var x = currentS * rect.width;
            var y = (1 - currentV) * rect.height;
            cursor.style.left = Math.round(x) + 'px';
            cursor.style.top = Math.round(y) + 'px';
        }

        function updateColorFromHSV() {
            var rgb = hsvToRgb(currentH, currentS, currentV);
            var hex = rgbToHex(rgb.r, rgb.g, rgb.b);
            currentColorHex = hex;

            if (modalHexInput) {
                modalHexInput.value = hex.replace('#', '');
            }
            if (modalSwatchNew) {
                modalSwatchNew.style.backgroundColor = hex;
            }
            updateCursorPosition();
        }

        function applyNewHex(hex, updateInputs) {
            hex = cleanHex(hex);
            if (!isValidHex(hex)) return;
            currentColorHex = hex;

            var rgb = hexToRgb(hex);
            var hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            currentH = hsv.h;
            currentS = hsv.s;
            currentV = hsv.v;

            if (hueSlider) hueSlider.value = currentH;
            if (hueDegreesText) hueDegreesText.textContent = currentH + '°';

            drawCanvas(currentH);
            updateCursorPosition();

            if (updateInputs && modalHexInput) {
                modalHexInput.value = hex.replace('#', '');
            }
            if (modalSwatchNew) {
                modalSwatchNew.style.backgroundColor = hex;
            }
        }

        function handleCanvasDrag(e) {
            if (!canvas) return;
            var rect = canvas.getBoundingClientRect();
            var clientX = e.touches ? e.touches[0].clientX : e.clientX;
            var clientY = e.touches ? e.touches[0].clientY : e.clientY;

            var x = Math.max(0, Math.min(rect.width, clientX - rect.left));
            var y = Math.max(0, Math.min(rect.height, clientY - rect.top));

            currentS = x / rect.width;
            currentV = 1 - (y / rect.height);

            updateColorFromHSV();
        }

        // Canvas mouse and touch interactions
        if (canvasContainer) {
            canvasContainer.addEventListener('mousedown', function(e) {
                isDraggingCanvas = true;
                handleCanvasDrag(e);
            });
            window.addEventListener('mousemove', function(e) {
                if (isDraggingCanvas) {
                    handleCanvasDrag(e);
                }
            });
            window.addEventListener('mouseup', function() {
                isDraggingCanvas = false;
            });

            // Touch events
            canvasContainer.addEventListener('touchstart', function(e) {
                isDraggingCanvas = true;
                handleCanvasDrag(e);
            }, { passive: true });
            window.addEventListener('touchmove', function(e) {
                if (isDraggingCanvas) {
                    handleCanvasDrag(e);
                }
            }, { passive: true });
            window.addEventListener('touchend', function() {
                isDraggingCanvas = false;
            });
        }

        // Hue Slider change
        if (hueSlider) {
            hueSlider.addEventListener('input', function() {
                currentH = parseInt(this.value, 10) || 0;
                if (hueDegreesText) hueDegreesText.textContent = currentH + '°';
                drawCanvas(currentH);
                updateColorFromHSV();
            });
        }

        // Modal HEX Input direct typing
        if (modalHexInput) {
            modalHexInput.addEventListener('input', function() {
                var clean = this.value.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
                this.value = clean;
                if (clean.length === 6) {
                    applyNewHex('#' + clean, false);
                }
            });
            modalHexInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    acceptColorModal();
                }
            });
        }

        // =====================================================================
        // Modal Open, Accept & Cancel Handlers
        // =====================================================================
        function openColorModal() {
            originalColorHex = cleanHex(colorHexInput ? colorHexInput.value : '#FFFFFF');
            currentColorHex = originalColorHex;

            if (modalSwatchOriginal) modalSwatchOriginal.style.backgroundColor = originalColorHex;
            if (modalSwatchNew) modalSwatchNew.style.backgroundColor = originalColorHex;

            // Show modal
            if (modalBackdrop) {
                modalBackdrop.style.display = 'flex';
                modalBackdrop.setAttribute('aria-hidden', 'false');
            }

            // Sync visual controls with original hex
            applyNewHex(originalColorHex, true);

            // Focus and select the HEX text input by default as requested
            setTimeout(function() {
                if (modalHexInput) {
                    modalHexInput.focus();
                    modalHexInput.select();
                }
            }, 60);
        }

        function closeColorModal() {
            if (modalBackdrop) {
                modalBackdrop.style.display = 'none';
                modalBackdrop.setAttribute('aria-hidden', 'true');
            }
        }

        function acceptColorModal() {
            var chosenHex = cleanHex('#' + (modalHexInput ? modalHexInput.value : currentColorHex.replace('#', '')));
            if (!isValidHex(chosenHex)) {
                chosenHex = currentColorHex;
            }

            // Write to main form controls
            if (colorHexInput) {
                colorHexInput.value = chosenHex;
            }
            if (colorSwatchBtn) {
                colorSwatchBtn.style.backgroundColor = chosenHex;
            }

            closeColorModal();
        }

        function cancelColorModal() {
            // Restore original hex without changes
            closeColorModal();
        }

        // Hook up open triggers
        if (btnOpenPicker) {
            btnOpenPicker.addEventListener('click', function(e) {
                e.preventDefault();
                openColorModal();
            });
        }
        if (colorSwatchBtn) {
            colorSwatchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openColorModal();
            });
        }

        // Hook up modal action buttons
        if (btnModalAccept) {
            btnModalAccept.addEventListener('click', acceptColorModal);
        }
        if (btnModalCancel) {
            btnModalCancel.addEventListener('click', cancelColorModal);
        }
        if (btnModalClose) {
            btnModalClose.addEventListener('click', cancelColorModal);
        }

        // Close on clicking backdrop outside dialog
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === modalBackdrop) {
                    cancelColorModal();
                }
            });
        }

        // ESC key to cancel
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.style.display === 'flex') {
                cancelColorModal();
            }
        });

        // Direct typing in main form hex input
        if (colorHexInput) {
            colorHexInput.addEventListener('input', function() {
                var val = cleanHex(this.value);
                if (isValidHex(val) && colorSwatchBtn) {
                    colorSwatchBtn.style.backgroundColor = val;
                }
            });
            colorHexInput.addEventListener('blur', function() {
                var val = cleanHex(this.value);
                if (isValidHex(val)) {
                    this.value = val;
                    if (colorSwatchBtn) colorSwatchBtn.style.backgroundColor = val;
                }
            });
        }

        // =====================================================================
        // Client-side Grid Row Selection without full page reload
        // =====================================================================
        var gridContainer = document.getElementById('colors-grid');
        if (gridContainer) {
            gridContainer.addEventListener('grid:row-select', function(e) {
                var rowData = e.detail && e.detail.row;
                if (!rowData) return;

                // 1. Update Form Title
                if (formTitle) {
                    formTitle.textContent = 'Edit: ' + (rowData.name || '');
                }

                // 2. Update ID Badge
                if (idBadge && idBadgeText) {
                    idBadgeText.textContent = '#' + rowData.id;
                    idBadge.style.visibility = 'visible';
                }

                // 3. Update Timestamps (date-only)
                if (timestampsContent) {
                    var created = (rowData.created || '').substring(0, 10);
                    var modified = (rowData.modified || '').substring(0, 10);
                    if (created || modified) {
                        timestampsContent.innerHTML = 'Created: ' + escapeHtml(created) + ' &bull; Last Modified: ' + escapeHtml(modified);
                    } else {
                        timestampsContent.innerHTML = '<span class="brand-timestamps-empty">New color &bull; Not saved yet</span>';
                    }
                }

                // 4. Fill Inputs
                if (colorIdInput) colorIdInput.value = rowData.id;
                if (colorNameInput) colorNameInput.value = rowData.name || '';
                var hex = cleanHex(rowData.hex_code || '#FFFFFF');
                if (colorHexInput) colorHexInput.value = hex;
                if (colorSwatchBtn) colorSwatchBtn.style.backgroundColor = hex;

                // 5. Update Action Buttons
                if (saveBtnText) {
                    saveBtnText.textContent = 'Save Changes';
                }

                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.className = 'btn brand-sub-btn brand-delete-btn btn-danger';
                    deleteBtn.title = 'Delete Color';
                    deleteBtn.onclick = function() {
                        handleDeleteColor(rowData.id, rowData.name || '');
                    };
                }

                if (deleteIdInput) {
                    deleteIdInput.value = rowData.id;
                }

                // 6. Update URL in browser history without page reload
                if (window.history && window.history.pushState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('edit', rowData.id);
                    window.history.pushState({ colorId: rowData.id }, '', currentUrl.toString());
                }

                // 7. Visual Feedback pulse
                if (formCard) {
                    formCard.classList.remove('form-card-highlight');
                    void formCard.offsetWidth; // trigger reflow
                    formCard.classList.add('form-card-highlight');
                }
            });
        }

        // =====================================================================
        // Handle "New Color" triggers without reload
        // =====================================================================
        function resetColorForm() {
            if (formTitle) formTitle.textContent = 'Color Details';
            if (idBadge) idBadge.style.visibility = 'hidden';
            if (timestampsContent) {
                timestampsContent.innerHTML = '<span class="brand-timestamps-empty">New color &bull; Not saved yet</span>';
            }
            if (colorIdInput) colorIdInput.value = '';
            if (colorNameInput) {
                colorNameInput.value = '';
                colorNameInput.focus();
            }
            var defaultHex = '#FFFFFF';
            if (colorHexInput) colorHexInput.value = defaultHex;
            if (colorSwatchBtn) colorSwatchBtn.style.backgroundColor = defaultHex;

            if (saveBtnText) saveBtnText.textContent = 'Save Color';

            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.className = 'btn brand-sub-btn brand-delete-btn btn-outline disabled';
                deleteBtn.title = 'Select a color from the grid to delete';
                deleteBtn.onclick = null;
            }
            if (deleteIdInput) deleteIdInput.value = '';

            // Deselect active row in the grid
            var activeRows = document.querySelectorAll('#colors-grid tr.is-selected');
            activeRows.forEach(function(row) {
                row.classList.remove('is-selected');
            });

            // Update URL to /colors without page reload
            if (window.history && window.history.pushState) {
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('edit');
                window.history.pushState({}, '', currentUrl.toString());
            }

            if (formCard) {
                formCard.classList.remove('form-card-highlight');
                void formCard.offsetWidth;
                formCard.classList.add('form-card-highlight');
            }
        }

        var newTriggers = document.querySelectorAll('.color-new-trigger');
        newTriggers.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                resetColorForm();
            });
        });

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    })();

    // Global Delete Confirmation Handler
    function handleDeleteColor(id, name) {
        if (!id) return;
        var message = "Are you sure you want to delete the color '" + name + "' (#" + id + ")?\nThis action cannot be undone.";
        if (confirm(message)) {
            var deleteForm = document.getElementById('color-delete-form');
            var deleteIdInput = document.getElementById('delete-color-id-input');
            if (deleteForm && deleteIdInput) {
                deleteIdInput.value = id;
                deleteForm.submit();
            }
        }
    }
    </script>

<?php
require __DIR__ . '/layout/footer.php';
?>
