<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/national_ids_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'National IDs';

$allowedPerPage = [10, 25, 50, 100];

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => in_array($_GET['status'] ?? '', ['active', 'expired', 'revoked'], true) ? $_GET['status'] : '',
];

$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getNationalIdsList($filters, $page, $perPage);
$nationalIds = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

$queryParams = array_filter([
    'search'   => $filters['search'],
    'status'   => $filters['status'],
    'per_page' => $perPage !== 10 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'national_id/index.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'National IDs' => null]); ?>

<h1 class="h4 mb-3">National IDs</h1>
<p class="text-muted small mb-3">National ID cards are issued automatically when an application is approved — there is no separate "add" action here.</p>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="ID number, name, or Citizen ID..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= $filters['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="revoked" <?= $filters['status'] === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($nationalIds)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-id-card"></i>
                <p>No National IDs match your search.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th></th>
                            <th>Citizen</th>
                            <th>ID Number</th>
                            <th>Issued</th>
                            <th>Expires</th>
                            <th>Printed</th>
                            <th>Collected</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nationalIds as $nid): ?>
                        <tr>
                            <td><?php renderAvatar($nid['photo_path'], 'citizens', $nid['first_name'], $nid['last_name'], 'sm'); ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($nid['first_name'] . ' ' . $nid['last_name']) ?></div>
                                <div class="text-muted small"><?= e($nid['citizen_uid']) ?></div>
                            </td>
                            <td><?= e($nid['id_number']) ?></td>
                            <td class="small"><?= formatDate($nid['issue_date']) ?></td>
                            <td class="small"><?= formatDate($nid['expiry_date']) ?></td>
                            <td class="small"><?= $nid['card_printed_at'] ? '<i class="fa-solid fa-check text-success"></i>' : '<span class="text-muted">—</span>' ?></td>
                            <td class="small"><?= $nid['collected_at'] ? '<i class="fa-solid fa-check text-success"></i>' : '<span class="text-muted">—</span>' ?></td>
                            <td><?php renderStatusBadge(ucfirst($nid['status']), nationalIdStatusVariant($nid['status'])); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>national_id/view.php?id=<?= (int) $nid['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="<?= BASE_URL ?>national_id/print.php?id=<?= (int) $nid['id'] ?>" class="btn btn-outline-secondary" title="Print" target="_blank"><i class="fa-solid fa-print"></i></a>
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
$pageScript = 'national_id/national_id.js';
require ROOT_PATH . '/layouts/app.php';
