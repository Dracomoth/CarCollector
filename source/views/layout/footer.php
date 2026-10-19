<?php
/**
 * Global Application Layout Footer
 * 
 * Reusable footer displaying database online telemetry, system/database versions
 * with centered dot separator, current date, and interactive UI scripts.
 */

require_once dirname(__DIR__, 2) . '/services/Database.php';
require_once dirname(__DIR__, 2) . '/repositories/SystemInfoRepository.php';

$isDbOnline = Database::isOnline();
$systemVersions = SystemInfoRepository::getAllVersions();
$systemVersion = $systemVersions['system'] ?? '0.0.0';
$databaseVersion = $systemVersions['database'] ?? '0.0.0';
$currentDateFormatted = date('D, M j, Y');
?>
    <!-- ======================================================================= -->
    <!-- Application Footer (Telemetry Console Bar) -->
    <!-- ======================================================================= -->
    <footer class="app-footer">
        <div class="container footer-container">
            <!-- Left: Brand, Tagline & Copyright -->
            <div class="footer-left">
                <span class="footer-brand">CarCollector</span>
                <span class="footer-dot">&bull;</span>
                <span class="footer-tagline">Scale Car Collection</span>
                <span class="footer-copyright">&copy; <?= date('Y') ?></span>
            </div>

            <!-- Right: Database Online Indicator, Current Date, Versions with Centered Dot -->
            <div class="footer-right">
                <div class="footer-meta-top">
                    <!-- Database Online Status Indicator -->
                    <div class="db-status-pill <?= $isDbOnline ? 'is-online' : 'is-offline' ?>" 
                         title="<?= $isDbOnline ? 'Database is online and connected' : 'Database server connection offline' ?>">
                        <span class="db-status-dot"></span>
                        <span class="db-status-text">Database <?= $isDbOnline ? 'Online' : 'Offline' ?></span>
                    </div>

                    <!-- Current Date Display -->
                    <div class="footer-date" title="Current Date">
                        <svg class="footer-date-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span><?= htmlspecialchars($currentDateFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <!-- System and Database Version with Centered Dot Separator (under the status row) -->
                <div class="footer-meta-bottom">
                    <span class="version-item">System: <strong class="version-num"><?= htmlspecialchars($systemVersion, ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <span class="version-separator">&bull;</span>
                    <span class="version-item">Database: <strong class="version-num"><?= htmlspecialchars($databaseVersion, ENT_QUOTES, 'UTF-8') ?></strong></span>
                </div>
            </div>
        </div>
    </footer>

    <!-- ======================================================================= -->
    <!-- Interactive Scripts for Sidebar and Navigation -->
    <!-- ======================================================================= -->
    <script>
        (function() {
            var openBtn = document.getElementById('sidebar-open-btn');
            var closeBtn = document.getElementById('sidebar-close-btn');
            var sidebar = document.getElementById('app-sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');
            var avatarTrigger = document.getElementById('user-avatar-trigger');
            var userDropdown = document.getElementById('user-dropdown-panel');

            function openSidebar() {
                if (!sidebar) return;
                sidebar.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
                document.body.classList.add('sidebar-active');
            }

            function closeSidebar() {
                if (!sidebar) return;
                sidebar.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-open');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('sidebar-active');
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);

            // User avatar menu toggle
            if (avatarTrigger && userDropdown) {
                avatarTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var isOpen = userDropdown.classList.contains('is-open');
                    if (isOpen) {
                        userDropdown.classList.remove('is-open');
                        avatarTrigger.setAttribute('aria-expanded', 'false');
                    } else {
                        userDropdown.classList.add('is-open');
                        avatarTrigger.setAttribute('aria-expanded', 'true');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && !avatarTrigger.contains(e.target)) {
                        userDropdown.classList.remove('is-open');
                        avatarTrigger.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Collapsible Sidebar Categories (collapsed by default)
            var categoryToggles = document.querySelectorAll('.sidebar-category-toggle');
            categoryToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    var block = toggle.closest('.sidebar-category-block');
                    if (!block) return;
                    var isCollapsed = block.classList.contains('is-collapsed');
                    if (isCollapsed) {
                        block.classList.remove('is-collapsed');
                        toggle.setAttribute('aria-expanded', 'true');
                    } else {
                        block.classList.add('is-collapsed');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            // Keyboard navigation (Escape key to close drawer/dropdown)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                    if (userDropdown) {
                        userDropdown.classList.remove('is-open');
                        if (avatarTrigger) avatarTrigger.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        })();
    </script>
</body>
</html>
