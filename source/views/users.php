<?php
$pageTitle = 'Users Management - CarCollector';
require __DIR__ . '/layout/header.php';
?>

    <main class="container page-content">
        <div class="page-header">
            <div>
                <h1>Users Management</h1>
                <p class="text-muted">Create, edit, activate/deactivate, and manage system users and roles.</p>
            </div>
            <div>
                <?php if (!$isFormVisible): ?>
                    <a href="/users?action=create" class="btn btn-primary" id="btn-new-user">+ New User</a>
                <?php endif; ?>
            </div>
        </div>

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

        <!-- =============================================================== -->
        <!-- User Form (Shown on Create or Edit) -->
        <!-- =============================================================== -->
        <?php if ($isFormVisible): ?>
            <div class="card form-card" id="user-form-card">
                <div class="card-header-row">
                    <h2><?= $editUser ? 'Edit User: ' . htmlspecialchars($editUser['username'], ENT_QUOTES, 'UTF-8') : 'Create New User' ?></h2>
                    <a href="/users" class="btn btn-outline btn-sm">Close Form</a>
                </div>

                <form method="POST" action="/users" class="form">
                    <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?= htmlspecialchars($editUser['username'] ?? $_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="e.g. jdoe">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= htmlspecialchars($editUser['email'] ?? $_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="e.g. jdoe@example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required 
                                   value="<?= htmlspecialchars($editUser['first_name'] ?? $_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="John">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required 
                                   value="<?= htmlspecialchars($editUser['last_name'] ?? $_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Doe">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role_id">Role *</label>
                            <select id="role_id" name="role_id" required class="form-control">
                                <option value="">-- Select Role --</option>
                                <?php 
                                $selectedRoleId = $editUser['role_id'] ?? $_POST['role_id'] ?? null;
                                foreach ($roles as $role): 
                                ?>
                                    <option value="<?= (int) $role['id'] ?>" <?= ((int)$selectedRoleId === (int)$role['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?= !empty($role['is_super']) ? ' (SuperAdmin)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <?= $editUser ? '<span class="text-muted">(leave blank to keep current)</span>' : '*' ?></label>
                            <input type="password" id="password" name="password" <?= $editUser ? '' : 'required' ?> 
                                   autocomplete="new-password" placeholder="<?= $editUser ? '••••••••' : 'Enter secure password' ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="avatar_path">Avatar Path / URL</label>
                            <input type="text" id="avatar_path" name="avatar_path" 
                                   value="<?= htmlspecialchars($editUser['avatar_path'] ?? $_POST['avatar_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="e.g. /images/avatars/user.png">
                        </div>

                        <div class="form-group form-group-checkbox">
                            <?php 
                            $isActiveChecked = true;
                            if ($editUser) {
                                $isActiveChecked = !empty($editUser['is_active']);
                            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                $isActiveChecked = isset($_POST['is_active']);
                            }
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?= $isActiveChecked ? 'checked' : '' ?>>
                                <span>Active Account (user can log in)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= $editUser ? 'Update User' : 'Create User' ?></button>
                        <a href="/users" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- =============================================================== -->
        <!-- Users Data Grid (Filtering, Sorting, Pagination, Per-Page) -->
        <!-- =============================================================== -->
        <?php if (!empty($grid)): ?>
            <?= $grid->render() ?>
        <?php endif; ?>
    </main>
<?php
require __DIR__ . '/layout/footer.php';
?>
