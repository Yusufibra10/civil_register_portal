<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Birth Certificates';
$canManage = hasAnyRole(['Admin', 'Officer']);

$allowedPerPage = [10, 25, 50, 100];

$filters = [
    'search'    => trim((string) ($_GET['search'] ?? '')),
    'status'    => in_array($_GET['status'] ?? '', ['active', 'revoked'], true) ? $_GET['status'] : '',
    'date_from' => (!empty($_GET['date_from']) && strtotime($_GET['date_from']) !== false) ? $_GET['date_from'] : '',
    'date_to'   => (!empty($_GET['date_to']) && strtotime($_GET['date_to']) !== false) ? $_GET['date_to'] : '',
];

$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getBirthCertificatesList($filters, $page, $perPage);
$certificates = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

$queryParams = array_filter([
    'search'    => $filters['search'],
    'status'    => $filters['status'],
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
    'per_page'  => $perPage !== 10 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'birth/index.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Birth Certificates' => null]); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0">Birth Certificates</h1>
    <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>birth/create.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-circle-plus me-1"></i>Register Certificate</a>
    <?php endif; ?>
</div>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Certificate #, name, or Citizen ID..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="revoked" <?= $filters['status'] === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Registered From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Registered To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($certificates)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-file-lines"></i>
                <p>No birth certificates match your search.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th></th>
                            <th>Citizen</th>
                            <th>Certificate #</th>
                            <th>Gender</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                        <tr>
                            <td><?php renderAvatar($cert['photo_path'], 'citizens', $cert['first_name'], $cert['last_name'], 'sm'); ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($cert['first_name'] . ' ' . $cert['last_name']) ?></div>
                                <div class="text-muted small"><?= e($cert['citizen_uid']) ?></div>
                            </td>
                            <td><?= e($cert['certificate_number']) ?></td>
                            <td><?= e($cert['gender']) ?></td>
                            <td class="small"><?= formatDate($cert['registration_date']) ?></td>
                            <td><?php renderStatusBadge(ucfirst($cert['status']), $cert['status'] === 'active' ? 'success' : 'secondary'); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>birth/view.php?id=<?= (int) $cert['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="<?= BASE_URL ?>birth/print.php?id=<?= (int) $cert['id'] ?>" class="btn btn-outline-secondary" title="Print" target="_blank"><i class="fa-solid fa-print"></i></a>
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
$pageScript = 'birth/birth.js';
require ROOT_PATH . '/layouts/app.php';
