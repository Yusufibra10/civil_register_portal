<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'users.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/users_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';

$id = (int) ($_GET['id'] ?? 0);
$user = $id > 0 ? findUserById($id, true) : null;

if ($user === null) {
    setFlash('danger', 'That user was not found.');
    redirect('users/index.php');
}

$pageTitle = $user['full_name'];
$canManage = hasPermission('users.manage');
$isSelf = $id === (int) $_SESSION['user_id'];
$isDeleted = $user['deleted_at'] !== null;
[$firstName, $lastName] = array_pad(explode(' ', $user['full_name'], 2), 2, '');
$recentLogs = getUserActivityLogs($id, 10);

$statusVariant = match ($user['status']) {
    'active'    => 'success',
    'inactive'  => 'secondary',
    'suspended' => 'danger',
    default     => 'secondary',
};

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Users' => BASE_URL . 'users/index.php', $user['full_name'] => null]); ?>

<?php if ($isDeleted): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-trash-can me-1"></i>This account was deleted <?= timeAgo($user['deleted_at']) ?> and can no longer log in.</span>
    <?php if ($canManage): ?>
        <form action="<?= BASE_URL ?>users/restore.php" method="post" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fa-solid fa-rotate-left me-1"></i>Restore</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?php renderAvatar($user['profile_photo'], 'users', $firstName, $lastName, 'lg'); ?>
        <div class="flex-grow-1">
            <h1 class="h4 mb-1"><?= e($user['full_name']) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border"><?= e($user['username']) ?></span>
                <span class="badge text-bg-light border"><?= e($user['role_name']) ?></span>
                <?php renderStatusBadge(ucfirst($user['status']), $statusVariant); ?>
            </div>
        </div>
        <?php if ($canManage && !$isDeleted): ?>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>users/edit.php?id=<?= (int) $user['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
            <?php if (!$isSelf && in_array($user['status'], ['active', 'inactive'], true)): ?>
                <form action="<?= BASE_URL ?>users/toggle_status.php" method="post" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary">
                        <?php if ($user['status'] === 'active'): ?>
                            <i class="fa-solid fa-user-slash me-1"></i>Deactivate
                        <?php else: ?>
                            <i class="fa-solid fa-user-check me-1"></i>Activate
                        <?php endif; ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!$isSelf): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fa-solid fa-trash me-1"></i>Delete</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Account</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7"><?= e($user['email']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7"><?= $user['phone'] ? e($user['phone']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Role</dt>
                    <dd class="col-7"><?= e($user['role_name']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Last Login</dt>
                    <dd class="col-7"><?= $user['last_login_at'] ? formatDate($user['last_login_at'], 'd M Y, g:i A') : 'Never' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Created</dt>
                    <dd class="col-7"><?= formatDate($user['created_at'], 'd M Y') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Recent Activity</h2>
                <?php if (empty($recentLogs)): ?>
                    <div class="empty-state py-3"><i class="fa-regular fa-clock"></i><p>No activity recorded yet.</p></div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= e($log['action']) ?></span>
                                <span class="text-muted small"><?= timeAgo($log['created_at']) ?></span>
                            </div>
                            <?php if ($log['description']): ?><div class="text-muted small"><?= e($log['description']) ?></div><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage && !$isDeleted && !$isSelf): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>users/delete.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5">Delete User</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete <strong><?= e($user['full_name']) ?></strong>? Their account will be deactivated and can be restored later, but they will not be able to log in until then.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/app.php';
