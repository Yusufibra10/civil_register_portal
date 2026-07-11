<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'ID Applications';
$canManage = hasAnyRole(['Admin', 'Officer']);

$allowedPerPage = [10, 25, 50, 100];
$allowedStatusCodes = array_keys(APPLICATION_STATUS_TRANSITIONS);

$filters = [
    'search'    => trim((string) ($_GET['search'] ?? '')),
    'status'    => in_array($_GET['status'] ?? '', $allowedStatusCodes, true) ? $_GET['status'] : '',
    'date_from' => (!empty($_GET['date_from']) && strtotime($_GET['date_from']) !== false) ? $_GET['date_from'] : '',
    'date_to'   => (!empty($_GET['date_to']) && strtotime($_GET['date_to']) !== false) ? $_GET['date_to'] : '',
];

$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getApplicationsList($filters, $page, $perPage);
$applications = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

$queryParams = array_filter([
    'search'    => $filters['search'],
    'status'    => $filters['status'],
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
    'per_page'  => $perPage !== 10 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'applications/index.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

// Status dropdown options in workflow order, not alphabetical.
$statusStmt = getDB()->query('SELECT code, label FROM application_status ORDER BY sort_order');
$statusOptions = $statusStmt->fetchAll();
$statusLabels = array_column($statusOptions, 'label', 'code');

// Short verb for each transition button: Approve/Reject read better inline
// than the generic "Move to X" wording view.php uses in its own full-size
// button, but the choice of *which* transitions are even offered still
// comes entirely from APPLICATION_STATUS_TRANSITIONS — the same graph
// applications/update_status.php enforces server-side.
$actionLabels = [
    'approved' => 'Approve',
    'rejected' => 'Reject',
];

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'ID Applications' => null]); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0">ID Applications</h1>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>applications/track.php" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="fa-solid fa-magnifying-glass-location me-1"></i>Public Tracking</a>
        <?php if ($canManage): ?>
            <a href="<?= BASE_URL ?>applications/create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-circle-plus me-1"></i>New Application</a>
        <?php endif; ?>
    </div>
</div>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Application #, name, or Citizen ID..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($statusOptions as $option): ?>
                        <option value="<?= e($option['code']) ?>" <?= $filters['status'] === $option['code'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                <p>No applications match your search.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th>Application No</th>
                            <th>Citizen</th>
                            <th class="small">Applied</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <?php $rowAllowedNext = $canManage ? (APPLICATION_STATUS_TRANSITIONS[$app['status_code']] ?? []) : []; ?>
                        <tr>
                            <td class="fw-semibold"><?= e($app['application_number']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php renderAvatar($app['photo_path'], 'citizens', $app['first_name'], $app['last_name'], 'sm'); ?>
                                    <div>
                                        <div class="fw-semibold"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></div>
                                        <div class="text-muted small"><?= e($app['citizen_uid']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="small"><?= formatDate($app['applied_date']) ?></td>
                            <td><?php renderStatusBadge($app['status_label'], applicationStatusVariant($app['status_code'])); ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a href="<?= BASE_URL ?>applications/view.php?id=<?= (int) $app['id'] ?>" class="text-decoration-none">View</a>
                                    <?php foreach ($rowAllowedNext as $i => $nextCode): ?>
                                        <span class="text-muted">|</span>
                                        <button type="button"
                                                class="btn btn-link btn-sm p-0 text-decoration-none <?= $nextCode === 'rejected' ? 'text-danger' : 'text-success' ?>"
                                                data-bs-toggle="modal" data-bs-target="#statusModal"
                                                data-application-id="<?= (int) $app['id'] ?>"
                                                data-status-code="<?= e($nextCode) ?>"
                                                data-status-label="<?= e($statusLabels[$nextCode] ?? $nextCode) ?>">
                                            <?= e($actionLabels[$nextCode] ?? ('Mark ' . ($statusLabels[$nextCode] ?? $nextCode))) ?>
                                        </button>
                                    <?php endforeach; ?>
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

<?php if ($canManage): ?>
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>applications/update_status.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="">
                <input type="hidden" name="target_status_code" id="statusModalCode" value="">
                <div class="modal-header">
                    <h2 class="modal-title h5">Move too <span id="statusModalLabel"></span></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="remarks" class="form-control" rows="3" maxlength="255" placeholder="Reason, condition, or comments for this update..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageScript = 'applications/applications.js';
require ROOT_PATH . '/layouts/app.php';
