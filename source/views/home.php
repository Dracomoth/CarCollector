<?php
$pageTitle = 'Dashboard - CarCollector';
$currentUser = $user ?? ($_SESSION['user'] ?? []);
require __DIR__ . '/layout/header.php';
?>
    <main class="container page-content">
        <div class="card">
            <h1>Protected Dashboard</h1>
            <p class="text-muted">You have successfully authenticated. This area is protected by the authentication guard.</p>

            <!-- Screens Navigation Section -->
            <?php if (!empty($canAccessUsers) || !empty($canAccessRoles) || !empty($canAccessModels) || !empty($canAccessCollections) || !empty($canAccessMakers) || !empty($canAccessBrands) || !empty($canAccessScales) || !empty($canAccessConditions) || !empty($canAccessColors)): ?>
                <div class="section">
                    <h2>Accessible Screens</h2>
                    <p class="text-muted" style="margin-bottom: 1rem;">Modules available based on your assigned role permissions:</p>
                    <div class="info-grid">
                        <?php if (!empty($canAccessModels)): ?>
                            <a href="/models" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Collection</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Models Collection &rarr;</span>
                                <span class="text-muted text-sm">Enter and catalog your personal diecasts, scales, series, and photos.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessCollections)): ?>
                            <a href="/collections" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Collection</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Custom Collections &rarr;</span>
                                <span class="text-muted text-sm">Curate custom theme showcases, sets, and model groupings.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessUsers)): ?>
                            <a href="/users" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Security</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Users Management &rarr;</span>
                                <span class="text-muted text-sm">Create, edit, deactivate, or delete system users.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessRoles)): ?>
                            <a href="/roles" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Security</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Roles & Permissions &rarr;</span>
                                <span class="text-muted text-sm">Manage roles and configure PC/other device screen permissions.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessMakers)): ?>
                            <a href="/makers" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Catalog</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Makers &rarr;</span>
                                <span class="text-muted text-sm">Manage model car manufacturers / diecast makers and logos.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessBrands)): ?>
                            <a href="/brands" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Catalog</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Brands &rarr;</span>
                                <span class="text-muted text-sm">Manage car brands, descriptions, and brand logos.</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessScales)): ?>
                            <a href="/scales" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Metadata</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Scales &rarr;</span>
                                <span class="text-muted text-sm">Manage standard model car scale ratios (1:8, 1:18, 1:64, etc.).</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessConditions)): ?>
                            <a href="/conditions" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Metadata</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Conditions &rarr;</span>
                                <span class="text-muted text-sm">Manage model car grading conditions (Mint, Scratches, Junkyard, etc.).</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($canAccessColors)): ?>
                            <a href="/colors" class="info-item" style="text-decoration: none; color: inherit; transition: border-color 0.15s ease;">
                                <span class="info-label" style="color: var(--color-primary);">Screen &bull; Metadata</span>
                                <span class="info-value" style="font-size: 1.1rem; font-weight: 600;">Colors &rarr;</span>
                                <span class="text-muted text-sm">Manage model car paint and trim colors with hex codes.</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="section">
                <h2>User Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Role</span>
                        <span class="info-value">
                            <span class="badge"><?= htmlspecialchars($user['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($user['is_super'])): ?>
                                <span class="badge badge-primary">SuperAdmin</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Session Login Time</span>
                        <span class="info-value"><?= htmlspecialchars($user['logged_in_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php
require __DIR__ . '/layout/footer.php';
?>
