<?php
/**
 * Collection Analytics Dashboard View
 * 
 * High-octane motorsport & collection telemetry console featuring:
 * - Brand distribution interactive SVG donut pie chart with percentages
 * - Key collection metrics & KPI cards
 * - Scale proportions & casting size breakdowns
 * - Model condition & packaging quality metrics
 * - Exterior color palette spectrum
 * - Themed collection showcases
 * - Recent garage acquisitions
 */

$pageTitle = 'Collection Dashboard - CarCollector';
$currentUser = $currentUser ?? $user ?? ($_SESSION['user'] ?? []);

require __DIR__ . '/layout/header.php';

// Prepare SVG Donut metrics
$brandList = $brandDistribution['brands'] ?? [];
$totalModels = (int) ($summaryStats['total_models'] ?? 0);
$brandCount = count($brandList);
$donutCircumference = 2 * M_PI * 65; // ~408.407
?>

<main class="container page-content dashboard-page" id="dashboard-page">

    <!-- ======================================================================= -->
    <!-- Hero Header & Quick Action Launchpad -->
    <!-- ======================================================================= -->
    <header class="dashboard-hero card">
        <div class="dashboard-hero-content">
            <div class="dashboard-hero-title-group">
                <div class="dashboard-badge-pill">
                    <span class="pulse-indicator"></span>
                    <span>Fleet Telemetry Console</span>
                </div>
                <h1 class="dashboard-title">Collection Dashboard</h1>
                <p class="dashboard-subtitle">
                    Comprehensive overview, brand distribution analytics, scale ratios, and catalog highlights for your scale car collection.
                </p>
            </div>
            <div class="dashboard-hero-actions">
                <?php if (!empty($canAccessGarage)): ?>
                    <a href="/garage" class="btn btn-primary dashboard-action-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.1 10.9 2 11.2 2 11.5V16c0 .6.4 1 1 1h2"></path>
                            <circle cx="7" cy="17" r="2"></circle>
                            <circle cx="17" cy="17" r="2"></circle>
                            <path d="M9 17h6"></path>
                            <path d="M2 7l10-4 10 4"></path>
                        </svg>
                        <span>Open Garage</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($canAccessModels)): ?>
                    <a href="/models" class="btn btn-secondary dashboard-action-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Car</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($canAccessBrandsGallery)): ?>
                    <a href="/brands_gallery" class="btn btn-secondary dashboard-action-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        <span>Brands</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($canAccessCollectionsGallery)): ?>
                    <a href="/collections_gallery" class="btn btn-secondary dashboard-action-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path>
                            <path d="M7 3h10a2 2 0 0 1 2 2v1H5V5a2 2 0 0 1 2-2Z"></path>
                        </svg>
                        <span>Collections</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ======================================================================= -->
    <!-- Top KPI Telemetry Cards -->
    <!-- ======================================================================= -->
    <section class="dashboard-kpi-grid" aria-label="Key Performance Indicators">
        <!-- 1. Total Models -->
        <a href="/garage" class="dashboard-kpi-card" title="View all models in Garage">
            <div class="kpi-header">
                <span class="kpi-label">Total Diecasts</span>
                <span class="kpi-icon-box text-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="7" cy="17" r="2"></circle>
                        <circle cx="17" cy="17" r="2"></circle>
                        <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                </span>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?= number_format($totalModels) ?></span>
                <span class="kpi-unit"><?= $totalModels === 1 ? 'Car' : 'Cars' ?></span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-status-badge badge-success">Active Fleet</span>
                <span class="kpi-link-hint">Garage Showcase &rarr;</span>
            </div>
        </a>

        <!-- 2. Brands Represented -->
        <a href="/brands_gallery" class="dashboard-kpi-card" title="Browse Brands Gallery">
            <div class="kpi-header">
                <span class="kpi-label">Brands Represented</span>
                <span class="kpi-icon-box text-warning">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                </span>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?= number_format((int)$summaryStats['total_brands']) ?></span>
                <span class="kpi-unit"><?= (int)$summaryStats['total_brands'] === 1 ? 'Brand' : 'Brands' ?></span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-subtext">Diecast labels in garage</span>
                <span class="kpi-link-hint">Brands Gallery &rarr;</span>
            </div>
        </a>

        <!-- 3. Manufacturers / Makers -->
        <a href="/makers_gallery" class="dashboard-kpi-card" title="Browse Makers Gallery">
            <div class="kpi-header">
                <span class="kpi-label">Manufacturers (Makers)</span>
                <span class="kpi-icon-box text-info">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path>
                        <path d="M17 18h1"></path><path d="M12 18h1"></path><path d="M7 18h1"></path>
                    </svg>
                </span>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?= number_format((int)$summaryStats['total_makers']) ?></span>
                <span class="kpi-unit"><?= (int)$summaryStats['total_makers'] === 1 ? 'Maker' : 'Makers' ?></span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-subtext">Automotive manufacturers</span>
                <span class="kpi-link-hint">Makers Gallery &rarr;</span>
            </div>
        </a>

        <!-- 4. Curated Collections -->
        <a href="/collections_gallery" class="dashboard-kpi-card" title="Browse Custom Collections">
            <div class="kpi-header">
                <span class="kpi-label">Custom Collections</span>
                <span class="kpi-icon-box text-purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path>
                        <path d="M7 3h10a2 2 0 0 1 2 2v1H5V5a2 2 0 0 1 2-2Z"></path>
                        <circle cx="8" cy="11" r="1.5"></circle>
                        <path d="m21 16-5-5-5 5"></path>
                    </svg>
                </span>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?= number_format((int)$summaryStats['total_collections']) ?></span>
                <span class="kpi-unit"><?= (int)$summaryStats['total_collections'] === 1 ? 'Set' : 'Sets' ?></span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-subtext">Themed curations</span>
                <span class="kpi-link-hint">Collections &rarr;</span>
            </div>
        </a>

        <!-- 5. Dominant Scale -->
        <a href="/scales" class="dashboard-kpi-card" title="View Scale Metadata">
            <div class="kpi-header">
                <span class="kpi-label">Dominant Scale</span>
                <span class="kpi-icon-box text-emerald">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-6-6a1 1 0 0 1 0-1.4L13.9 1.3a1 1 0 0 1 1.4 0l6 6a1 1 0 0 1 0 1.4Z"></path>
                        <path d="m14.5 4.5 2 2"></path><path d="m11.5 7.5 2 2"></path><path d="m8.5 10.5 2 2"></path>
                    </svg>
                </span>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?= htmlspecialchars($summaryStats['primary_scale'] ?: '1:64') ?></span>
            </div>
            <div class="kpi-footer">
                <span class="kpi-subtext">
                    <?= !empty($summaryStats['primary_scale_count']) ? htmlspecialchars($summaryStats['primary_scale_count'] . ' models in scale') : 'Standard scale' ?>
                </span>
                <span class="kpi-link-hint">Scales &rarr;</span>
            </div>
        </a>
    </section>

    <!-- ======================================================================= -->
    <!-- Two-Column Primary Analytics Grid -->
    <!-- ======================================================================= -->
    <div class="dashboard-grid-2col">

        <!-- =================================================================== -->
        <!-- LEFT COLUMN: Brand Distribution & Condition Breakdown -->
        <!-- =================================================================== -->
        <div class="dashboard-col">

            <!-- CARD: Brand Distribution (The User's Core Pie Chart Requirement) -->
            <section class="card dashboard-card dashboard-brand-card">
                <div class="dashboard-card-header">
                    <div>
                        <div class="dashboard-card-tag">Collection Share</div>
                        <h2 class="dashboard-card-title">Brand Distribution</h2>
                    </div>
                    <span class="badge badge-primary">By Brand</span>
                </div>
                <p class="dashboard-card-desc">
                    Interactive proportion breakdown of diecast brands in your garage. Hover over chart slices or legend items to inspect telemetry. Click to filter Garage.
                </p>

                <?php if ($totalModels === 0 || empty($brandList)): ?>
                    <div class="dashboard-empty-chart">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10"></path>
                        </svg>
                        <p class="empty-title">No Brands in Collection Yet</p>
                        <p class="text-muted text-sm">Add models to your collection to populate the real-time brand distribution chart.</p>
                        <a href="/models" class="btn btn-primary btn-sm" style="margin-top: 0.75rem;">Add Model</a>
                    </div>
                <?php else: ?>
                    <div class="dashboard-donut-container">
                        <!-- SVG Donut Chart -->
                        <div class="donut-svg-wrap">
                            <svg class="dashboard-donut-svg" id="dashboard-donut-svg" viewBox="0 0 200 200" aria-label="Brand distribution pie chart">
                                <!-- Base background ring -->
                                <circle cx="100" cy="100" r="65" fill="none" stroke="var(--color-surface-hover)" stroke-width="26"></circle>

                                <!-- Interactive Brand Slices -->
                                <?php 
                                foreach ($brandList as $brand): 
                                    $pct = (float) $brand['percentage'];
                                    $sliceLength = ($pct / 100) * $donutCircumference;
                                    // When multiple slices exist, add a subtle gap
                                    $gap = $brandCount > 1 ? 1.5 : 0;
                                    $adjustedLength = max(0.5, $sliceLength - $gap);
                                    $offset = - (($brand['accumulated_pct'] / 100) * $donutCircumference);
                                    $url = $brand['brand_id'] ? "/garage?brand_id={$brand['brand_id']}" : "/garage";
                                ?>
                                    <circle cx="100" cy="100" r="65" fill="transparent" 
                                            stroke="<?= htmlspecialchars($brand['color']) ?>" 
                                            stroke-width="26"
                                            stroke-dasharray="<?= sprintf('%.3f %.3f', $adjustedLength, $donutCircumference) ?>"
                                            stroke-dashoffset="<?= sprintf('%.3f', $offset) ?>"
                                            transform="rotate(-90 100 100)"
                                            class="donut-slice"
                                            data-brand-id="<?= htmlspecialchars((string)($brand['brand_id'] ?? '0')) ?>"
                                            data-brand-name="<?= htmlspecialchars($brand['brand_name']) ?>"
                                            data-count="<?= (int)$brand['model_count'] ?>"
                                            data-pct="<?= sprintf('%.1f', $pct) ?>"
                                            data-color="<?= htmlspecialchars($brand['color']) ?>"
                                            data-url="<?= htmlspecialchars($url) ?>"
                                            role="button"
                                            tabindex="0"
                                            aria-label="<?= htmlspecialchars($brand['brand_name']) ?>: <?= (int)$brand['model_count'] ?> models (<?= sprintf('%.1f', $pct) ?>%)">
                                    </circle>
                                <?php endforeach; ?>

                                <!-- Center Readout Display -->
                                <g class="donut-center-group">
                                    <text x="100" y="86" text-anchor="middle" class="donut-center-metric" id="donut-center-metric" data-default-color="var(--color-text)">
                                        <?= number_format($totalModels) ?>
                                    </text>
                                    <text x="100" y="104" text-anchor="middle" class="donut-center-label" id="donut-center-label">
                                        Total Models
                                    </text>
                                    <text x="100" y="120" text-anchor="middle" class="donut-center-sub" id="donut-center-sub">
                                        <?= $brandCount ?> <?= $brandCount === 1 ? 'Brand' : 'Brands' ?>
                                    </text>
                                </g>
                            </svg>
                        </div>

                        <!-- Brand Legend & Table -->
                        <div class="dashboard-brand-legend" id="dashboard-brand-legend">
                            <div class="legend-header">
                                <span class="legend-col-brand">Brand</span>
                                <span class="legend-col-count">Cars</span>
                                <span class="legend-col-pct">Share</span>
                            </div>
                            <div class="legend-rows">
                                <?php foreach ($brandList as $brand): 
                                    $url = $brand['brand_id'] ? "/garage?brand_id={$brand['brand_id']}" : "/garage";
                                ?>
                                    <div class="dashboard-legend-row" 
                                         data-brand-id="<?= htmlspecialchars((string)($brand['brand_id'] ?? '0')) ?>"
                                         data-brand-name="<?= htmlspecialchars($brand['brand_name']) ?>"
                                         data-count="<?= (int)$brand['model_count'] ?>"
                                         data-pct="<?= sprintf('%.1f', $brand['percentage']) ?>"
                                         data-color="<?= htmlspecialchars($brand['color']) ?>">
                                        
                                        <div class="legend-brand-info">
                                            <span class="legend-color-dot" style="background-color: <?= htmlspecialchars($brand['color']) ?>; box-shadow: 0 0 8px <?= htmlspecialchars($brand['color']) ?>66;"></span>
                                            
                                            <?php if (!empty($brand['brand_logo_path'])): ?>
                                                <img src="<?= htmlspecialchars(ImageHelper::versioned($brand['brand_logo_path'], $brand['brand_modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" 
                                                     alt="<?= htmlspecialchars($brand['brand_name']) ?>" 
                                                     class="legend-brand-logo"
                                                     loading="lazy">
                                            <?php endif; ?>

                                            <a href="<?= htmlspecialchars($url) ?>" class="legend-brand-link" title="Filter Garage by <?= htmlspecialchars($brand['brand_name']) ?>">
                                                <span class="legend-brand-name"><?= htmlspecialchars($brand['brand_name']) ?></span>
                                                <svg class="legend-arrow-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                    <polyline points="12 5 19 12 12 19"></polyline>
                                                </svg>
                                            </a>
                                        </div>

                                        <div class="legend-brand-count">
                                            <span class="count-badge"><?= number_format((int)$brand['model_count']) ?></span>
                                        </div>

                                        <div class="legend-brand-pct">
                                            <span class="pct-pill" style="border-color: <?= htmlspecialchars($brand['color']) ?>44;">
                                                <?= sprintf('%.1f', $brand['percentage']) ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CARD: Condition & Packaging Quality -->
            <section class="card dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <div class="dashboard-card-tag">Preservation Quality</div>
                        <h2 class="dashboard-card-title">Condition Breakdown</h2>
                    </div>
                    <span class="badge badge-secondary"><?= count($conditionDistribution) ?> Tiers</span>
                </div>
                <p class="dashboard-card-desc">
                    Grading, card packaging state, and loose condition ratings across your active collection.
                </p>

                <?php if (empty($conditionDistribution)): ?>
                    <p class="text-muted text-sm" style="padding: 1rem 0;">No condition metadata recorded yet.</p>
                <?php else: ?>
                    <div class="condition-meter-list">
                        <?php foreach ($conditionDistribution as $cond): 
                            $pct = (float) $cond['percentage'];
                        ?>
                            <div class="condition-meter-item">
                                <div class="condition-meta-row">
                                    <span class="condition-name">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                            <polyline points="9 12 11 14 15 10"></polyline>
                                        </svg>
                                        <?= htmlspecialchars($cond['condition_name']) ?>
                                    </span>
                                    <div class="condition-counts">
                                        <span class="condition-count-num"><?= (int)$cond['model_count'] ?> <?= (int)$cond['model_count'] === 1 ? 'car' : 'cars' ?></span>
                                        <span class="condition-pct-badge"><?= sprintf('%.1f', $pct) ?>%</span>
                                    </div>
                                </div>
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill condition-fill" style="width: <?= max(4, $pct) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>

        <!-- =================================================================== -->
        <!-- RIGHT COLUMN: Scale Proportions, Color Spectrum & Collections -->
        <!-- =================================================================== -->
        <div class="dashboard-col">

            <!-- CARD: Scale Proportions -->
            <section class="card dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <div class="dashboard-card-tag">Dimensions & Ratios</div>
                        <h2 class="dashboard-card-title">Scale Distribution</h2>
                    </div>
                    <span class="badge badge-info"><?= count($scaleDistribution) ?> Scales</span>
                </div>
                <p class="dashboard-card-desc">
                    Proportion of diecast casting sizes currently in your garage. Click any scale to inspect matching models.
                </p>

                <?php if (empty($scaleDistribution)): ?>
                    <p class="text-muted text-sm" style="padding: 1rem 0;">No scale information available.</p>
                <?php else: ?>
                    <div class="scale-bar-list">
                        <?php foreach ($scaleDistribution as $s): 
                            $pct = (float) $s['percentage'];
                            $scaleUrl = $s['scale_id'] ? "/garage?scale_id={$s['scale_id']}" : "/garage";
                        ?>
                            <a href="<?= htmlspecialchars($scaleUrl) ?>" class="scale-bar-item" title="View <?= htmlspecialchars($s['scale_name']) ?> in Garage">
                                <div class="scale-meta-row">
                                    <span class="scale-label-box">
                                        <span class="scale-tag-pill"><?= htmlspecialchars($s['scale_name']) ?></span>
                                    </span>
                                    <div class="scale-counts">
                                        <span class="scale-count-text"><?= (int)$s['model_count'] ?> <?= (int)$s['model_count'] === 1 ? 'model' : 'models' ?></span>
                                        <span class="scale-pct-text"><?= sprintf('%.1f', $pct) ?>%</span>
                                        <svg class="scale-link-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </div>
                                <div class="progress-bar-track">
                                    <div class="progress-bar-fill scale-fill" style="width: <?= max(4, $pct) ?>%;"></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CARD: Color Spectrum & Finishes -->
            <section class="card dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <div class="dashboard-card-tag">Livery & Finishes</div>
                        <h2 class="dashboard-card-title">Color Spectrum</h2>
                    </div>
                    <span class="badge badge-secondary"><?= count($colorDistribution) ?> Colors</span>
                </div>
                <p class="dashboard-card-desc">
                    Dominant paint codes and exterior finishes recorded for your collection.
                </p>

                <?php if (empty($colorDistribution)): ?>
                    <p class="text-muted text-sm" style="padding: 1rem 0;">No colors recorded yet.</p>
                <?php else: ?>
                    <div class="color-spectrum-grid">
                        <?php foreach ($colorDistribution as $clr): 
                            $hex = !empty($clr['hex_code']) ? $clr['hex_code'] : '#555555';
                        ?>
                            <div class="color-spectrum-chip" title="<?= htmlspecialchars($clr['color_name']) ?> (<?= htmlspecialchars($hex) ?>): <?= (int)$clr['model_count'] ?> models">
                                <span class="color-swatch-circle" style="background-color: <?= htmlspecialchars($hex) ?>; border-color: rgba(255,255,255,0.2);"></span>
                                <div class="color-chip-info">
                                    <span class="color-chip-name"><?= htmlspecialchars($clr['color_name']) ?></span>
                                    <span class="color-chip-count"><?= (int)$clr['model_count'] ?> <?= (int)$clr['model_count'] === 1 ? 'car' : 'cars' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CARD: Custom Themed Collections Highlights -->
            <?php if (!empty($collectionsSummary)): ?>
                <section class="card dashboard-card">
                    <div class="dashboard-card-header">
                        <div>
                            <div class="dashboard-card-tag">Curated Showcases</div>
                            <h2 class="dashboard-card-title">Custom Collections</h2>
                        </div>
                        <a href="/collections_gallery" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="dashboard-collections-list">
                        <?php foreach ($collectionsSummary as $col): 
                            $colUrl = "/garage?collection_id={$col['id']}";
                        ?>
                            <a href="<?= htmlspecialchars($colUrl) ?>" class="dashboard-col-item" title="View collection models in Garage">
                                <div class="col-item-left">
                                    <?php if (!empty($col['logo_path'])): ?>
                                        <img src="<?= htmlspecialchars(ImageHelper::versioned($col['logo_path'], $col['modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($col['name']) ?>" class="col-item-logo">
                                    <?php else: ?>
                                        <div class="col-item-icon-box">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path>
                                                <path d="M7 3h10a2 2 0 0 1 2 2v1H5V5a2 2 0 0 1 2-2Z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-item-texts">
                                        <span class="col-item-name"><?= htmlspecialchars($col['name']) ?></span>
                                        <?php if (!empty($col['description'])): ?>
                                            <span class="col-item-desc text-muted"><?= htmlspecialchars($col['description']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-item-right">
                                    <span class="col-item-count badge-primary"><?= (int)$col['model_count'] ?> Cars</span>
                                    <svg class="col-arrow-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- Bottom Full-Width Section: Recent Acquisitions / Garage Spotlight -->
    <!-- ======================================================================= -->
    <section class="card dashboard-card dashboard-spotlight-card">
        <div class="dashboard-card-header">
            <div>
                <div class="dashboard-card-tag">Garage Spotlight</div>
                <h2 class="dashboard-card-title">Recent Additions</h2>
            </div>
            <?php if (!empty($canAccessGarage)): ?>
                <a href="/garage" class="btn btn-secondary btn-sm">Full Garage &rarr;</a>
            <?php endif; ?>
        </div>
        <p class="dashboard-card-desc">
            The latest scale models cataloged into your personal showcase.
        </p>

        <?php if (empty($recentAdditions)): ?>
            <div class="dashboard-empty-spotlight">
                <p class="text-muted">No models have been added to your garage yet.</p>
                <a href="/models" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">Add Your First Model</a>
            </div>
        <?php else: ?>
            <div class="dashboard-recent-grid">
                <?php foreach ($recentAdditions as $car): 
                    $modelUrl = "/garage?model_id={$car['id']}";
                ?>
                    <article class="recent-car-card">
                        <a href="<?= htmlspecialchars($modelUrl) ?>" class="recent-car-media-wrap" title="View <?= htmlspecialchars($car['model']) ?> in Garage">
                            <?php if (!empty($car['image_path'])): ?>
                                <img src="<?= htmlspecialchars(ImageHelper::versioned($car['image_path'], $car['modified'] ?? null), ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="<?= htmlspecialchars($car['model']) ?>" 
                                     class="recent-car-img" 
                                     loading="lazy">
                            <?php else: ?>
                                <div class="recent-car-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                        <circle cx="7" cy="17" r="2"></circle>
                                        <circle cx="17" cy="17" r="2"></circle>
                                        <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                                        <path d="M5 12h14"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($car['scale_name'])): ?>
                                <span class="recent-car-scale-badge"><?= htmlspecialchars($car['scale_name']) ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="recent-car-body">
                            <div class="recent-car-badges">
                                <?php if (!empty($car['brand_name'])): ?>
                                    <span class="badge badge-primary"><?= htmlspecialchars($car['brand_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($car['maker_name'])): ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($car['maker_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="recent-car-title">
                                <a href="<?= htmlspecialchars($modelUrl) ?>"><?= htmlspecialchars($car['model']) ?></a>
                            </h3>

                            <div class="recent-car-meta">
                                <?php if (!empty($car['model_year'])): ?>
                                    <span class="meta-item" title="Model Car Year">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?= htmlspecialchars($car['model_year']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($car['condition_name'])): ?>
                                    <span class="meta-item text-success" title="Condition">
                                        <?= htmlspecialchars($car['condition_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= htmlspecialchars($modelUrl) ?>" class="recent-car-view-link">
                                <span>Inspect in Garage</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<script src="/assets/js/dashboard.js"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
