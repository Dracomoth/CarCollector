<?php
/**
 * Generic Reusable Grid Controller
 * 
 * Provides robust tabular data presentation, multi-field filtering,
 * column sorting with visual indicators, and advanced pagination
 * (First, Previous, Numbers, Next, Last, Jump to Page, and Per-Page sizing).
 */

class GridController {
    protected string $id;
    protected string $formAction;
    protected array $columns = [];
    protected array $allowedPerPage = [10, 25, 50, 100];
    protected int $perPage = 25;
    protected int $currentPage = 1;
    protected ?string $sortColumn = null;
    protected string $sortOrder = 'asc';
    protected array $filters = [];
    protected string $globalSearch = '';
    protected bool $showSearch = true;
    protected bool $showResetButton = false;
    protected string $filterPosition = 'toolbar'; // 'toolbar', 'header_above', 'header_below'

    /** @var callable|string|null URL template or callback generating navigation URL for each row */
    protected $rowUrl = null;

    /** @var callable|null Callback returning custom CSS classes for each row */
    protected $rowClassCallback = null;

    protected array $allRows = [];
    protected array $filteredRows = [];
    protected array $pagedRows = [];
    protected int $totalRows = 0;
    protected int $totalPages = 1;
    protected int $startIndex = 0;
    protected int $endIndex = 0;
    protected bool $isProcessed = false;

    /**
     * @param array $config Configuration array
     *   - id: unique identifier for the grid (default: 'data-grid')
     *   - action: form action / page route (default: current URI path)
     *   - per_page: default items per page (default: 25)
     *   - allowed_per_page: array of available page sizes (default: [10, 25, 50, 100])
     *   - default_sort: default sort column key
     *   - default_order: 'asc' or 'desc' (default: 'asc')
     *   - show_search: bool whether to show global search bar in toolbar (default: true)
     *   - show_reset_button: bool whether to show reset filters button (default: false)
     *   - filter_position: 'toolbar'|'header_above'|'header_below' (default: 'toolbar')
     *   - row_url: callable|string navigation URL callback function($row) or string template '/path?id={id}'
     *   - row_class: callable callback returning custom CSS class for row function($row)
     */
    public function __construct(array $config = []) {
        $this->id = $config['id'] ?? 'grid_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        $this->formAction = $config['action'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        if (!empty($config['allowed_per_page']) && is_array($config['allowed_per_page'])) {
            $this->allowedPerPage = array_values(array_filter(array_map('intval', $config['allowed_per_page'])));
        }

        $this->perPage = isset($config['per_page']) && in_array((int)$config['per_page'], $this->allowedPerPage, true) 
            ? (int)$config['per_page'] 
            : 25;

        $this->sortColumn = $config['default_sort'] ?? null;
        $this->sortOrder = strtolower($config['default_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $this->showSearch = isset($config['show_search']) ? (bool)$config['show_search'] : true;
        $this->showResetButton = isset($config['show_reset_button']) ? (bool)$config['show_reset_button'] : false;
        $this->filterPosition = $config['filter_position'] ?? 'toolbar';

        if (isset($config['row_url'])) {
            $this->rowUrl = $config['row_url'];
        }
        if (isset($config['row_class']) && is_callable($config['row_class'])) {
            $this->rowClassCallback = $config['row_class'];
        }

        $this->loadRequestState();
    }

    /**
     * Parse and sanitize grid state from HTTP GET request
     */
    protected function loadRequestState(): void {
        // Page number
        if (isset($_GET['page'])) {
            $page = (int) $_GET['page'];
            $this->currentPage = max(1, $page);
        }

        // Per page size
        if (isset($_GET['per_page'])) {
            $pp = (int) $_GET['per_page'];
            if (in_array($pp, $this->allowedPerPage, true)) {
                $this->perPage = $pp;
            }
        }

        // Sorting
        if (!empty($_GET['sort'])) {
            $this->sortColumn = trim((string) $_GET['sort']);
        }
        if (!empty($_GET['order'])) {
            $order = strtolower(trim((string) $_GET['order']));
            $this->sortOrder = $order === 'desc' ? 'desc' : 'asc';
        }

        // Global search term
        if (isset($_GET['q'])) {
            $this->globalSearch = trim((string) $_GET['q']);
        }

        // Column-specific filters array: filter[col_key] = value
        if (isset($_GET['filter']) && is_array($_GET['filter'])) {
            foreach ($_GET['filter'] as $key => $value) {
                if ($value !== '' && $value !== null) {
                    $this->filters[(string) $key] = trim((string) $value);
                }
            }
        }
    }

    /**
     * Add a column definition to the grid
     * 
     * @param string $key Column field key (e.g. 'username', 'email')
     * @param string $label Header title
     * @param array $options Configuration options:
     *   - sortable: bool (default true)
     *   - filterable: bool (default true)
     *   - filter_type: 'text'|'select' (default 'text')
     *   - filter_options: array of [value => label] for select filters
     *   - value_getter: callable function($row): mixed
     *   - formatter: callable function($value, $row): string
     *   - filter_callback: callable function($rowValue, $filterValue, $row): bool
     *   - align: 'left'|'center'|'right' (default 'left')
     *   - header_class: string
     *   - cell_class: string
     *   - width: string (e.g. '150px', '20%')
     */
    public function addColumn(string $key, string $label, array $options = []): self {
        $this->columns[$key] = array_merge([
            'key'             => $key,
            'label'           => $label,
            'sortable'        => true,
            'filterable'      => true,
            'filter_type'     => 'text',
            'filter_options'  => [],
            'value_getter'    => null,
            'formatter'       => null,
            'filter_callback' => null,
            'align'           => 'left',
            'header_class'    => '',
            'cell_class'      => '',
            'width'           => null,
        ], $options);

        return $this;
    }

    /**
     * Set row navigation URL callback or template
     * e.g. function($row) { return '/brands?edit=' . $row['id']; }
     * or '/brands?edit={id}'
     */
    public function setRowUrl($callbackOrTemplate): self {
        $this->rowUrl = $callbackOrTemplate;
        return $this;
    }

    /**
     * Set custom row CSS class callback
     * e.g. function($row) { return $row['id'] == 1 ? 'is-selected' : ''; }
     */
    public function setRowClass(callable $callback): self {
        $this->rowClassCallback = $callback;
        return $this;
    }

    /**
     * Resolve navigation URL for a given row
     */
    public function resolveRowUrl(array $row): ?string {
        if (is_callable($this->rowUrl)) {
            $url = call_user_func($this->rowUrl, $row);
            return $url !== null ? (string)$url : null;
        }

        if (is_string($this->rowUrl) && $this->rowUrl !== '') {
            return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($m) use ($row) {
                return isset($row[$m[1]]) ? urlencode((string)$row[$m[1]]) : '';
            }, $this->rowUrl);
        }

        return null;
    }

    /**
     * Set multiple columns at once
     */
    public function setColumns(array $columns): self {
        $this->columns = [];
        foreach ($columns as $key => $col) {
            $label = $col['label'] ?? (string)$key;
            $this->addColumn($key, $label, $col);
        }
        return $this;
    }

    /**
     * Set the raw dataset to be filtered, sorted, and paginated
     */
    public function setData(array $rows): self {
        $this->allRows = array_values($rows);
        $this->isProcessed = false;
        return $this;
    }

    /**
     * Process filtering, sorting, and pagination
     */
    public function process(): self {
        $filtered = $this->allRows;

        // 1. Apply column-specific filters
        if (!empty($this->filters)) {
            $filtered = array_filter($filtered, function($row) {
                foreach ($this->filters as $colKey => $filterVal) {
                    if ($filterVal === '' || !isset($this->columns[$colKey])) {
                        continue;
                    }

                    $col = $this->columns[$colKey];
                    $rowVal = $this->extractCellValue($row, $col);

                    if (is_callable($col['filter_callback'])) {
                        if (!call_user_func($col['filter_callback'], $rowVal, $filterVal, $row)) {
                            return false;
                        }
                    } elseif ($col['filter_type'] === 'select') {
                        if ((string) $rowVal !== (string) $filterVal) {
                            return false;
                        }
                    } else {
                        // Case-insensitive substring match
                        if (stripos((string) $rowVal, (string) $filterVal) === false) {
                            return false;
                        }
                    }
                }
                return true;
            });
        }

        // 2. Apply global search query across all filterable columns
        if ($this->globalSearch !== '') {
            $search = $this->globalSearch;
            $filtered = array_filter($filtered, function($row) use ($search) {
                foreach ($this->columns as $col) {
                    if (empty($col['filterable'])) {
                        continue;
                    }
                    $rowVal = $this->extractCellValue($row, $col);
                    if (stripos((string) $rowVal, $search) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $this->filteredRows = array_values($filtered);
        $this->totalRows = count($this->filteredRows);

        // 3. Apply sorting
        if ($this->sortColumn !== null && isset($this->columns[$this->sortColumn])) {
            $sortCol = $this->columns[$this->sortColumn];
            if (!empty($sortCol['sortable'])) {
                $dir = $this->sortOrder === 'desc' ? -1 : 1;
                usort($this->filteredRows, function($a, $b) use ($sortCol, $dir) {
                    $valA = $this->extractCellValue($a, $sortCol);
                    $valB = $this->extractCellValue($b, $sortCol);

                    if (is_numeric($valA) && is_numeric($valB)) {
                        return ($valA <=> $valB) * $dir;
                    }
                    return strnatcasecmp((string)$valA, (string)$valB) * $dir;
                });
            }
        }

        // 4. Calculate pagination bounds
        $this->totalPages = (int) max(1, ceil($this->totalRows / $this->perPage));
        if ($this->currentPage > $this->totalPages) {
            $this->currentPage = $this->totalPages;
        }

        $offset = ($this->currentPage - 1) * $this->perPage;
        $this->pagedRows = array_slice($this->filteredRows, $offset, $this->perPage);

        $this->startIndex = $this->totalRows > 0 ? $offset + 1 : 0;
        $this->endIndex = min($offset + $this->perPage, $this->totalRows);
        $this->isProcessed = true;

        return $this;
    }

    /**
     * Helper to extract a cell value from a row using column definition
     */
    protected function extractCellValue(array $row, array $col): mixed {
        if (is_callable($col['value_getter'])) {
            return call_user_func($col['value_getter'], $row);
        }
        return $row[$col['key']] ?? '';
    }

    /**
     * Get paginated rows for the current view
     */
    public function getRows(): array {
        if (!$this->isProcessed) {
            $this->process();
        }
        return $this->pagedRows;
    }

    public function getTotalRows(): int {
        return $this->totalRows;
    }

    public function getTotalPages(): int {
        return $this->totalPages;
    }

    public function getCurrentPage(): int {
        return $this->currentPage;
    }

    public function getPerPage(): int {
        return $this->perPage;
    }

    public function getStartIndex(): int {
        return $this->startIndex;
    }

    public function getEndIndex(): int {
        return $this->endIndex;
    }

    public function getSortColumn(): ?string {
        return $this->sortColumn;
    }

    public function getSortOrder(): string {
        return $this->sortOrder;
    }

    public function getColumns(): array {
        return $this->columns;
    }

    public function getFilters(): array {
        return $this->filters;
    }

    public function getGlobalSearch(): string {
        return $this->globalSearch;
    }

    public function getAllowedPerPage(): array {
        return $this->allowedPerPage;
    }

    /**
     * Build URL preserving query parameters with overrides
     */
    public function buildUrl(array $overrides = []): string {
        $params = $_GET;

        foreach ($overrides as $k => $v) {
            if ($v === null) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }

        // Clean empty filter array
        if (isset($params['filter']) && is_array($params['filter'])) {
            $params['filter'] = array_filter($params['filter'], fn($val) => $val !== '' && $val !== null);
            if (empty($params['filter'])) {
                unset($params['filter']);
            }
        }

        $query = http_build_query($params);
        return $this->formAction . ($query !== '' ? '?' . $query : '');
    }

    /**
     * Build URL for toggling sort on a column
     */
    public function buildSortUrl(string $colKey): string {
        $newOrder = ($this->sortColumn === $colKey && $this->sortOrder === 'asc') ? 'desc' : 'asc';
        return $this->buildUrl([
            'sort'  => $colKey,
            'order' => $newOrder,
            'page'  => 1, // Reset to page 1 on sort change
        ]);
    }

    /**
     * Render complete Grid HTML (Toolbar, Table with Field Filters, and Pagination)
     */
    public function render(): string {
        if (!$this->isProcessed) {
            $this->process();
        }

        ob_start();
        ?>
        <div class="grid-container" id="<?= htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') ?>">
            <!-- Grid Toolbar (Global Search, Active Filters Summary, Per-Page Dropdown) -->
            <?= $this->renderToolbar() ?>

            <!-- Table with Headers & Field-Specific Filters -->
            <?= $this->renderTable() ?>

            <!-- Grid Footer & Pagination Controls -->
            <?= $this->renderPagination() ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Render the top toolbar (Search box, filter count, per page select)
     */
    public function renderToolbar(): string {
        $hasActiveFilters = !empty($this->filters) || $this->globalSearch !== '';
        $resetUrl = $this->buildUrl(['q' => null, 'filter' => null, 'page' => 1]);

        // Filterable columns
        $filterableCols = array_filter($this->columns, fn($c) => !empty($c['filterable']));

        ob_start();
        ?>
        <div class="grid-toolbar">
            <div class="grid-toolbar-left">
                <?php if ($this->filterPosition === 'toolbar' && !empty($filterableCols)): ?>
                    <!-- Toolbar Column Filters (Above column headers, with the items selector) -->
                    <form method="GET" action="<?= htmlspecialchars($this->formAction, ENT_QUOTES, 'UTF-8') ?>" class="grid-search-form grid-toolbar-filters-form">
                        <?php foreach ($_GET as $k => $v): ?>
                            <?php if (!in_array($k, ['filter', 'page'], true) && !is_array($v)): ?>
                                <input type="hidden" name="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="grid-toolbar-filters">
                            <?php foreach ($filterableCols as $col): ?>
                                <?php
                                $filterVal = $this->filters[$col['key']] ?? '';
                                $fieldName = "filter[" . htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') . "]";
                                $clearUrl = $this->buildUrl(['filter' => [$col['key'] => null], 'page' => 1]);
                                ?>
                                <?php if ($col['filter_type'] === 'select'): ?>
                                    <div class="grid-filter-select-wrap">
                                        <select name="<?= $fieldName ?>" 
                                                class="grid-search-input grid-filter-select"
                                                onchange="this.form.submit()">
                                            <option value="">All <?= htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php foreach ($col['filter_options'] as $optVal => $optLabel): ?>
                                                <option value="<?= htmlspecialchars((string)$optVal, ENT_QUOTES, 'UTF-8') ?>" 
                                                        <?= (string)$filterVal === (string)$optVal ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <div class="grid-search-wrap <?= $filterVal !== '' ? 'has-clear' : '' ?>">
                                        <input type="text" 
                                               name="<?= $fieldName ?>" 
                                               value="<?= htmlspecialchars($filterVal, ENT_QUOTES, 'UTF-8') ?>" 
                                               placeholder="Filter by <?= htmlspecialchars(strtolower($col['label']), ENT_QUOTES, 'UTF-8') ?>..." 
                                               class="grid-search-input grid-filter-input grid-filter-keypress"
                                               data-col-key="<?= htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') ?>"
                                               autocomplete="off"
                                               onkeydown="if(event.key==='Enter'){this.form.submit();}">
                                        <svg class="grid-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                        <?php if ($filterVal !== ''): ?>
                                            <a href="<?= htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8') ?>" class="grid-search-clear" title="Clear filter">&times;</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php elseif ($this->showSearch): ?>
                    <!-- Global Quick Search Form -->
                    <form method="GET" action="<?= htmlspecialchars($this->formAction, ENT_QUOTES, 'UTF-8') ?>" class="grid-search-form">
                        <?php foreach ($_GET as $k => $v): ?>
                            <?php if (!in_array($k, ['q', 'page'], true) && !is_array($v)): ?>
                                <input type="hidden" name="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="grid-search-wrap <?= $this->globalSearch !== '' ? 'has-clear' : '' ?>">
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($this->globalSearch, ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="Search all columns..." 
                                   class="grid-search-input">
                            <svg class="grid-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <?php if ($this->globalSearch !== ''): ?>
                                <a href="<?= htmlspecialchars($this->buildUrl(['q' => null, 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>" class="grid-search-clear" title="Clear search">&times;</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($this->showResetButton && $hasActiveFilters): ?>
                    <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline btn-xs btn-warn grid-reset-btn">
                        ✕ Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <div class="grid-toolbar-right">
                <!-- Records Per Page Selector (Number of Items Selector) -->
                <form method="GET" action="<?= htmlspecialchars($this->formAction, ENT_QUOTES, 'UTF-8') ?>" class="grid-per-page-form">
                    <?php foreach ($_GET as $k => $v): ?>
                        <?php if ($k === 'filter' && is_array($v)): ?>
                            <?php foreach ($v as $fk => $fv): ?>
                                <?php if ($fv !== '' && $fv !== null): ?>
                                    <input type="hidden" name="filter[<?= htmlspecialchars((string)$fk, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars((string)$fv, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php elseif (!in_array($k, ['per_page', 'page'], true) && !is_array($v)): ?>
                            <input type="hidden" name="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label class="grid-per-page-label">
                        <span>Show</span>
                        <select name="per_page" class="grid-per-page-select" onchange="this.form.submit()">
                            <?php foreach ($this->allowedPerPage as $opt): ?>
                                <option value="<?= $opt ?>" <?= $this->perPage === $opt ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span>entries</span>
                    </label>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Render the main table with sort headers and column filter inputs
     */
    public function renderTable(): string {
        ob_start();
        ?>
        <form method="GET" action="<?= htmlspecialchars($this->formAction, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') ?>_filter_form">
            <?php foreach ($_GET as $k => $v): ?>
                <?php if (!in_array($k, ['filter', 'page'], true) && !is_array($v)): ?>
                    <input type="hidden" name="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="table-responsive">
                <table class="table grid-table">
                    <thead>
                        <?php if ($this->filterPosition === 'header_above'): ?>
                            <!-- Field-Specific Filter Inputs Row ABOVE Headers -->
                            <tr class="grid-filter-row">
                                <?php foreach ($this->columns as $col): ?>
                                    <?php $alignClass = 'text-' . $col['align']; ?>
                                    <th class="<?= htmlspecialchars($alignClass, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (!empty($col['filterable'])): ?>
                                            <?php 
                                            $filterVal = $this->filters[$col['key']] ?? ''; 
                                            $fieldName = "filter[" . htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') . "]";
                                            ?>
                                            <?php if ($col['filter_type'] === 'select'): ?>
                                                <select name="<?= $fieldName ?>" 
                                                        class="grid-filter-input grid-filter-select"
                                                        onchange="this.form.submit()">
                                                    <option value="">All</option>
                                                    <?php foreach ($col['filter_options'] as $optVal => $optLabel): ?>
                                                        <option value="<?= htmlspecialchars((string)$optVal, ENT_QUOTES, 'UTF-8') ?>" 
                                                                <?= (string)$filterVal === (string)$optVal ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <div class="grid-input-clearable">
                                                    <input type="text" 
                                                           name="<?= $fieldName ?>" 
                                                           value="<?= htmlspecialchars($filterVal, ENT_QUOTES, 'UTF-8') ?>" 
                                                           placeholder="Filter..." 
                                                           class="grid-filter-input grid-filter-keypress"
                                                           data-col-key="<?= htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') ?>"
                                                           autocomplete="off"
                                                           onkeydown="if(event.key==='Enter'){this.form.submit();}">
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>

                        <!-- Column Header Titles with Sort Toggles -->
                        <tr class="grid-header-row">
                            <?php foreach ($this->columns as $col): ?>
                                <?php
                                $isSorted = ($this->sortColumn === $col['key']);
                                $alignClass = 'text-' . $col['align'];
                                $widthAttr = !empty($col['width']) ? 'style="width: ' . htmlspecialchars($col['width'], ENT_QUOTES, 'UTF-8') . '"' : '';
                                ?>
                                <th class="<?= htmlspecialchars($alignClass . ' ' . $col['header_class'], ENT_QUOTES, 'UTF-8') ?>" <?= $widthAttr ?> data-col-key="<?= htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (!empty($col['sortable'])): ?>
                                        <a href="<?= htmlspecialchars($this->buildSortUrl($col['key']), ENT_QUOTES, 'UTF-8') ?>" 
                                           class="grid-sort-link <?= $isSorted ? 'active-sort' : '' ?>"
                                           title="Sort by <?= htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span><?= htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="grid-sort-icon">
                                                <?php if ($isSorted): ?>
                                                    <?= $this->sortOrder === 'asc' ? '▲' : '▼' ?>
                                                <?php else: ?>
                                                    <span class="grid-sort-idle">⇅</span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <span><?= htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>

                        <?php if ($this->filterPosition === 'header_below'): ?>
                            <!-- Field-Specific Filter Inputs Row BELOW Headers -->
                            <tr class="grid-filter-row">
                                <?php foreach ($this->columns as $col): ?>
                                    <?php $alignClass = 'text-' . $col['align']; ?>
                                    <th class="<?= htmlspecialchars($alignClass, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (!empty($col['filterable'])): ?>
                                            <?php 
                                            $filterVal = $this->filters[$col['key']] ?? ''; 
                                            $fieldName = "filter[" . htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') . "]";
                                            ?>
                                            <?php if ($col['filter_type'] === 'select'): ?>
                                                <select name="<?= $fieldName ?>" 
                                                        class="grid-filter-input grid-filter-select"
                                                        onchange="this.form.submit()">
                                                    <option value="">All</option>
                                                    <?php foreach ($col['filter_options'] as $optVal => $optLabel): ?>
                                                        <option value="<?= htmlspecialchars((string)$optVal, ENT_QUOTES, 'UTF-8') ?>" 
                                                                <?= (string)$filterVal === (string)$optVal ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <div class="grid-input-clearable">
                                                    <input type="text" 
                                                           name="<?= $fieldName ?>" 
                                                           value="<?= htmlspecialchars($filterVal, ENT_QUOTES, 'UTF-8') ?>" 
                                                           placeholder="Filter..." 
                                                           class="grid-filter-input grid-filter-keypress"
                                                           data-col-key="<?= htmlspecialchars($col['key'], ENT_QUOTES, 'UTF-8') ?>"
                                                           autocomplete="off"
                                                           onkeydown="if(event.key==='Enter'){this.form.submit();}">
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if (empty($this->pagedRows)): ?>
                            <tr class="grid-empty-row">
                                <td colspan="<?= count($this->columns) ?>" class="text-center text-muted grid-empty-cell">
                                    <div class="grid-empty-content">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                        <span>No matching records found.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->pagedRows as $row): ?>
                                <?php
                                $navUrl = $this->resolveRowUrl($row);
                                $rowClasses = ['grid-data-row'];
                                if ($navUrl !== null && $navUrl !== '') {
                                    $rowClasses[] = 'is-clickable';
                                }
                                if (is_callable($this->rowClassCallback)) {
                                    $customClass = call_user_func($this->rowClassCallback, $row);
                                    if (!empty($customClass)) {
                                        $rowClasses[] = (string)$customClass;
                                    }
                                }
                                $rowClassesStr = implode(' ', array_unique($rowClasses));
                                $rowId = $row['id'] ?? null;
                                ?>
                                <tr class="<?= htmlspecialchars($rowClassesStr, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $rowId !== null ? 'data-id="' . htmlspecialchars((string)$rowId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                                    data-row='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'
                                    <?= $navUrl !== null && $navUrl !== '' ? 'data-href="' . htmlspecialchars($navUrl, ENT_QUOTES, 'UTF-8') . '" tabindex="0" role="link"' : '' ?>>
                                    <?php foreach ($this->columns as $col): ?>
                                        <?php
                                        $cellValue = $this->extractCellValue($row, $col);
                                        $renderedOutput = is_callable($col['formatter']) 
                                            ? call_user_func($col['formatter'], $cellValue, $row) 
                                            : htmlspecialchars((string)$cellValue, ENT_QUOTES, 'UTF-8');
                                        $alignClass = 'text-' . $col['align'];
                                        ?>
                                        <td class="<?= htmlspecialchars($alignClass . ' ' . $col['cell_class'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= $renderedOutput ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <script>
        (function() {
            var gridContainer = document.getElementById('<?= htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') ?>');
            if (!gridContainer) return;

            // 1. Live keypress filtering (finds column by data-col-key or th cellIndex)
            var keypressInputs = gridContainer.querySelectorAll('.grid-filter-keypress');
            keypressInputs.forEach(function(input) {
                var colKey = input.getAttribute('data-col-key');
                var colIndex = -1;
                if (colKey) {
                    var headerTh = gridContainer.querySelector('.grid-header-row th[data-col-key="' + colKey + '"]');
                    if (headerTh) {
                        colIndex = headerTh.cellIndex;
                    }
                }
                if (colIndex < 0) {
                    var colTh = input.closest('th');
                    if (colTh) colIndex = colTh.cellIndex;
                }

                input.addEventListener('input', function() {
                    var filterVal = input.value.trim().toLowerCase();
                    var tbody = gridContainer.querySelector('.grid-table tbody');
                    if (!tbody) return;

                    var rows = tbody.querySelectorAll('tr.grid-data-row');
                    var visibleCount = 0;

                    rows.forEach(function(row) {
                        var cell = (colIndex >= 0 && colIndex < row.cells.length) ? row.cells[colIndex] : null;
                        var cellText = (cell ? cell.textContent : '').toLowerCase();

                        if (filterVal === '' || cellText.indexOf(filterVal) !== -1) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Live empty feedback row if nothing matches
                    var liveEmpty = tbody.querySelector('.grid-live-empty-row');
                    if (visibleCount === 0 && rows.length > 0) {
                        if (!liveEmpty) {
                            liveEmpty = document.createElement('tr');
                            liveEmpty.className = 'grid-live-empty-row';
                            var colsCount = rows[0].cells.length;
                            liveEmpty.innerHTML = '<td colspan="' + colsCount + '" class="text-center text-muted" style="padding: 1.5rem;">No matching records found for "' + filterVal + '".</td>';
                            tbody.appendChild(liveEmpty);
                        } else {
                            liveEmpty.style.display = '';
                            liveEmpty.querySelector('td').textContent = 'No matching records found for "' + filterVal + '".';
                        }
                    } else if (liveEmpty) {
                        liveEmpty.style.display = 'none';
                    }
                });
            });

            // 2. Entire Row Click & Keyboard Navigation
            gridContainer.addEventListener('click', function(e) {
                // Ignore clicks on form controls, buttons, checkboxes, inputs, or inside actions cell
                if (e.target.closest('input, select, textarea, button, label, .actions-cell, [data-no-row-click]')) {
                    return;
                }

                var row = e.target.closest('tr.grid-data-row');
                if (!row) return;

                var href = row.getAttribute('data-href');
                if (!href) {
                    // Fallback auto-detection: first non-action link in the row
                    var link = row.querySelector('td:not(.actions-cell) a[href]');
                    if (link) {
                        href = link.getAttribute('href');
                    }
                }

                var rowData = {};
                try {
                    var rawData = row.getAttribute('data-row');
                    if (rawData) rowData = JSON.parse(rawData);
                } catch(err) {}

                // Dispatch custom row-select event allowing consumers to intercept without page reload
                var customEvt = new CustomEvent('grid:row-select', {
                    bubbles: true,
                    cancelable: true,
                    detail: {
                        row: row,
                        href: href,
                        id: row.getAttribute('data-id'),
                        data: rowData,
                        originalEvent: e
                    }
                });

                var notCancelled = gridContainer.dispatchEvent(customEvt);
                if (!notCancelled) {
                    // Consumer handled the row selection without page reload!
                    return;
                }

                if (href) {
                    var clickedAnchor = e.target.closest('a');
                    if (clickedAnchor && clickedAnchor.getAttribute('href') === href) {
                        // User directly clicked an anchor pointing to the same href; default browser action proceeds
                        return;
                    }
                    if (e.ctrlKey || e.metaKey) {
                        window.open(href, '_blank');
                    } else {
                        window.location.href = href;
                    }
                }
            });

            gridContainer.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    var row = document.activeElement;
                    if (row && row.classList.contains('grid-data-row')) {
                        var href = row.getAttribute('data-href');
                        if (!href) {
                            var link = row.querySelector('td:not(.actions-cell) a[href]');
                            if (link) href = link.getAttribute('href');
                        }

                        var rowData = {};
                        try {
                            var rawData = row.getAttribute('data-row');
                            if (rawData) rowData = JSON.parse(rawData);
                        } catch(err) {}

                        var customEvt = new CustomEvent('grid:row-select', {
                            bubbles: true,
                            cancelable: true,
                            detail: {
                                row: row,
                                href: href,
                                id: row.getAttribute('data-id'),
                                data: rowData,
                                originalEvent: e
                            }
                        });

                        var notCancelled = gridContainer.dispatchEvent(customEvt);
                        if (!notCancelled) {
                            e.preventDefault();
                            return;
                        }

                        if (href) {
                            e.preventDefault();
                            window.location.href = href;
                        }
                    }
                }
            });
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Render pagination controls (First, Prev, Page numbers, Next, Last, Jump to page)
     */
    public function renderPagination(): string {
        $cp = $this->currentPage;
        $tp = $this->totalPages;
        $hasPrev = $cp > 1;
        $hasNext = $cp < $tp;

        ob_start();
        ?>
        <div class="grid-pagination-container">
            <!-- Records Summary Info -->
            <div class="grid-pagination-info">
                <?php if ($this->totalRows > 0): ?>
                    Showing <strong class="text-highlight"><?= $this->startIndex ?></strong> to 
                    <strong class="text-highlight"><?= $this->endIndex ?></strong> of 
                    <strong class="text-highlight"><?= $this->totalRows ?></strong> entries
                    <?php if (count($this->allRows) !== $this->totalRows): ?>
                        <span class="text-muted">(filtered from <?= count($this->allRows) ?> total)</span>
                    <?php endif; ?>
                <?php else: ?>
                    No entries found
                <?php endif; ?>
            </div>

            <!-- Pagination Navigation & Jump Controls -->
            <div class="grid-pagination-controls">
                <!-- Navigation Buttons: First, Prev, Numbers, Next, Last -->
                <nav class="grid-page-nav" aria-label="Table pagination">
                    <!-- First Page -->
                    <a href="<?= $hasPrev ? htmlspecialchars($this->buildUrl(['page' => 1]), ENT_QUOTES, 'UTF-8') : 'javascript:void(0)' ?>" 
                       class="grid-page-btn <?= !$hasPrev ? 'disabled' : '' ?>" 
                       aria-label="First Page" 
                       title="First Page">
                        &laquo; First
                    </a>

                    <!-- Previous Page -->
                    <a href="<?= $hasPrev ? htmlspecialchars($this->buildUrl(['page' => $cp - 1]), ENT_QUOTES, 'UTF-8') : 'javascript:void(0)' ?>" 
                       class="grid-page-btn <?= !$hasPrev ? 'disabled' : '' ?>" 
                       aria-label="Previous Page" 
                       title="Previous Page">
                        &lsaquo; Prev
                    </a>

                    <!-- Sliding Window Page Numbers -->
                    <?php
                    $range = 2; // Pages to display on each side of current
                    $startPage = max(1, $cp - $range);
                    $endPage = min($tp, $cp + $range);

                    if ($startPage > 1) {
                        echo '<a href="' . htmlspecialchars($this->buildUrl(['page' => 1]), ENT_QUOTES, 'UTF-8') . '" class="grid-page-btn">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="grid-page-ellipsis">&hellip;</span>';
                        }
                    }

                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $isActive = ($i === $cp);
                        ?>
                        <a href="<?= htmlspecialchars($this->buildUrl(['page' => $i]), ENT_QUOTES, 'UTF-8') ?>" 
                           class="grid-page-btn <?= $isActive ? 'active' : '' ?>"
                           <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= $i ?>
                        </a>
                        <?php
                    }

                    if ($endPage < $tp) {
                        if ($endPage < $tp - 1) {
                            echo '<span class="grid-page-ellipsis">&hellip;</span>';
                        }
                        echo '<a href="' . htmlspecialchars($this->buildUrl(['page' => $tp]), ENT_QUOTES, 'UTF-8') . '" class="grid-page-btn">' . $tp . '</a>';
                    }
                    ?>

                    <!-- Next Page -->
                    <a href="<?= $hasNext ? htmlspecialchars($this->buildUrl(['page' => $cp + 1]), ENT_QUOTES, 'UTF-8') : 'javascript:void(0)' ?>" 
                       class="grid-page-btn <?= !$hasNext ? 'disabled' : '' ?>" 
                       aria-label="Next Page" 
                       title="Next Page">
                        Next &rsaquo;
                    </a>

                    <!-- Last Page -->
                    <a href="<?= $hasNext ? htmlspecialchars($this->buildUrl(['page' => $tp]), ENT_QUOTES, 'UTF-8') : 'javascript:void(0)' ?>" 
                       class="grid-page-btn <?= !$hasNext ? 'disabled' : '' ?>" 
                       aria-label="Last Page" 
                       title="Last Page">
                        Last &raquo;
                    </a>
                </nav>

                <!-- Jump to Specific Page Control -->
                <form method="GET" action="<?= htmlspecialchars($this->formAction, ENT_QUOTES, 'UTF-8') ?>" class="grid-jump-form">
                    <?php foreach ($_GET as $k => $v): ?>
                        <?php if ($k !== 'page' && !is_array($v)): ?>
                            <input type="hidden" name="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <span class="grid-jump-label">Page</span>
                    <input type="number" 
                           name="page" 
                           min="1" 
                           max="<?= $tp ?>" 
                           value="<?= $cp ?>" 
                           class="grid-jump-input" 
                           aria-label="Jump to page number">
                    <span class="grid-jump-label">of <?= $tp ?></span>
                    <button type="submit" class="btn btn-outline btn-xs grid-jump-btn">Go</button>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
