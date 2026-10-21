<?php
/**
 * Brands Gallery Showcase View
 * 
 * Interactive gallery for diecast manufacturers and branding:
 * - Real-time client-side instant search filter
 * - Sticky Alphabet selector matching Garage style
 * - Dynamic Content Header group box with "Showing: [Letter] ([count])" and right-aligned Sort Toggle
 * - Dual Mode: List Mode (responsive cards grid) and Detail Mode (showcase with logo, models link & markdown description)
 */

$pageTitle = 'Brands Gallery - CarCollector';
$currentUser = $currentUser ?? ($_SESSION['user'] ?? []);
require __DIR__ . '/layout/header.php';

$brandsJson = json_encode($allBrands, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<main class="container page-content garage-page">

    <!-- ======================================================================= -->
    <!-- 1. Filter Section (Top) -->
    <!-- ======================================================================= -->
    <section class="garage-filter-section card" aria-label="Brand Filter Controls">
        <div class="garage-filter-grid" style="grid-template-columns: 1fr auto;">
            <!-- Brand Search Input -->
            <div class="garage-filter-item garage-filter-search">
                <label for="brands-filter-search" class="garage-filter-label">Search Brands</label>
                <div class="input-with-icon">
                    <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" 
                           id="brands-filter-search" 
                           class="form-control" 
                           placeholder="Search by brand name or description..." 
                           autocomplete="off">
                    <button type="button" 
                            id="brands-clear-search-btn" 
                            class="input-clear-btn" 
                            style="display: none;" 
                            aria-label="Clear brand search">&times;</button>
                </div>
            </div>

            <!-- Reset Action -->
            <div class="garage-filter-item garage-filter-actions">
                <label class="garage-filter-label">&nbsp;</label>
                <button type="button" id="brands-btn-reset-filters" class="btn btn-outline garage-reset-btn" title="Reset all search criteria to defaults">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    <span>Reset</span>
                </button>
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 2. Sticky Alphabet Bar -->
    <!-- ======================================================================= -->
    <nav class="garage-alphabet-wrapper" aria-label="Alphabetical Index Filter">
        <div class="garage-alphabet-bar" id="brands-alphabet-bar">
            <!-- Rendered dynamically by JavaScript with counts in tooltip & disabled state -->
        </div>
    </nav>

    <!-- ======================================================================= -->
    <!-- 3. Dynamic Content Header Group Box -->
    <!-- ======================================================================= -->
    <header class="garage-content-header" id="brands-content-header">
        <!-- List Mode Header: "Showing: [Letter] ([count])" + Sort Toggle -->
        <div class="garage-header-list-mode" id="brands-header-list-mode">
            <div class="garage-header-left">
                <span class="garage-showing-label">Showing:</span>
                <span class="garage-showing-letter" id="brands-active-letter-display">All</span>
                <span class="garage-showing-count">(<span id="brands-active-count-display">0</span>)</span>
            </div>
            <div class="garage-header-right">
                <button type="button" id="brands-sort-btn" class="btn btn-outline btn-sm garage-sort-toggle" aria-label="Toggle sort order">
                    <svg id="brands-sort-icon-asc" class="sort-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5h10M11 9h7M11 13h4"></path>
                        <path d="M3 17l3 3 3-3M6 18V4"></path>
                    </svg>
                    <svg id="brands-sort-icon-desc" class="sort-icon" style="display: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5h4M11 9h7M11 13h10"></path>
                        <path d="M3 7l3-3 3 3M6 6v14"></path>
                    </svg>
                    <span id="brands-sort-label">SORT: A &rarr; Z</span>
                </button>
            </div>
        </div>

        <!-- Detail Mode Header: Breadcrumb [Letter] > [Brand Name] + Back Button -->
        <div class="garage-header-detail-mode" id="brands-header-detail-mode" style="display: none;">
            <div class="garage-header-left">
                <nav class="garage-breadcrumb" aria-label="Breadcrumb">
                    <button type="button" class="garage-breadcrumb-link" id="brands-breadcrumb-letter">All</button>
                    <span class="garage-breadcrumb-separator">&gt;</span>
                    <span class="garage-breadcrumb-current" id="brands-breadcrumb-brand-name">—</span>
                </nav>
            </div>
            <div class="garage-header-right">
                <button type="button" id="brands-btn-back-to-list" class="btn btn-primary btn-sm garage-back-btn">
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
        <div class="garage-list-view" id="brands-list-view">
            <!-- Cards Grid Container -->
            <div class="garage-grid brands-gallery-grid" id="brands-grid">
                <!-- Dynamically populated with .garage-card items -->
            </div>

            <!-- Empty State Display -->
            <div class="garage-empty-state" id="brands-empty-state" style="display: none;">
                <div class="garage-empty-icon-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                </div>
                <h3 class="garage-empty-title">No Brands Found</h3>
                <p class="garage-empty-desc">No diecast brands match the current search or letter filter.</p>
                <button type="button" class="btn btn-outline btn-sm" id="brands-empty-reset-btn">
                    Clear All Filters
                </button>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- MODE B: Detail Mode (Showcase) -->
        <!-- =================================================================== -->
        <div class="garage-detail-view" id="brands-detail-view" style="display: none;">
            <div class="garage-detail-showcase card">
                
                <!-- Title Bar with Brand Title -->
                <div class="garage-detail-title-bar">
                    <h1 class="garage-detail-model-name" id="detail-brand-title">Brand Name</h1>
                </div>

                <!-- Main Layout: Logo on Left, Information on Right -->
                <div class="garage-detail-body brand-detail-body">
                    <!-- Brand Logo Showcase Box (Left) -->
                    <div class="garage-detail-media-col brand-detail-media-col">
                        <div class="garage-detail-image-box brand-detail-logo-box" id="detail-brand-logo-box">
                            <img src="" alt="" id="detail-brand-logo-img" class="garage-detail-img brand-detail-logo-img" style="display: none;">
                            <div class="garage-detail-placeholder" id="detail-brand-logo-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                </svg>
                                <span class="text-muted text-sm">No logo available</span>
                            </div>
                        </div>
                    </div>

                    <!-- Brand Information (Right of logo, clean layout) -->
                    <div class="garage-detail-specs-col">
                        <div class="garage-specs-grid brand-specs-grid">
                            <div class="spec-cell">
                                <span class="spec-label">Brand Name</span>
                                <span class="spec-value font-semibold" id="detail-brand-name-grid">—</span>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Your Models</span>
                                <div class="spec-value">
                                    <a href="/garage.php" id="detail-spec-garage-link" class="brand-models-nav-link" title="View models of this brand in Garage">
                                        <span id="detail-spec-models-text">0 models</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="brand-models-arrow-icon">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">Date Added</span>
                                <span class="spec-value" id="detail-brand-created-grid">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes & Description (Markdown Rendered, Clean Section below) -->
                <div class="garage-detail-notes-section" id="detail-brand-notes-container">
                    <h3 class="garage-notes-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Description & History
                    </h3>
                    <div class="markdown-body garage-markdown-body" id="detail-brand-markdown-content">
                        <!-- Dynamic Markdown HTML inserted here -->
                    </div>
                    <div class="garage-notes-empty" id="detail-brand-notes-empty" style="display: none;">
                        <span class="text-muted text-sm">No description provided for this brand.</span>
                    </div>
                </div>

                <!-- Associated Models Grid Section (Paginated) -->
                <div class="detail-models-section" id="detail-brand-models-section">
                    <div class="detail-models-header">
                        <h3 class="detail-models-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="detail-models-title-icon">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.1 10.7 2 11.3 2 12v4c0 .6.4 1 1 1h2"></path>
                                <circle cx="7" cy="17" r="2"></circle>
                                <circle cx="17" cy="17" r="2"></circle>
                            </svg>
                            <span>Models by this Brand</span>
                        </h3>
                    </div>

                    <!-- Models Grid -->
                    <div class="detail-models-grid" id="detail-brand-models-grid"></div>

                    <!-- Empty State for 0 models -->
                    <div class="detail-models-empty" id="detail-brand-models-empty" style="display: none;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="detail-models-empty-icon">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.1 10.7 2 11.3 2 12v4c0 .6.4 1 1 1h2"></path>
                            <circle cx="7" cy="17" r="2"></circle>
                            <circle cx="17" cy="17" r="2"></circle>
                        </svg>
                        <p class="detail-models-empty-text">No models in your collection belong to this brand yet.</p>
                    </div>

                    <!-- Pagination Bar -->
                    <div class="detail-models-pagination" id="detail-brand-models-pagination" style="display: none;">
                        <div class="detail-pagination-info" id="detail-brand-pagination-info"></div>
                        <div class="detail-pagination-controls" id="detail-brand-pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Embedded Brands Data for Instant Zero-Latency Filtering -->
<script id="brands-gallery-data" type="application/json">
<?= $brandsJson ?>
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Data Store & State
    const allBrandsDataEl = document.getElementById('brands-gallery-data');
    let allBrands = [];
    try {
        allBrands = JSON.parse(allBrandsDataEl.textContent || '[]');
    } catch (e) {
        console.error('Failed to parse brands gallery data:', e);
        allBrands = [];
    }

    const state = {
        mode: '<?= $initialMode ?>',
        selectedLetter: 'All',
        searchQuery: '',
        sortAsc: true,
        selectedBrandId: <?= $detailBrandId ? (int)$detailBrandId : 'null' ?>,
    };

    // DOM Elements - Content Header & Modes
    const headerListMode = document.getElementById('brands-header-list-mode');
    const headerDetailMode = document.getElementById('brands-header-detail-mode');
    const listView = document.getElementById('brands-list-view');
    const detailView = document.getElementById('brands-detail-view');
    const grid = document.getElementById('brands-grid');
    const emptyState = document.getElementById('brands-empty-state');
    const alphabetBar = document.getElementById('brands-alphabet-bar');
    const activeLetterDisplay = document.getElementById('brands-active-letter-display');
    const activeCountDisplay = document.getElementById('brands-active-count-display');
    const sortBtn = document.getElementById('brands-sort-btn');
    const sortLabel = document.getElementById('brands-sort-label');
    const sortIconAsc = document.getElementById('brands-sort-icon-asc');
    const sortIconDesc = document.getElementById('brands-sort-icon-desc');

    // Filter controls
    const searchInput = document.getElementById('brands-filter-search');
    const clearSearchBtn = document.getElementById('brands-clear-search-btn');
    const resetFiltersBtn = document.getElementById('brands-btn-reset-filters');
    const emptyResetBtn = document.getElementById('brands-empty-reset-btn');

    // Breadcrumbs & Detail navigation
    const breadcrumbLetter = document.getElementById('brands-breadcrumb-letter');
    const breadcrumbBrandName = document.getElementById('brands-breadcrumb-brand-name');
    const backToListBtn = document.getElementById('brands-btn-back-to-list');

    // Detail DOM Elements
    const detailTitle = document.getElementById('detail-brand-title');
    const detailLogoImg = document.getElementById('detail-brand-logo-img');
    const detailLogoPlaceholder = document.getElementById('detail-brand-logo-placeholder');
    const detailNameGrid = document.getElementById('detail-brand-name-grid');
    const detailSpecGarageLink = document.getElementById('detail-spec-garage-link');
    const detailSpecModelsText = document.getElementById('detail-spec-models-text');
    const detailCreatedGrid = document.getElementById('detail-brand-created-grid');
    const detailMarkdownContent = document.getElementById('detail-brand-markdown-content');
    const detailNotesEmpty = document.getElementById('detail-brand-notes-empty');
    const detailModelsGrid = document.getElementById('detail-brand-models-grid');
    const detailModelsEmpty = document.getElementById('detail-brand-models-empty');
    const detailModelsPagination = document.getElementById('detail-brand-models-pagination');
    const detailPaginationInfo = document.getElementById('detail-brand-pagination-info');
    const detailPaginationControls = document.getElementById('detail-brand-pagination-controls');

    const MODELS_PER_PAGE = 18;
    let currentBrandModels = [];
    let currentBrandModelsPage = 1;

    function getPaginationRange(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    function renderDetailModels(models, page = 1) {
        currentBrandModels = models || [];
        const totalModels = currentBrandModels.length;

        if (totalModels === 0) {
            detailModelsGrid.innerHTML = '';
            detailModelsGrid.style.display = 'none';
            detailModelsEmpty.style.display = 'flex';
            detailModelsPagination.style.display = 'none';
            return;
        }

        detailModelsEmpty.style.display = 'none';
        detailModelsGrid.style.display = 'grid';

        const totalPages = Math.ceil(totalModels / MODELS_PER_PAGE);
        currentBrandModelsPage = Math.max(1, Math.min(page, totalPages));

        const startIndex = (currentBrandModelsPage - 1) * MODELS_PER_PAGE;
        const endIndex = Math.min(startIndex + MODELS_PER_PAGE, totalModels);
        const pageModels = currentBrandModels.slice(startIndex, endIndex);

        detailModelsGrid.innerHTML = '';
        const fragment = document.createDocumentFragment();

        pageModels.forEach(m => {
            const card = document.createElement('a');
            card.className = 'detail-model-card';
            card.href = `/garage.php?id=${encodeURIComponent(m.id)}`;
            card.title = `View ${m.model || 'Model'} in Garage`;

            const imgHtml = m.image_path
                ? `<img src="${escapeHtml(getVersionedImageUrl(m.image_path, m.modified))}" alt="${escapeHtml(m.model || 'Model')}" class="detail-model-card-img" loading="lazy">`
                : `<div class="detail-model-card-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.1 10.7 2 11.3 2 12v4c0 .6.4 1 1 1h2"></path>
                        <circle cx="7" cy="17" r="2"></circle>
                        <circle cx="17" cy="17" r="2"></circle>
                    </svg>
                    <span class="text-xs text-muted" style="margin-top: 4px;">No image</span>
                   </div>`;

            card.innerHTML = `
                <div class="detail-model-card-img-box">
                    ${imgHtml}
                </div>
                <div class="detail-model-card-info">
                    <h4 class="detail-model-card-name" title="${escapeHtml(m.model || 'Unnamed Model')}">${escapeHtml(m.model || 'Unnamed Model')}</h4>
                </div>
            `;
            fragment.appendChild(card);
        });

        detailModelsGrid.appendChild(fragment);

        // Pagination controls
        if (totalPages > 1) {
            detailModelsPagination.style.display = 'flex';
            detailPaginationInfo.textContent = `Showing ${startIndex + 1}–${endIndex} of ${totalModels} models`;
            detailPaginationControls.innerHTML = '';

            // Prev button
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'detail-pagination-btn';
            prevBtn.innerHTML = `&laquo; Prev`;
            prevBtn.disabled = (currentBrandModelsPage === 1);
            prevBtn.addEventListener('click', () => {
                renderDetailModels(currentBrandModels, currentBrandModelsPage - 1);
            });
            detailPaginationControls.appendChild(prevBtn);

            // Page numbers
            const pageRange = getPaginationRange(currentBrandModelsPage, totalPages);
            pageRange.forEach(p => {
                if (p === '...') {
                    const dots = document.createElement('span');
                    dots.className = 'detail-pagination-ellipsis';
                    dots.innerHTML = '&hellip;';
                    detailPaginationControls.appendChild(dots);
                } else {
                    const pageBtn = document.createElement('button');
                    pageBtn.type = 'button';
                    pageBtn.className = 'detail-pagination-btn' + (p === currentBrandModelsPage ? ' is-active' : '');
                    pageBtn.textContent = p;
                    if (p !== currentBrandModelsPage) {
                        pageBtn.addEventListener('click', () => {
                            renderDetailModels(currentBrandModels, p);
                        });
                    }
                    detailPaginationControls.appendChild(pageBtn);
                }
            });

            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'detail-pagination-btn';
            nextBtn.innerHTML = `Next &raquo;`;
            nextBtn.disabled = (currentBrandModelsPage === totalPages);
            nextBtn.addEventListener('click', () => {
                renderDetailModels(currentBrandModels, currentBrandModelsPage + 1);
            });
            detailPaginationControls.appendChild(nextBtn);
        } else {
            detailModelsPagination.style.display = 'none';
        }
    }

    // Helper: Determine index bucket ('#', 'A'..'Z') from brand name
    function getBrandBucket(name) {
        if (!name || typeof name !== 'string') return '#';
        const trimmed = name.trim();
        if (trimmed.length === 0) return '#';
        const firstChar = trimmed[0].toUpperCase();
        if (firstChar >= 'A' && firstChar <= 'Z') {
            return firstChar;
        }
        return '#';
    }

    const alphabetLetters = ['All', '#', 'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    // 2. Render Sticky Alphabet Bar (Letter only - no numbers inside buttons)
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
                btn.title = `No brands under '${letter}'`;
            } else if (letter === 'All' && count === 0) {
                btn.classList.add('is-disabled');
                btn.title = 'No brands match current filters';
            } else {
                btn.title = `${letter} (${count} brands)`;
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

    // 3. Filter and Sort Brands
    function filterBrands() {
        const query = (state.searchQuery || '').trim().toLowerCase();

        // Count distribution across all letters
        const letterCounts = { _total: 0 };
        alphabetLetters.forEach(l => { letterCounts[l] = 0; });

        allBrands.forEach(b => {
            let matchesSearch = true;
            if (query) {
                const name = (b.name || '').toLowerCase();
                const desc = (b.description || '').toLowerCase();
                if (!name.includes(query) && !desc.includes(query)) {
                    matchesSearch = false;
                }
            }

            if (matchesSearch) {
                letterCounts._total++;
                const bucket = getBrandBucket(b.name);
                letterCounts[bucket] = (letterCounts[bucket] || 0) + 1;
            }
        });

        // Filter by search query and selected letter
        const filtered = allBrands.filter(b => {
            if (query) {
                const name = (b.name || '').toLowerCase();
                const desc = (b.description || '').toLowerCase();
                if (!name.includes(query) && !desc.includes(query)) {
                    return false;
                }
            }

            if (state.selectedLetter !== 'All') {
                const bucket = getBrandBucket(b.name);
                if (bucket !== state.selectedLetter) {
                    return false;
                }
            }

            return true;
        });

        // Sort brands
        filtered.sort((a, b) => {
            const nameA = (a.name || '').toLowerCase();
            const nameB = (b.name || '').toLowerCase();
            const cmp = nameA.localeCompare(nameB);
            return state.sortAsc ? cmp : -cmp;
        });

        return { brands: filtered, letterCounts };
    }

    // 4. Render Brand Cards (Clean card layout matching garage)
    function renderCards(brands) {
        grid.innerHTML = '';

        if (brands.length === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        grid.style.display = 'grid';
        emptyState.style.display = 'none';

        const fragment = document.createDocumentFragment();

        brands.forEach(b => {
            const card = document.createElement('div');
            card.className = 'garage-card brand-card';
            card.tabIndex = 0;
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', `View details for brand ${b.name}`);

            const logoHtml = b.logo_path 
                ? `<img src="${escapeHtml(getVersionedImageUrl(b.logo_path, b.modified))}" alt="${escapeHtml(b.name)} logo" class="garage-card-img brand-card-logo" loading="lazy">`
                : `<div class="garage-card-img-placeholder">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                   </div>`;

            const modelCount = parseInt(b.models_count, 10) || 0;
            const modelBadgeText = modelCount === 1 ? '1 Model' : `${modelCount} Models`;

            card.innerHTML = `
                <div class="garage-card-img-box brand-card-logo-box">
                    ${logoHtml}
                    <div class="garage-card-scale-badge brand-card-badge">${modelBadgeText}</div>
                </div>
                <div class="garage-card-info">
                    <h3 class="garage-card-title brand-card-title" title="${escapeHtml(b.name)}">${escapeHtml(b.name)}</h3>
                </div>
            `;

            // Open Detail Mode on Click or Enter key
            card.addEventListener('click', () => openDetailMode(b.id));
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openDetailMode(b.id);
                }
            });

            fragment.appendChild(card);
        });

        grid.appendChild(fragment);
    }

    // 5. Open Detail Mode
    function openDetailMode(brandId) {
        const brand = allBrands.find(b => String(b.id) === String(brandId));
        if (!brand) return;

        state.selectedBrandId = brand.id;
        state.mode = 'detail';

        // Populate Detail View
        detailTitle.textContent = brand.name || 'Unnamed Brand';

        // Models count and link navigating to garage pre-filtered (even if 0 models)
        const count = parseInt(brand.models_count, 10) || 0;
        const modelsText = count === 1 ? '1 model' : `${count} models`;
        if (detailSpecModelsText) {
            detailSpecModelsText.textContent = modelsText;
        }

        const garageUrl = `/garage.php?brand_id=${encodeURIComponent(brand.id)}`;
        if (detailSpecGarageLink) {
            detailSpecGarageLink.href = garageUrl;
        }

        // Logo Image Box
        if (brand.logo_path) {
            detailLogoImg.src = getVersionedImageUrl(brand.logo_path, brand.modified);
            detailLogoImg.alt = brand.name + ' Logo';
            detailLogoImg.style.display = 'block';
            detailLogoPlaceholder.style.display = 'none';
            detailLogoImg.onerror = () => {
                detailLogoImg.style.display = 'none';
                detailLogoPlaceholder.style.display = 'flex';
            };
        } else {
            detailLogoImg.style.display = 'none';
            detailLogoPlaceholder.style.display = 'flex';
        }

        // Information Grid
        detailNameGrid.textContent = brand.name || '—';
        detailCreatedGrid.textContent = brand.created ? brand.created.split(' ')[0] : '—';

        // Notes and Description (Markdown HTML)
        if (brand.description_html && brand.description_html.trim() !== '') {
            detailMarkdownContent.innerHTML = brand.description_html;
            detailMarkdownContent.style.display = 'block';
            detailNotesEmpty.style.display = 'none';
        } else {
            detailMarkdownContent.innerHTML = '';
            detailMarkdownContent.style.display = 'none';
            detailNotesEmpty.style.display = 'block';
        }

        // Render Associated Models (Paginated)
        renderDetailModels(brand.models || [], 1);

        // Breadcrumb updates: [Letter] > [Brand Name]
        breadcrumbLetter.textContent = state.selectedLetter;
        breadcrumbBrandName.textContent = brand.name || 'Brand';

        // Update URL state
        const url = new URL(window.location);
        url.searchParams.set('id', brand.id);
        window.history.pushState({ mode: 'detail', id: brand.id }, '', url);

        // Switch active view container
        listView.style.display = 'none';
        detailView.style.display = 'block';

        headerListMode.style.display = 'none';
        headerDetailMode.style.display = 'flex';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 6. Return to List Mode
    function returnToListMode() {
        state.mode = 'list';
        state.selectedBrandId = null;

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
        const { brands, letterCounts } = filterBrands();

        // Render Alphabet Bar
        renderAlphabetBar(letterCounts);

        // Update Content Header Box
        activeLetterDisplay.textContent = state.selectedLetter;
        activeCountDisplay.textContent = brands.length;

        // Update Sort Controls Display (Sort: A -> Z vs Sort: Z -> A)
        if (state.sortAsc) {
            if (sortIconAsc) sortIconAsc.style.display = 'inline-block';
            if (sortIconDesc) sortIconDesc.style.display = 'none';
            if (sortLabel) sortLabel.innerHTML = 'SORT: A &rarr; Z';
        } else {
            if (sortIconAsc) sortIconAsc.style.display = 'none';
            if (sortIconDesc) sortIconDesc.style.display = 'inline-block';
            if (sortLabel) sortLabel.innerHTML = 'SORT: Z &rarr; A';
        }

        // Render Cards if in List Mode
        if (state.mode === 'list') {
            renderCards(brands);
        }
    }

    // 8. Event Listeners for Filters
    let searchDebounceTimer = null;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            state.searchQuery = e.target.value;
            clearSearchBtn.style.display = state.searchQuery ? 'block' : 'none';
            if (state.mode === 'detail') returnToListMode();
            updateView();
        }, 120);
    });

    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        state.searchQuery = '';
        clearSearchBtn.style.display = 'none';
        if (state.mode === 'detail') returnToListMode();
        updateView();
        searchInput.focus();
    });

    function resetAllFilters() {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        state.searchQuery = '';
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

    sortBtn.addEventListener('click', () => {
        state.sortAsc = !state.sortAsc;
        updateView();
    });

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
    const initialId = initialUrlParams.get('id');

    updateView();

    if (initialId && allBrands.some(b => String(b.id) === String(initialId))) {
        openDetailMode(initialId);
    }
});
</script>

<?php
require __DIR__ . '/layout/footer.php';
?>
