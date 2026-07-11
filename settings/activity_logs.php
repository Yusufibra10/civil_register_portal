<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'activity_logs.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/activity_logs_repository.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/pagination.php';

$pageTitle = 'Activity Log';
$allowedPerPage = [25, 50, 100];
$modules = getActivityLogModules();

$filters = [
    'search'    => trim((string) ($_GET['search'] ?? '')),
    'module'    => in_array($_GET['module'] ?? '', $modules, true) ? $_GET['module'] : '',
    'date_from' => (!empty($_GET['date_from']) && strtotime($_GET['date_from']) !== false) ? $_GET['date_from'] : '',
    'date_to'   => (!empty($_GET['date_to']) && strtotime($_GET['date_to']) !== false) ? $_GET['date_to'] : '',
];

$perPage = (int) ($_GET['per_page'] ?? 25);
$perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 25;
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = getActivityLogsList($filters, $page, $perPage);
$logs = $result['rows'];
$totalPages = max(1, (int) ceil($result['total'] / $perPage));

$queryParams = array_filter([
    'search'    => $filters['search'],
    'module'    => $filters['module'],
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
    'per_page'  => $perPage !== 25 ? $perPage : '',
], fn ($v) => $v !== '');

$paginationBaseUrl = BASE_URL . 'settings/activity_logs.php?' . http_build_query($queryParams)
    . (empty($queryParams) ? '' : '&') . 'page=';

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Settings' => BASE_URL . 'settings/index.php', 'Activity Log' => null]); ?>

<h1 class="h4 mb-3">Activity Log</h1>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Action, description, or user..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Module</label>
                <select name="module" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= e($module) ?>" <?= $filters['module'] === $module ? 'selected' : '' ?>><?= e($module) ?></option>
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
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-clock"></i>
                <p>No activity matches your search.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-nowrap"><?= formatDate($log['created_at'], 'd M Y, g:i A') ?></td>
                            <td class="small"><?= $log['user_name'] ? e($log['user_name']) : '<span class="text-muted">System</span>' ?></td>
                            <td class="small fw-semibold"><?= e($log['action']) ?></td>
                            <td class="small text-muted"><?= $log['module'] ? e($log['module']) : '—' ?></td>
                            <td class="small"><?= $log['description'] ? e($log['description']) : '—' ?></td>
                            <td class="small text-muted"><?= $log['ip_address'] ? e($log['ip_address']) : '—' ?></td>
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
$pageScript = 'settings/settings.js';
require ROOT_PATH . '/layouts/app.php';
