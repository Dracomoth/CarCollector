<?php
/**
 * Global Application Layout Header & Sidebar
 * 
 * Reusable header, brand bar, collection telemetry badges,
 * user avatar/initials, and dynamic sliding sidebar.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/services/Database.php';
require_once dirname(__DIR__, 2) . '/services/CollectionService.php';
require_once dirname(__DIR__, 2) . '/repositories/ScreenRepository.php';
require_once dirname(__DIR__, 2) . '/repositories/SystemInfoRepository.php';

$currentUser = $currentUser ?? $user ?? ($_SESSION['user'] ?? []);
$pageTitle = $pageTitle ?? 'CarCollector - Scale Car Collection';

// Total cars in collection for current user
$totalCars = CollectionService::getTotalCars(!empty($currentUser['id']) ? (int)$currentUser['id'] : null);

// Current path for active link state
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentPath = rtrim($currentPath, '/');
if ($currentPath === '') {
    $currentPath = '/';
}

// User avatar & initials calculation
$userAvatar = !empty($currentUser['avatar_path']) ? $currentUser['avatar_path'] : null;
$firstName = trim($currentUser['first_name'] ?? '');
$lastName = trim($currentUser['last_name'] ?? '');
if ($firstName !== '' || $lastName !== '') {
    $firstChar = function_exists('mb_substr') ? mb_substr($firstName, 0, 1) : substr($firstName, 0, 1);
    $lastChar = function_exists('mb_substr') ? mb_substr($lastName, 0, 1) : substr($lastName, 0, 1);
    $userInitials = strtoupper($firstChar . $lastChar);
    $userDisplayName = trim($firstName . ' ' . $lastName);
} else {
    $u = $currentUser['username'] ?? 'CC';
    $chars = function_exists('mb_substr') ? mb_substr($u, 0, 2) : substr($u, 0, 2);
    $userInitials = strtoupper($chars);
    $userDisplayName = $currentUser['username'] ?? 'User';
}

// Fetch accessible screens for current user and device context
$accessibleScreens = ScreenRepository::getAccessibleScreens($currentUser);

// Group screens by category, respecting sort_order and grouped flag
$categories = [];
foreach ($accessibleScreens as $screen) {
    $catName = !empty($screen['category']) ? trim($screen['category']) : 'General';
    if (!isset($categories[$catName])) {
        $categories[$catName] = [
            'name'            => $catName,
            'grouped_items'   => [],
            'ungrouped_items' => [],
        ];
    }
    if (!empty($screen['grouped'])) {
        $categories[$catName]['grouped_items'][] = $screen;
    } else {
        $categories[$catName]['ungrouped_items'][] = $screen;
    }
}

/**
 * Helper to render contextual SVG icon for a screen key
 */
if (!function_exists('renderScreenIcon')) {
    function renderScreenIcon(string $screenKey): string {
        switch ($screenKey) {
            case 'users':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
            case 'roles':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
            case 'makers':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path><path d="M17 18h1"></path><path d="M12 18h1"></path><path d="M7 18h1"></path></svg>';
            case 'brands':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>';
            case 'scales':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-6-6a1 1 0 0 1 0-1.4L13.9 1.3a1 1 0 0 1 1.4 0l6 6a1 1 0 0 1 0 1.4Z"></path><path d="m14.5 4.5 2 2"></path><path d="m11.5 7.5 2 2"></path><path d="m8.5 10.5 2 2"></path><path d="m5.5 13.5 2 2"></path></svg>';
            case 'conditions':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>';
            case 'colors':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.12a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.56-2.5 5.56-5.55C22 6.01 17.5 2 12 2z"></path></svg>';
            case 'models':
            case 'cars':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path><path d="M5 12h14"></path></svg>';
            case 'garage':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.1 10.9 2 11.2 2 11.5V16c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle><path d="M9 17h6"></path><path d="M2 7l10-4 10 4"></path></svg>';
            case 'collections':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"></path><path d="M7 3h10a2 2 0 0 1 2 2v1H5V5a2 2 0 0 1 2-2Z"></path><circle cx="8" cy="11" r="1.5"></circle><path d="m21 16-5-5-5 5"></path></svg>';
            case 'dashboard':
            case 'home':
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
            default:
                return '<svg class="sidebar-link-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"></circle><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"></path></svg>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="alternate icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="has-cockpit-layout">

    <!-- ======================================================================= -->
    <!-- Application Header (Telemetry & Navigation Bar) -->
    <!-- ======================================================================= -->
    <header class="app-header">
        <div class="container header-container">
            <!-- Left: Sandwich Toggle, Brand Title, Description, Collection Badges -->
            <div class="header-left">
                <!-- Sandwich Hamburger Button to open sidebar -->
                <button type="button" 
                        class="sidebar-toggle-btn" 
                        id="sidebar-open-btn" 
                        aria-label="Open sidebar navigation" 
                        aria-controls="app-sidebar"
                        aria-expanded="false"
                        title="Open navigation menu">
                    <svg class="icon-hamburger" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <!-- Brand Title and Tagline Description -->
                <div class="header-brand-wrap">
                    <a href="/" class="brand" title="CarCollector Dashboard">
                        <span class="brand-text">CarCollector</span>
                    </a>
                    <span class="brand-divider">/</span>
                    <span class="brand-tagline">scale car collection</span>
                </div>

                <!-- Collection Overall Count Info Badges -->
                <div class="header-badges">
                    <div class="collection-badge badge-primary" title="Total scale cars in collection overall">
                        <span class="badge-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="7" cy="17" r="2"></circle>
                                <circle cx="17" cy="17" r="2"></circle>
                                <path d="M5 17H3v-4l2-5h12l3 5v4h-2"></path>
                                <path d="M5 12h14"></path>
                            </svg>
                        </span>
                        <span class="badge-label">COLLECTION</span>
                        <span class="badge-count"><?= number_format($totalCars) ?></span>
                        <span class="badge-unit"><?= $totalCars === 1 ? 'CAR' : 'CARS' ?></span>
                    </div>

                    <div class="collection-badge badge-secondary" title="Overall inventory telemetry">
                        <span class="badge-status-dot"></span>
                        <span class="badge-label">TOTAL OVERALL:</span>
                        <span class="badge-value"><?= number_format($totalCars) ?></span>
                    </div>
                </div>
            </div>

            <!-- Right: Logged User Avatar (or initials) -->
            <div class="header-right">
                <div class="user-avatar-container" id="user-avatar-container">
                    <button type="button" 
                            class="user-avatar-trigger" 
                            id="user-avatar-trigger" 
                            aria-haspopup="true" 
                            aria-expanded="false" 
                            title="<?= htmlspecialchars($userDisplayName) ?> (<?= htmlspecialchars($currentUser['username'] ?? 'User') ?>)">
                        <?php if (!empty($userAvatar)): ?>
                            <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8') ?>" 
                                 class="user-avatar-img">
                        <?php else: ?>
                            <div class="user-avatar-initials"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </button>

                    <!-- User Quick Dropdown -->
                    <div class="user-dropdown-panel" id="user-dropdown-panel" aria-hidden="true">
                        <div class="user-dropdown-profile">
                            <div class="user-dropdown-name"><?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="user-dropdown-username">@<?= htmlspecialchars($currentUser['username'] ?? 'user', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="user-dropdown-role">
                                <span class="badge"><?= htmlspecialchars($currentUser['role_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($currentUser['is_super'])): ?>
                                    <span class="badge badge-primary">SuperAdmin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <nav class="user-dropdown-links">
                            <a href="/" class="user-dropdown-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                <span>Dashboard</span>
                            </a>
                            <a href="/logout" class="user-dropdown-link text-danger">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                <span>Log Out</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ======================================================================= -->
    <!-- Sliding Sidebar Drawer & Backdrop Overlay -->
    <!-- ======================================================================= -->
    <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <aside class="app-sidebar" id="app-sidebar" aria-label="Main Navigation">
        <!-- Sidebar Top: Brand Title & 'X' Close Button -->
        <div class="sidebar-header">
            <div class="sidebar-brand-group">
                <a href="/" class="sidebar-brand-title">CarCollector</a>
                <span class="sidebar-brand-tagline">scale car collection</span>
            </div>
            <!-- X Close Button replacing sandwich icon -->
            <button type="button" 
                    class="sidebar-close-btn" 
                    id="sidebar-close-btn" 
                    aria-label="Close sidebar" 
                    title="Close navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Sidebar Navigation Content -->
        <div class="sidebar-content">
            <!-- Dashboard Primary Link -->
            <div class="sidebar-section">
                <a href="/" class="sidebar-nav-link <?= $currentPath === '/' ? 'active' : '' ?>">
                    <span class="sidebar-icon-box">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </span>
                    <span class="sidebar-nav-text">Dashboard</span>
                </a>
            </div>

            <!-- Dynamic Screens Navigation with category separators and grouped logic -->
            <?php foreach ($categories as $catName => $catData): ?>
                <div class="sidebar-separator" role="separator"></div>
                <div class="sidebar-section">
                    <!-- Ungrouped Screens (displayed at section level without category heading) -->
                    <?php if (!empty($catData['ungrouped_items'])): ?>
                        <div class="sidebar-ungrouped-items">
                            <?php foreach ($catData['ungrouped_items'] as $item): ?>
                                <?php 
                                $href = ($item['screen_key'] === 'home' || $item['screen_key'] === 'dashboard') ? '/' : '/' . ltrim($item['screen_key'], '/');
                                $isActive = ($currentPath === $href);
                                ?>
                                <a href="<?= htmlspecialchars($href) ?>" class="sidebar-nav-link <?= $isActive ? 'active' : '' ?>">
                                    <span class="sidebar-icon-box"><?= renderScreenIcon($item['screen_key']) ?></span>
                                    <span class="sidebar-nav-text"><?= htmlspecialchars($item['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Grouped Screens (displayed grouped under collapsible category heading) -->
                    <?php if (!empty($catData['grouped_items'])): ?>
                        <?php 
                        $catSlug = 'cat-' . preg_replace('/[^a-z0-9_-]/i', '', strtolower($catName));
                        $hasActiveChild = false;
                        foreach ($catData['grouped_items'] as $item) {
                            $href = ($item['screen_key'] === 'home' || $item['screen_key'] === 'dashboard') ? '/' : '/' . ltrim($item['screen_key'], '/');
                            if ($currentPath === $href) {
                                $hasActiveChild = true;
                                break;
                            }
                        }
                        ?>
                        <div class="sidebar-category-block is-collapsed <?= $hasActiveChild ? 'has-active-child' : '' ?>" id="<?= $catSlug ?>-block">
                            <button type="button" 
                                    class="sidebar-category-header sidebar-category-toggle" 
                                    aria-expanded="false" 
                                    aria-controls="<?= $catSlug ?>-items"
                                    id="<?= $catSlug ?>-toggle"
                                    title="Toggle <?= htmlspecialchars($catName) ?> category">
                                <span class="sidebar-category-title"><?= htmlspecialchars($catName) ?></span>
                                <span class="sidebar-category-meta">
                                    <span class="sidebar-category-count"><?= count($catData['grouped_items']) ?></span>
                                    <svg class="sidebar-category-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </span>
                            </button>
                            <div class="sidebar-category-items" id="<?= $catSlug ?>-items" role="region" aria-labelledby="<?= $catSlug ?>-toggle">
                                <?php foreach ($catData['grouped_items'] as $item): ?>
                                    <?php 
                                    $href = ($item['screen_key'] === 'home' || $item['screen_key'] === 'dashboard') ? '/' : '/' . ltrim($item['screen_key'], '/');
                                    $isActive = ($currentPath === $href);
                                    ?>
                                    <a href="<?= htmlspecialchars($href) ?>" class="sidebar-nav-link sidebar-grouped-link <?= $isActive ? 'active' : '' ?>">
                                        <span class="sidebar-icon-box"><?= renderScreenIcon($item['screen_key']) ?></span>
                                        <span class="sidebar-nav-text"><?= htmlspecialchars($item['name']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sidebar Footer (Logged User summary & Quick Logout) -->
        <div class="sidebar-footer">
            <div class="sidebar-user-summary">
                <?php if (!empty($userAvatar)): ?>
                    <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" 
                         alt="Avatar" 
                         class="sidebar-avatar-img">
                <?php else: ?>
                    <div class="sidebar-avatar-initials"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="sidebar-user-text">
                    <span class="sidebar-user-name"><?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="sidebar-user-role"><?= htmlspecialchars($currentUser['role_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <a href="/logout" class="sidebar-logout-btn" title="Log Out" aria-label="Log Out">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>
