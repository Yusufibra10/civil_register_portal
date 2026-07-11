<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'users.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/users_repository.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Users';
$canManage = hasPermission('users.manage');
$roles = getRolesList();

$allowedPerPage = [10, 25, 50, 100];

$filters = [
    'search'  => trim((string) ($_GET['search'] ?? '')),
    'role_id' => (int) ($_GET['role_id'] ?? 0),
    'status'  => in_array($_GET['status'] ?? '', ['active', 'inactive', 'suspended'], true) ? $_GET['status'] : '',
    'deleted' => ($_GET['deleted'] ?? '') === '1',
];

$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getUsersList($filters, $page, $perPage);
$users = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

$queryParams = array_filter([
    'search'   => $filters['search'],
    'role_id'  => $filters['role_id'] ?: '',
    'status'   => $filters['status'],
    'deleted'  => $filters['deleted'] ? '1' : '',
    'per_page' => $perPage !== 10 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'users/index.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Users' => null]); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0"><?= $filters['deleted'] ? 'Deleted Users' : 'Users' ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>users/index.php<?= $filters['deleted'] ? '' : '?deleted=1' ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-trash-can me-1"></i><?= $filters['deleted'] ? 'Back to Active List' : 'View Deleted' ?>
        </a>
        <?php if ($canManage && !$filters['deleted']): ?>
        <a href="<?= BASE_URL ?>users/create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus me-1"></i>Add User</a>
        <?php endif; ?>
    </div>
</div>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <?php if ($filters['deleted']): ?><input type="hidden" name="deleted" value="1"><?php endif; ?>
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, email, or username..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Role</label>
                <select name="role_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= $filters['role_id'] === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!$filters['deleted']): ?>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-users-slash"></i>
                <p><?= $filters['deleted'] ? 'No deleted users found.' : 'No users match your search.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th></th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th><?= $filters['deleted'] ? 'Deleted' : 'Status' ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <?php
                            [$uFirst, $uLast] = array_pad(explode(' ', $u['full_name'], 2), 2, '');
                            $uStatusVariant = match ($u['status']) {
                                'active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', default => 'secondary',
                            };
                        ?>
                        <tr>
                            <td><?php renderAvatar($u['profile_photo'], 'users', $uFirst, $uLast, 'sm'); ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($u['full_name']) ?></div>
                                <div class="text-muted small"><?= e($u['email']) ?></div>
                            </td>
                            <td><?= e($u['role_name']) ?></td>
                            <td class="small"><?= $u['last_login_at'] ? timeAgo($u['last_login_at']) : '<span class="text-muted">Never</span>' ?></td>
                            <td>
                                <?php if ($filters['deleted']): ?>
                                    <span class="badge text-bg-secondary">Deleted <?= timeAgo($u['deleted_at']) ?></span>
                                <?php else: ?>
                                    <?php renderStatusBadge(ucfirst($u['status']), $uStatusVariant); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>users/view.php?id=<?= (int) $u['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($canManage && !$filters['deleted']): ?>
                                        <a href="<?= BASE_URL ?>users/edit.php?id=<?= (int) $u['id'] ?>" class="btn btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <?php elseif ($canManage && $filters['deleted']): ?>
                                        <form action="<?= BASE_URL ?>users/restore.php" method="post" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <div class="d-flex align-items-center gap-2 small text-muted">
                    <span>Rows per page</span>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width:auto;">
                        <?php foreach ($allowedPerPage as $option): ?>
                            <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span><?= number_format($result['total']) ?> total</span>
                </div>
                <?php renderPagination($page, $totalPages, $paginationBaseUrl); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageScript = 'users/users.js';
require ROOT_PATH . '/layouts/app.php';
