<?php
/**
 * Garage Showcase View
 * 
 * Interactive gallery for the user's scale car collection:
 * - Real-time client-side & server-side filter toolbar
 * - Sticky Alphabet selector with dynamic item counts and greyed-out empty letters
 * - Dual Mode: List Mode (responsive cards grid) and Detail Mode (gallery showcase)
 * - Sorting toggle (Ascending / Descending)
 * - Markdown-enabled notes and description
 */

$pageTitle = 'Garage - Scale Car Showcase - CarCollector';
require __DIR__ . '/layout/header.php';

// Prepare models data for client-side instant filtering and transitions
$modelsJson = json_encode($allModels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
$filterOptionsJson = json_encode($filterOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<main class="container page-content garage-page">

    <!-- ======================================================================= -->
    <!-- 1. Filter Section (Top) -->
    <!-- ======================================================================= -->
    <section class="garage-filter-section card" aria-label="Garage Filters">
        <div class="garage-filter-grid">
            <!-- Model Search Input -->
            <div class="garage-filter-item garage-filter-search">
                <label for="garage-filter-model" class="garage-filter-label">Model Name</label>
                <div class="input-with-icon">
                    <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" 
                           id="garage-filter-model" 
                           class="form-control" 
                           placeholder="Type to filter models..." 
                           autocomplete="off">
                    <button type="button" 
                            id="garage-clear-model-input" 
                            class="input-clear-btn" 
                            style="display: none;" 
                            aria-label="Clear model search">&times;</button>
                </div>
            </div>

            <!-- Brand Dropdown -->
            <div class="garage-filter-item">
                <label for="garage-filter-brand" class="garage-filter-label">Brand</label>
                <select id="garage-filter-brand" class="form-control">
                    <option value="">— All Brands —</option>
                    <?php foreach ($filterOptions['brands'] as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Maker Dropdown -->
            <div class="garage-filter-item">
                <label for="garage-filter-maker" class="garage-filter-label">Maker</label>
                <select id="garage-filter-maker" class="form-control">
                    <option value="">— All Makers —</option>
                    <?php foreach ($filterOptions['makers'] as $mk): ?>
                        <option value="<?= (int)$mk['id'] ?>"><?= htmlspecialchars($mk['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Custom Collection Dropdown -->
            <div class="garage-filter-item">
                <label for="garage-filter-collection" class="garage-filter-label">Collection</label>
                <select id="garage-filter-collection" class="form-control">
                    <option value="">— All Collections —</option>
                    <?php foreach ($filterOptions['collections'] as $col): ?>
                        <option value="<?= (int)$col['id'] ?>"><?= htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Scale Dropdown -->
            <div class="garage-filter-item">
                <label for="garage-filter-scale" class="garage-filter-label">Scale</label>
                <select id="garage-filter-scale" class="form-control">
                    <option value="">— All Scales —</option>
                    <?php foreach ($filterOptions['scales'] as $sc): ?>
                        <option value="<?= (int)$sc['id'] ?>"><?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Reset Filters Action -->
            <div class="garage-filter-item garage-filter-actions">
                <label class="garage-filter-label">&nbsp;</label>
                <button type="button" id="garage-btn-reset-filters" class="btn btn-outline garage-reset-btn" title="Reset all search criteria to defaults">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    <span>Reset All Filters</span>
                </button>
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 2. Sticky Alphabet Bar -->
    <!-- ======================================================================= -->
    <nav class="garage-alphabet-wrapper" aria-label="Alphabetical Index Filter">
        <div class="garage-alphabet-bar" id="garage-alphabet-bar">
            <!-- Rendered dynamically by JavaScript with counts & disabled state -->
        </div>
    </nav>

    <!-- ======================================================================= -->
    <!-- 3. Dynamic Content Header -->
    <!-- ======================================================================= -->
    <header class="garage-content-header" id="garage-content-header">
        <!-- List Mode Header: "Showing: [Letter] ([count])" + Sort Toggle -->
        <div class="garage-header-list-mode" id="garage-header-list-mode">
            <div class="garage-header-left">
                <span class="garage-showing-label">Showing:</span>
                <span class="garage-showing-letter" id="garage-active-letter-display">All</span>
                <span class="garage-showing-count">(<span id="garage-active-count-display">0</span>)</span>
            </div>
            <div class="garage-header-right">
                <button type="button" id="garage-sort-btn" class="btn btn-outline btn-sm garage-sort-toggle" aria-label="Toggle sort order">
                    <svg id="garage-sort-icon-asc" class="sort-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5h10M11 9h7M11 13h4"></path>
                        <path d="M3 17l3 3 3-3M6 18V4"></path>
                    </svg>
                    <svg id="garage-sort-icon-desc" class="sort-icon" style="display: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5h4M11 9h7M11 13h10"></path>
                        <path d="M3 7l3-3 3 3M6 6v14"></path>
                    </svg>
                    <span id="garage-sort-label">Sort: A &rarr; Z</span>
                </button>
            </div>
        </div>

        <!-- Detail Mode Header: Breadcrumb [Letter] > [Model Name] + Back Button -->
        <div class="garage-header-detail-mode" id="garage-header-detail-mode" style="display: none;">
            <div class="garage-header-left">
                <nav class="garage-breadcrumb" aria-label="Breadcrumb">
                    <button type="button" class="garage-breadcrumb-link" id="garage-breadcrumb-letter">All</button>
                    <span class="garage-breadcrumb-separator">&gt;</span>
                    <span class="garage-breadcrumb-current" id="garage-breadcrumb-model-name">—</span>
                </nav>
            </div>
            <div class="garage-header-right">
                <button type="button" id="garage-btn-back-to-list" class="btn btn-primary btn-sm garage-back-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back to List</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ======================================================================= -->
    <!-- 4. Proper Content: List Mode vs Detail Mode -->
    <!-- ======================================================================= -->
    <div class="garage-content-area">
        
        <!-- =================================================================== -->
        <!-- MODE A: List Mode (Cards Grid) -->
        <!-- =================================================================== -->
        <div class="garage-list-view" id="garage-list-view">
            <!-- Cards Grid Container -->
            <div class="garage-grid" id="garage-grid">
                <!-- Dynamically populated with .garage-card items -->
            </div>

            <!-- Empty State Display -->
            <div class="garage-empty-state" id="garage-empty-state" style="display: none;">
                <div class="garage-empty-icon-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="7" cy="17" r="2"></circle>
                        <circle cx="17" cy="17" r="2"></circle>
                        <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                </div>
                <h3 class="garage-empty-title">No Cars Found</h3>
                <p class="garage-empty-desc">No models in your collection match the current search or letter filter.</p>
                <button type="button" class="btn btn-outline btn-sm" id="garage-empty-reset-btn">
                    Clear All Filters
                </button>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODE B: Detail Mode (Showcase) -->
        <!-- =================================================================== -->
        <div class="garage-detail-view" id="garage-detail-view" style="display: none;">
            <!-- Clean Editorial Showcase: No group boxes, no subtitles, logos above specs on right -->
            <div class="garage-detail-showcase card">
                
                <!-- Title Bar with Condition & Scale Badges -->
                <div class="garage-detail-title-bar">
                    <h1 class="garage-detail-model-name" id="detail-model-title">Model Name</h1>
                    <div class="garage-detail-badges">
                        <span class="badge badge-condition" id="detail-condition-badge" title="Model Condition">Condition</span>
                        <span class="badge badge-scale" id="detail-scale-badge" title="Scale Ratio">1:64</span>
                    </div>
                </div>

                <!-- Main Layout: Photo on Left, Clean Specs List on Right -->
                <div class="garage-detail-body">
                    <!-- Photo Showcase Box (Left) -->
                    <div class="garage-detail-media-col">
                        <div class="garage-detail-image-box" id="detail-image-box">
                            <img src="" alt="" id="detail-model-img" class="garage-detail-img" style="display: none;">
                            <div class="garage-detail-placeholder" id="detail-image-placeholder">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                    <circle cx="7" cy="17" r="2"></circle>
                                    <circle cx="17" cy="17" r="2"></circle>
                                    <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                                    <path d="M5 12h14"></path>
                                </svg>
                                <span class="text-muted text-sm">No photo available</span>
                            </div>
                        </div>
                    </div>

                    <!-- Specifications & Information (Right of car image, NO groupboxes, NO subtitles) -->
                    <div class="garage-detail-specs-col">
                        <!-- Clean Pure Logos Strip (smaller, above all info, NO text on them) -->
                        <div class="garage-detail-logos-strip" id="detail-logos-strip" style="display: none;">
                            <a href="#" class="clean-logo-item clean-logo-link" id="detail-brand-logo-wrap" style="display: none;" title="View brand in Brands Gallery">
                                <img src="" alt="" id="detail-brand-logo" class="clean-logo-img">
                            </a>
                            <a href="#" class="clean-logo-item clean-logo-link" id="detail-maker-logo-wrap" style="display: none;" title="View maker in Makers Gallery">
                                <img src="" alt="" id="detail-maker-logo" class="clean-logo-img">
                            </a>
                        </div>

                        <!-- Clean Linear Car Information (No group boxes, no subtitles) -->
                        <div class="garage-specs-grid">
                            <div class="spec-cell">
                                <span class="spec-label">Brand</span>
                                <div class="spec-value font-semibold">
                                    <a href="#" id="detail-brand-name-grid" class="garage-brand-nav-link" title="View brand in Brands Gallery">
                                        <span id="detail-brand-name-text">—</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="garage-nav-arrow-icon" id="detail-brand-arrow-icon">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Year</span>
                                <span class="spec-value" id="detail-toy-year">—</span>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Series</span>
                                <span class="spec-value" id="detail-series">—</span>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Series #</span>
                                <span class="spec-value" id="detail-series-number">—</span>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Serial / Toy #</span>
                                <span class="spec-value" id="detail-serial-number">—</span>
                            </div>
                            <div class="spec-cell" id="detail-maker-spec-cell">
                                <span class="spec-label">Maker</span>
                                <div class="spec-value font-semibold">
                                    <a href="#" id="detail-maker-name-grid" class="garage-maker-nav-link" title="View maker in Makers Gallery">
                                        <span id="detail-maker-name-text">—</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="garage-nav-arrow-icon" id="detail-maker-arrow-icon">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="spec-cell" id="detail-car-year-spec-cell">
                                <span class="spec-label">Car Year</span>
                                <span class="spec-value" id="detail-car-year">—</span>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Color</span>
                                <div class="spec-value-color">
                                    <span class="color-badge-swatch" id="detail-color-swatch"></span>
                                    <span class="spec-value" id="detail-color-name">—</span>
                                </div>
                            </div>
                            <div class="spec-cell spec-cell-link">
                                <span class="spec-label">External Link</span>
                                <div id="detail-link-container">
                                    <a href="#" target="_blank" rel="noopener noreferrer" id="detail-external-link-btn" class="btn btn-outline btn-sm garage-ext-link">
                                        <span>Reference Wiki / Database</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                            <polyline points="15 3 21 3 21 9"></polyline>
                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                        </svg>
                                    </a>
                                    <span id="detail-no-link" class="text-muted text-sm" style="display: none;">—</span>
                                </div>
                            </div>
                            <div class="spec-cell spec-cell-collections" id="detail-collections-group" style="display: none;">
                                <span class="spec-label">Collections</span>
                                <div class="spec-collections-tags" id="detail-collections-tags"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes & Description (Markdown Rendered, Clean Section below) -->
                <div class="garage-detail-notes-section" id="detail-notes-container">
                    <h3 class="garage-notes-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Notes & Description
                    </h3>
                    <div class="markdown-body garage-markdown-body" id="detail-markdown-content">
                        <!-- Dynamic Markdown HTML inserted here -->
                    </div>
                    <div class="garage-notes-empty" id="detail-notes-empty" style="display: none;">
                        <span class="text-muted text-sm">No notes or description provided for this model.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Embedded Models Data for Instant Zero-Latency Filtering -->
<script id="garage-models-data" type="application/json">
<?= $modelsJson ?>
</script>

<script>
/**
 * Garage Client-Side Controller
 * 
 * Manages zero-latency instant filtering, dynamic alphabet status calculation,
 * sort order toggle, and seamless dual-mode state switching with filter persistence.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Raw Data & Initial State
    let allModels = [];
    try {
        const raw = document.getElementById('garage-models-data');
        allModels = raw ? JSON.parse(raw.textContent || '[]') : [];
    } catch (e) {
        console.error('Failed to parse garage models JSON:', e);
    }

    // State object
    const state = {
        mode: 'list', // 'list' | 'detail'
        selectedLetter: 'All', // 'All', '#', 'A'..'Z'
        sortAsc: true, // true = ASC, false = DESC
        filters: {
            model: '',
            brand_id: '',
            maker_id: '',
            collection_id: '',
            scale_id: ''
        },
        selectedModelId: null
    };

    // DOM Elements
    const filterModel = document.getElementById('garage-filter-model');
    const clearModelBtn = document.getElementById('garage-clear-model-input');
    const filterBrand = document.getElementById('garage-filter-brand');
    const filterMaker = document.getElementById('garage-filter-maker');
    const filterCollection = document.getElementById('garage-filter-collection');
    const filterScale = document.getElementById('garage-filter-scale');
    const resetFiltersBtn = document.getElementById('garage-btn-reset-filters');
    const emptyResetBtn = document.getElementById('garage-empty-reset-btn');

    const alphabetBar = document.getElementById('garage-alphabet-bar');

    const headerListMode = document.getElementById('garage-header-list-mode');
    const headerDetailMode = document.getElementById('garage-header-detail-mode');
    const activeLetterDisplay = document.getElementById('garage-active-letter-display');
    const activeCountDisplay = document.getElementById('garage-active-count-display');
    const sortBtn = document.getElementById('garage-sort-btn');
    const sortIconAsc = document.getElementById('garage-sort-icon-asc');
    const sortIconDesc = document.getElementById('garage-sort-icon-desc');
    const sortLabel = document.getElementById('garage-sort-label');

    const breadcrumbLetter = document.getElementById('garage-breadcrumb-letter');
    const breadcrumbModelName = document.getElementById('garage-breadcrumb-model-name');
    const backToListBtn = document.getElementById('garage-btn-back-to-list');

    const listView = document.getElementById('garage-list-view');
    const detailView = document.getElementById('garage-detail-view');
    const grid = document.getElementById('garage-grid');
    const emptyState = document.getElementById('garage-empty-state');

    // Detail DOM Elements
    const detailTitle = document.getElementById('detail-model-title');
    const detailConditionBadge = document.getElementById('detail-condition-badge');
    const detailScaleBadge = document.getElementById('detail-scale-badge');
    const detailImg = document.getElementById('detail-model-img');
    const detailImgPlaceholder = document.getElementById('detail-image-placeholder');
    
    // Pure Logos Strip & Specs elements
    const detailLogosStrip = document.getElementById('detail-logos-strip');
    const detailBrandLogoWrap = document.getElementById('detail-brand-logo-wrap');
    const detailBrandLogo = document.getElementById('detail-brand-logo');
    const detailMakerLogoWrap = document.getElementById('detail-maker-logo-wrap');
    const detailMakerLogo = document.getElementById('detail-maker-logo');

    const detailBrandNameGrid = document.getElementById('detail-brand-name-grid');
    const detailBrandNameText = document.getElementById('detail-brand-name-text');
    const detailBrandArrowIcon = document.getElementById('detail-brand-arrow-icon');
    const detailMakerSpecCell = document.getElementById('detail-maker-spec-cell');
    const detailMakerNameGrid = document.getElementById('detail-maker-name-grid');
    const detailMakerNameText = document.getElementById('detail-maker-name-text');
    const detailMakerArrowIcon = document.getElementById('detail-maker-arrow-icon');
    const detailCarYearSpecCell = document.getElementById('detail-car-year-spec-cell');

    const detailToyYear = document.getElementById('detail-toy-year');
    const detailSeries = document.getElementById('detail-series');
    const detailSeriesNumber = document.getElementById('detail-series-number');
    const detailSerialNumber = document.getElementById('detail-serial-number');
    const detailCarYear = document.getElementById('detail-car-year');
    const detailColorSwatch = document.getElementById('detail-color-swatch');
    const detailColorName = document.getElementById('detail-color-name');
    const detailExtLinkBtn = document.getElementById('detail-external-link-btn');
    const detailNoLink = document.getElementById('detail-no-link');
    const detailCollectionsGroup = document.getElementById('detail-collections-group');
    const detailCollectionsTags = document.getElementById('detail-collections-tags');
    const detailMarkdownContent = document.getElementById('detail-markdown-content');
    const detailNotesEmpty = document.getElementById('detail-notes-empty');

    // Helper: Determine index bucket ('#', 'A'..'Z') from model name
    function getModelBucket(name) {
        if (!name || typeof name !== 'string') return '#';
        const trimmed = name.trim();
        if (trimmed.length === 0) return '#';
        const firstChar = trimmed[0].toUpperCase();
        if (firstChar >= 'A' && firstChar <= 'Z') {
            return firstChar;
        }
        return '#';
    }

    // Alphabet keys
    const alphabetLetters = ['All', '#', 'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    // 2. Render Sticky Alphabet Bar
    function renderAlphabetBar(letterCounts) {
        alphabetBar.innerHTML = '';
        alphabetLetters.forEach(letter => {
            const count = letter === 'All' ? letterCounts._total : (letterCounts[letter] || 0);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'alpha-btn';
            btn.dataset.letter = letter;

            const isSelected = state.selectedLetter === letter;
            const isDisabled = count === 0;

            if (isSelected) {
                btn.classList.add('is-active');
            }
            if (isDisabled && letter !== 'All') {
                btn.classList.add('is-disabled');
                btn.disabled = true;
                btn.title = `No models under '${letter}'`;
            } else if (letter === 'All' && count === 0) {
                btn.classList.add('is-disabled');
                btn.title = 'No models match current filters';
            } else {
                btn.title = `${letter} (${count} models)`;
            }

            btn.innerHTML = `<span class="alpha-char">${letter}</span>`;

            btn.addEventListener('click', () => {
                if (isDisabled && letter !== 'All') return;
                state.selectedLetter = letter;
                updateView();
            });

            alphabetBar.appendChild(btn);
        });
    }

    // 3. Filter models according to active criteria
    function filterModels() {
        const query = state.filters.model.toLowerCase().trim();
        const brandId = state.filters.brand_id ? String(state.filters.brand_id) : '';
        const makerId = state.filters.maker_id ? String(state.filters.maker_id) : '';
        const colId = state.filters.collection_id ? String(state.filters.collection_id) : '';
        const scaleId = state.filters.scale_id ? String(state.filters.scale_id) : '';

        // Step A: Filter by search criteria (excluding letter) to determine letter counts
        const baseFiltered = allModels.filter(m => {
            // Text search in model name
            if (query && !(m.model || '').toLowerCase().includes(query)) {
                return false;
            }
            // Brand filter
            if (brandId && String(m.brand_id) !== brandId) {
                return false;
            }
            // Maker filter
            if (makerId && String(m.maker_id) !== makerId) {
                return false;
            }
            // Custom Collection filter (comma separated IDs)
            if (colId) {
                const ids = (m.collection_ids || '').split(',').map(s => s.trim());
                if (!ids.includes(colId)) {
                    return false;
                }
            }
            // Scale filter
            if (scaleId && String(m.scale_id) !== scaleId) {
                return false;
            }
            return true;
        });

        // Step B: Calculate counts per letter for the alphabet bar
        const letterCounts = { _total: baseFiltered.length };
        alphabetLetters.forEach(l => { if (l !== 'All') letterCounts[l] = 0; });

        baseFiltered.forEach(m => {
            const bucket = getModelBucket(m.model);
            if (letterCounts[bucket] !== undefined) {
                letterCounts[bucket]++;
            } else {
                letterCounts['#']++;
            }
        });

        // Step C: If current letter is empty and not 'All', reset to 'All'
        if (state.selectedLetter !== 'All' && (letterCounts[state.selectedLetter] || 0) === 0) {
            state.selectedLetter = 'All';
        }

        // Step D: Filter by active letter
        const finalFiltered = baseFiltered.filter(m => {
            if (state.selectedLetter === 'All') return true;
            return getModelBucket(m.model) === state.selectedLetter;
        });

        // Step E: Sort models
        finalFiltered.sort((a, b) => {
            const nameA = (a.model || '').toLowerCase();
            const nameB = (b.model || '').toLowerCase();
            const cmp = nameA.localeCompare(nameB);
            return state.sortAsc ? cmp : -cmp;
        });

        return {
            models: finalFiltered,
            letterCounts: letterCounts
        };
    }

    // 4. Render Grid of Cards (List Mode)
    function renderListGrid(models) {
        grid.innerHTML = '';

        if (models.length === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        grid.style.display = 'grid';
        emptyState.style.display = 'none';

        models.forEach(model => {
            const card = document.createElement('article');
            card.className = 'garage-card';
            card.dataset.id = model.id;
            card.tabIndex = 0;
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', `View details for ${model.model || 'model'}`);

            const hasImage = Boolean(model.image_path);
            const imageSrc = hasImage ? model.image_path : '';
            const modelName = model.model || 'Unnamed Model';
            const brandName = model.brand_name || 'Brand';
            const scaleName = model.scale_name || '';
            const year = model.year || '';

            card.innerHTML = `
                <div class="garage-card-img-box">
                    ${hasImage ? `
                        <img src="${imageSrc}" 
                             alt="${encodeURIComponent(modelName)}" 
                             class="garage-card-img" 
                             loading="lazy" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="garage-card-img-placeholder" style="display: none;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                <circle cx="7" cy="17" r="2"></circle>
                                <circle cx="17" cy="17" r="2"></circle>
                                <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                                <path d="M5 12h14"></path>
                            </svg>
                        </div>
                    ` : `
                        <div class="garage-card-img-placeholder">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                                <circle cx="7" cy="17" r="2"></circle>
                                <circle cx="17" cy="17" r="2"></circle>
                                <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                                <path d="M5 12h14"></path>
                            </svg>
                        </div>
                    `}
                    ${scaleName ? `<span class="garage-card-scale-badge">${escapeHtml(scaleName)}</span>` : ''}
                </div>
                <div class="garage-card-info">
                    <h3 class="garage-card-title" title="${escapeHtml(modelName)}">${escapeHtml(modelName)}</h3>
                    <div class="garage-card-meta">
                        <span class="garage-card-brand">${escapeHtml(brandName)}</span>
                        ${year ? `<span class="garage-card-bullet">&bull;</span><span class="garage-card-year">${escapeHtml(year)}</span>` : ''}
                    </div>
                </div>
            `;

            // Card click transitions to Detail Mode
            const handleCardSelect = () => {
                openDetailMode(model.id);
            };

            card.addEventListener('click', handleCardSelect);
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleCardSelect();
                }
            });

            grid.appendChild(card);
        });
    }

    // 5. Open Detail Mode for a specific model ID
    function openDetailMode(modelId) {
        const model = allModels.find(m => String(m.id) === String(modelId));
        if (!model) return;

        state.selectedModelId = model.id;
        state.mode = 'detail';

        // Populate Detail View
        detailTitle.textContent = model.model || 'Unnamed Model';

        // Badges: Condition and Scale
        if (model.condition_name) {
            detailConditionBadge.textContent = model.condition_name;
            detailConditionBadge.style.display = 'inline-flex';
        } else {
            detailConditionBadge.textContent = 'Condition: Not Specified';
            detailConditionBadge.style.display = 'inline-flex';
        }

        if (model.scale_name) {
            detailScaleBadge.textContent = model.scale_name;
            detailScaleBadge.style.display = 'inline-flex';
        } else {
            detailScaleBadge.style.display = 'none';
        }

        // Image Box
        if (model.image_path) {
            detailImg.src = model.image_path;
            detailImg.alt = model.model || 'Model photo';
            detailImg.style.display = 'block';
            detailImgPlaceholder.style.display = 'none';
            detailImg.onerror = () => {
                detailImg.style.display = 'none';
                detailImgPlaceholder.style.display = 'flex';
            };
        } else {
            detailImg.style.display = 'none';
            detailImgPlaceholder.style.display = 'flex';
        }

        // 1. Pure Logos Strip (smaller, above all info on right, NO text on them)
        let hasAnyLogo = false;
        if (model.brand_logo_path) {
            detailBrandLogo.src = model.brand_logo_path;
            detailBrandLogo.alt = (model.brand_name || 'Brand') + ' Logo';
            detailBrandLogoWrap.title = 'View ' + (model.brand_name || 'Brand') + ' in Brands Gallery';
            if (model.brand_id) {
                detailBrandLogoWrap.href = '/brands_gallery.php?id=' + encodeURIComponent(model.brand_id);
            } else {
                detailBrandLogoWrap.removeAttribute('href');
            }
            detailBrandLogoWrap.style.display = 'inline-flex';
            hasAnyLogo = true;
        } else {
            detailBrandLogoWrap.style.display = 'none';
        }

        const makerName = (model.maker_name || '').trim();
        if (makerName && model.maker_logo_path) {
            detailMakerLogo.src = model.maker_logo_path;
            detailMakerLogo.alt = makerName + ' Logo';
            detailMakerLogoWrap.title = 'View ' + makerName + ' in Makers Gallery';
            if (model.maker_id) {
                detailMakerLogoWrap.href = '/makers_gallery.php?id=' + encodeURIComponent(model.maker_id);
            } else {
                detailMakerLogoWrap.removeAttribute('href');
            }
            detailMakerLogoWrap.style.display = 'inline-flex';
            hasAnyLogo = true;
        } else {
            detailMakerLogoWrap.style.display = 'none';
        }

        if (detailLogosStrip) {
            detailLogosStrip.style.display = hasAnyLogo ? 'flex' : 'none';
        }

        // 2. Specifications & Information (Listed cleanly next to car, no groupboxes, no subtitles)
        if (detailBrandNameText) {
            detailBrandNameText.textContent = model.brand_name || '—';
        } else {
            detailBrandNameGrid.textContent = model.brand_name || '—';
        }
        if (model.brand_id) {
            detailBrandNameGrid.href = '/brands_gallery.php?id=' + encodeURIComponent(model.brand_id);
            detailBrandNameGrid.title = 'View ' + (model.brand_name || 'Brand') + ' in Brands Gallery';
            detailBrandNameGrid.classList.add('is-clickable');
            if (detailBrandArrowIcon) detailBrandArrowIcon.style.display = 'inline-block';
        } else {
            detailBrandNameGrid.removeAttribute('href');
            detailBrandNameGrid.title = '';
            detailBrandNameGrid.classList.remove('is-clickable');
            if (detailBrandArrowIcon) detailBrandArrowIcon.style.display = 'none';
        }

        detailToyYear.textContent = model.year || '—';
        detailSeries.textContent = model.series || '—';
        detailSeriesNumber.textContent = model.series_number || '—';
        detailSerialNumber.textContent = model.serial_number || '—';

        if (detailMakerNameText) {
            detailMakerNameText.textContent = makerName || '—';
        } else {
            detailMakerNameGrid.textContent = makerName || '—';
        }
        if (model.maker_id && makerName) {
            detailMakerNameGrid.href = '/makers_gallery.php?id=' + encodeURIComponent(model.maker_id);
            detailMakerNameGrid.title = 'View ' + makerName + ' in Makers Gallery';
            detailMakerNameGrid.classList.add('is-clickable');
            if (detailMakerArrowIcon) detailMakerArrowIcon.style.display = 'inline-block';
        } else {
            detailMakerNameGrid.removeAttribute('href');
            detailMakerNameGrid.title = '';
            detailMakerNameGrid.classList.remove('is-clickable');
            if (detailMakerArrowIcon) detailMakerArrowIcon.style.display = 'none';
        }

        detailCarYear.textContent = model.model_year || '—';

        // Color Swatch and Name
        detailColorName.textContent = model.color_name || '—';
        if (model.color_hex) {
            detailColorSwatch.style.backgroundColor = model.color_hex;
            detailColorSwatch.style.display = 'inline-block';
        } else {
            detailColorSwatch.style.display = 'none';
        }

        // External Link Button
        if (model.link && model.link.trim() !== '') {
            detailExtLinkBtn.href = model.link;
            detailExtLinkBtn.style.display = 'inline-flex';
            detailNoLink.style.display = 'none';
        } else {
            detailExtLinkBtn.style.display = 'none';
            detailNoLink.style.display = 'inline-block';
        }

        // Collections Tags
        if (model.collection_names && model.collection_names.trim() !== '') {
            detailCollectionsTags.innerHTML = '';
            const colNames = model.collection_names.split(',').map(s => s.trim()).filter(Boolean);
            const colIds = (model.collection_ids || '').split(',').map(s => s.trim()).filter(Boolean);
            colNames.forEach((colName, idx) => {
                const colId = colIds[idx] || null;
                const tag = document.createElement(colId ? 'a' : 'span');
                tag.className = 'spec-collection-tag' + (colId ? ' is-link' : '');
                tag.textContent = colName;
                if (colId) {
                    tag.href = '/collections_gallery.php?id=' + encodeURIComponent(colId);
                    tag.title = 'View ' + colName + ' in Collections Gallery';
                }
                detailCollectionsTags.appendChild(tag);
            });
            detailCollectionsGroup.style.display = 'block';
        } else {
            detailCollectionsGroup.style.display = 'none';
        }

        // Notes and Description (Markdown HTML)
        if (model.description_html && model.description_html.trim() !== '') {
            detailMarkdownContent.innerHTML = model.description_html;
            detailMarkdownContent.style.display = 'block';
            detailNotesEmpty.style.display = 'none';
        } else {
            detailMarkdownContent.innerHTML = '';
            detailMarkdownContent.style.display = 'none';
            detailNotesEmpty.style.display = 'block';
        }

        // Breadcrumb updates: [Letter] > [Model Name]
        breadcrumbLetter.textContent = state.selectedLetter;
        breadcrumbModelName.textContent = model.model || 'Model';

        // Update URL state
        const url = new URL(window.location);
        url.searchParams.set('id', model.id);
        window.history.pushState({ mode: 'detail', id: model.id }, '', url);

        // Switch active view container
        listView.style.display = 'none';
        detailView.style.display = 'block';

        headerListMode.style.display = 'none';
        headerDetailMode.style.display = 'flex';

        // Scroll to top of content smoothly
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 6. Return to List Mode preserving all active filters
    function returnToListMode() {
        state.mode = 'list';
        state.selectedModelId = null;

        // Clean URL parameter
        const url = new URL(window.location);
        url.searchParams.delete('id');
        window.history.pushState({ mode: 'list' }, '', url);

        detailView.style.display = 'none';
        listView.style.display = 'block';

        headerDetailMode.style.display = 'none';
        headerListMode.style.display = 'flex';

        updateView();
    }

    // 7. Update Master View
    function updateView() {
        const { models, letterCounts } = filterModels();

        // Render Alphabet Bar with live counts
        renderAlphabetBar(letterCounts);

        // Update List Header
        activeLetterDisplay.textContent = state.selectedLetter;
        activeCountDisplay.textContent = models.length;

        // Update Sort Controls Display
        if (state.sortAsc) {
            sortIconAsc.style.display = 'inline-block';
            sortIconDesc.style.display = 'none';
            sortLabel.textContent = 'Sort: A \u2192 Z';
        } else {
            sortIconAsc.style.display = 'none';
            sortIconDesc.style.display = 'inline-block';
            sortLabel.textContent = 'Sort: Z \u2192 A';
        }

        // Render List Grid
        if (state.mode === 'list') {
            renderListGrid(models);
        }
    }

    // 8. Event Listeners for Filters (Instant Filtering)
    filterModel.addEventListener('input', (e) => {
        state.filters.model = e.target.value;
        clearModelBtn.style.display = state.filters.model ? 'block' : 'none';
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    clearModelBtn.addEventListener('click', () => {
        filterModel.value = '';
        state.filters.model = '';
        clearModelBtn.style.display = 'none';
        filterModel.focus();
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    filterBrand.addEventListener('change', (e) => {
        state.filters.brand_id = e.target.value;
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    filterMaker.addEventListener('change', (e) => {
        state.filters.maker_id = e.target.value;
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    filterCollection.addEventListener('change', (e) => {
        state.filters.collection_id = e.target.value;
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    filterScale.addEventListener('change', (e) => {
        state.filters.scale_id = e.target.value;
        if (state.mode === 'detail') returnToListMode();
        updateView();
    });

    function resetAllFilters() {
        filterModel.value = '';
        clearModelBtn.style.display = 'none';
        filterBrand.value = '';
        filterMaker.value = '';
        filterCollection.value = '';
        filterScale.value = '';

        state.filters = {
            model: '',
            brand_id: '',
            maker_id: '',
            collection_id: '',
            scale_id: ''
        };
        state.selectedLetter = 'All';
        state.sortAsc = true;

        if (state.mode === 'detail') {
            returnToListMode();
        } else {
            updateView();
        }
    }

    resetFiltersBtn.addEventListener('click', resetAllFilters);
    emptyResetBtn.addEventListener('click', resetAllFilters);

    // Sort button toggle
    sortBtn.addEventListener('click', () => {
        state.sortAsc = !state.sortAsc;
        updateView();
    });

    // Detail breadcrumb & back button
    breadcrumbLetter.addEventListener('click', returnToListMode);
    backToListBtn.addEventListener('click', returnToListMode);

    // Browser back/forward navigation support
    window.addEventListener('popstate', (e) => {
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        if (id) {
            openDetailMode(id);
        } else {
            returnToListMode();
        }
    });

    // Helper: Safe HTML escape
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 9. Initial Load & Deep-Link Check
    const initialUrlParams = new URLSearchParams(window.location.search);
    const initialId = initialUrlParams.get('id') || initialUrlParams.get('model_id');

    // Pre-filter by brand if coming from Brands Gallery (e.g. ?brand_id=3 or ?brand=Hot+Wheels)
    const initialBrandParam = initialUrlParams.get('brand_id') || initialUrlParams.get('brand');
    if (initialBrandParam) {
        const brandStr = String(initialBrandParam).trim();
        let matchedOption = Array.from(filterBrand.options).find(opt => opt.value === brandStr);
        if (!matchedOption) {
            matchedOption = Array.from(filterBrand.options).find(opt => opt.text.trim().toLowerCase() === brandStr.toLowerCase());
        }
        if (matchedOption && matchedOption.value) {
            filterBrand.value = matchedOption.value;
            state.filters.brand_id = matchedOption.value;
        } else if (brandStr !== '') {
            const newOpt = document.createElement('option');
            newOpt.value = brandStr;
            newOpt.textContent = 'Brand #' + brandStr;
            filterBrand.appendChild(newOpt);
            filterBrand.value = brandStr;
            state.filters.brand_id = brandStr;
        }
    }

    // Pre-filter by maker if coming from Makers Gallery (e.g. ?maker_id=2 or ?maker=Ferrari)
    const initialMakerParam = initialUrlParams.get('maker_id') || initialUrlParams.get('maker');
    if (initialMakerParam) {
        const makerStr = String(initialMakerParam).trim();
        let matchedOption = Array.from(filterMaker.options).find(opt => opt.value === makerStr);
        if (!matchedOption) {
            matchedOption = Array.from(filterMaker.options).find(opt => opt.text.trim().toLowerCase() === makerStr.toLowerCase());
        }
        if (matchedOption && matchedOption.value) {
            filterMaker.value = matchedOption.value;
            state.filters.maker_id = matchedOption.value;
        } else if (makerStr !== '') {
            const newOpt = document.createElement('option');
            newOpt.value = makerStr;
            newOpt.textContent = 'Maker #' + makerStr;
            filterMaker.appendChild(newOpt);
            filterMaker.value = makerStr;
            state.filters.maker_id = makerStr;
        }
    }

    // Pre-filter by collection if coming from Collections Gallery (e.g. ?collection_id=1 or ?collection=Hot+Rods)
    const initialCollectionParam = initialUrlParams.get('collection_id') || initialUrlParams.get('collection');
    if (initialCollectionParam) {
        const colStr = String(initialCollectionParam).trim();
        let matchedOption = Array.from(filterCollection.options).find(opt => opt.value === colStr);
        if (!matchedOption) {
            matchedOption = Array.from(filterCollection.options).find(opt => opt.text.trim().toLowerCase() === colStr.toLowerCase());
        }
        if (matchedOption && matchedOption.value) {
            filterCollection.value = matchedOption.value;
            state.filters.collection_id = matchedOption.value;
        } else if (colStr !== '') {
            const newOpt = document.createElement('option');
            newOpt.value = colStr;
            newOpt.textContent = 'Collection #' + colStr;
            filterCollection.appendChild(newOpt);
            filterCollection.value = colStr;
            state.filters.collection_id = colStr;
        }
    }

    updateView();

    if (initialId && allModels.some(m => String(m.id) === String(initialId))) {
        openDetailMode(initialId);
    }
});
</script>

<?php
require __DIR__ . '/layout/footer.php';
?>
