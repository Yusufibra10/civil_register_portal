<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Citizens';
$canManage = hasAnyRole(['Admin', 'Officer']);

// ---------------------------------------------------------------------
// Read + whitelist filters from the query string. Nothing here is ever
// concatenated into SQL — getCitizensList() binds every value.
// ---------------------------------------------------------------------
$allowedPerPage = [10, 25, 50, 100];

$filters = [
    'search'    => trim((string) ($_GET['search'] ?? '')),
    'gender'    => in_array($_GET['gender'] ?? '', ['Male', 'Female'], true) ? $_GET['gender'] : '',
    'status'    => in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '',
    'date_from' => (!empty($_GET['date_from']) && strtotime($_GET['date_from']) !== false) ? $_GET['date_from'] : '',
    'date_to'   => (!empty($_GET['date_to']) && strtotime($_GET['date_to']) !== false) ? $_GET['date_to'] : '',
    'deleted'   => ($_GET['deleted'] ?? '') === '1',
];

$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;

$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getCitizensList($filters, $page, $perPage);
$citizens = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

// Preserve every active filter across pagination and per-page links.
$queryParams = array_filter([
    'search'    => $filters['search'],
    'gender'    => $filters['gender'],
    'status'    => $filters['status'],
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
    'deleted'   => $filters['deleted'] ? '1' : '',
    'per_page'  => $perPage !== 10 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'citizens/index.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Citizens' => null]); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0"><?= $filters['deleted'] ? 'Deleted Citizens' : 'Citizens' ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>citizens/index.php<?= $filters['deleted'] ? '' : '?deleted=1' ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-trash-can me-1"></i><?= $filters['deleted'] ? 'Back to Active List' : 'View Deleted' ?>
        </a>
        <?php if ($canManage && !$filters['deleted']): ?>
        <a href="<?= BASE_URL ?>citizens/create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus me-1"></i>Add Citizen</a>
        <?php endif; ?>
    </div>
</div>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <?php if ($filters['deleted']): ?><input type="hidden" name="deleted" value="1"><?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, National ID, phone, region..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">All</option>
                    <option value="Male" <?= $filters['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $filters['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <?php if (!$filters['deleted']): ?>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small text-muted">Registered From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Registered To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <?php if (array_filter([$filters['search'], $filters['gender'], $filters['status'], $filters['date_from'], $filters['date_to']])): ?>
            <div class="col-12">
                <a href="<?= BASE_URL ?>citizens/index.php<?= $filters['deleted'] ? '?deleted=1' : '' ?>" class="small">Clear filters</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($citizens)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-users-slash"></i>
                <p><?= $filters['deleted'] ? 'No deleted citizens found.' : 'No citizens match your search.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th></th>
                            <th>Citizen</th>
                            <th>National ID</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th><?= $filters['deleted'] ? 'Deleted' : 'Status' ?></th>
                            <th>Registered</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citizens as $citizen): ?>
                        <tr>
                            <td><?php renderAvatar($citizen['photo_path'], 'citizens', $citizen['first_name'], $citizen['last_name'], 'sm'); ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($citizen['first_name'] . ' ' . $citizen['last_name']) ?></div>
                                <div class="text-muted small"><?= e($citizen['citizen_uid']) ?></div>
                            </td>
                            <td><?= $citizen['national_id_number'] ? e($citizen['national_id_number']) : '<span class="text-muted">&mdash;</span>' ?></td>
                            <td><?= e($citizen['gender']) ?></td>
                            <td><?= $citizen['phone'] ? e($citizen['phone']) : '<span class="text-muted">&mdash;</span>' ?></td>
                            <td class="small"><?= e(trim(($citizen['district'] ?? '') . ($citizen['region'] ? ', ' . $citizen['region'] : ''), ', ')) ?: '<span class="text-muted">&mdash;</span>' ?></td>
                            <td>
                                <?php if ($filters['deleted']): ?>
                                    <span class="badge text-bg-secondary">Deleted <?= timeAgo($citizen['deleted_at']) ?></span>
                                <?php else: ?>
                                    <?php renderStatusBadge(ucfirst($citizen['status']), $citizen['status'] === 'active' ? 'success' : 'secondary'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= formatDate($citizen['registration_date']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>citizens/view.php?id=<?= (int) $citizen['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($canManage && !$filters['deleted']): ?>
                                        <a href="<?= BASE_URL ?>citizens/edit.php?id=<?= (int) $citizen['id'] ?>" class="btn btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-citizen-id="<?= (int) $citizen['id'] ?>"
                                                data-citizen-name="<?= e($citizen['first_name'] . ' ' . $citizen['last_name']) ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php elseif ($canManage && $filters['deleted']): ?>
                                        <form action="<?= BASE_URL ?>citizens/restore.php" method="post" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $citizen['id'] ?>">
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

<?php if ($canManage && !$filters['deleted']): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>citizens/delete.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="deleteCitizenId" value="">
                <div class="modal-header">
                    <h2 class="modal-title h5">Delete Citizen</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete <strong id="deleteCitizenName"></strong>? This moves the record to Deleted Citizens, where it can be restored later.</p>
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
$pageScript = 'citizens/citizens.js';
require ROOT_PATH . '/layouts/app.php';
