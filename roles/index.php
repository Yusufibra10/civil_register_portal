<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'roles.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'Roles & Permissions';
$roles = getRolesList();

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Roles & Permissions' => null]); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0">Roles &amp; Permissions</h1>
    <a href="<?= BASE_URL ?>roles/create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Role</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>Role</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <?php $core = isCoreRole($role['name']); ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= e($role['name']) ?></span>
                            <?php if ($core): ?><span class="badge text-bg-light border ms-1">Built-in</span><?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= $role['description'] ? e($role['description']) : '—' ?></td>
                        <td><?= (int) $role['user_count'] ?></td>
                        <td><?= (int) $role['permission_count'] ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>roles/edit.php?id=<?= (int) $role['id'] ?>" class="btn btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <?php if (!$core && (int) $role['user_count'] === 0): ?>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-role-id="<?= (int) $role['id'] ?>" data-role-name="<?= e($role['name']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0 mt-2">Built-in roles (Admin, Officer, Viewer) cannot be renamed or deleted — the application relies on their exact names. A custom role can only be deleted once no users are assigned to it.</p>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>roles/delete.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="deleteRoleId" value="">
                <div class="modal-header">
                    <h2 class="modal-title h5">Delete Role</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete <strong id="deleteRoleName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageScript = 'roles/roles.js';
require ROOT_PATH . '/layouts/app.php';
