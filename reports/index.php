<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'reports.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/reports_repository.php';
require_once ROOT_PATH . '/helpers/applications_repository.php'; // applicationStatusVariant() for the Applications report table
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Reports';

$type = in_array($_GET['type'] ?? '', REPORT_TYPES, true) ? $_GET['type'] : 'citizens';
$period = in_array($_GET['period'] ?? '', ['this_month', 'last_month', 'this_year', 'last_year', 'custom'], true)
    ? $_GET['period'] : 'this_month';

$range = resolveReportPeriod($period, (string) ($_GET['date_from'] ?? ''), (string) ($_GET['date_to'] ?? ''));

$extraFilters = [
    'gender' => in_array($_GET['gender'] ?? '', ['Male', 'Female'], true) ? $_GET['gender'] : '',
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$queryFilters = array_merge($range, $extraFilters);

$report = match ($type) {
    'citizens'           => getCitizensReport($queryFilters),
    'birth_certificates' => getBirthCertificatesReport($queryFilters),
    'national_ids'       => getNationalIdsReport($queryFilters),
    'applications'       => getApplicationsReport($queryFilters),
};

const PREVIEW_LIMIT = 200;
$totalRows = count($report['rows']);
$previewRows = array_slice($report['rows'], 0, PREVIEW_LIMIT);

// Every filter, preserved across period changes and the export buttons.
$sharedParams = array_filter([
    'period'    => $period,
    'date_from' => $period === 'custom' ? $range['date_from'] : '',
    'date_to'   => $period === 'custom' ? $range['date_to'] : '',
    'gender'    => $extraFilters['gender'],
    'status'    => $extraFilters['status'],
], fn ($v) => $v !== '');

// Switching report type resets gender/status — those filters (and their
// valid values) are specific to whichever type set them, and carrying
// e.g. status=in_review over to the Citizens report would just silently
// match nothing rather than mean anything there.
$typeSwitchParams = array_filter([
    'period'    => $period,
    'date_from' => $period === 'custom' ? $range['date_from'] : '',
    'date_to'   => $period === 'custom' ? $range['date_to'] : '',
], fn ($v) => $v !== '');

$recentReports = getRecentReports(8);

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Reports' => null]); ?>

<h1 class="h4 mb-3">Reports</h1>

<ul class="nav nav-pills mb-3">
    <?php foreach (REPORT_TYPES as $t): ?>
        <li class="nav-item">
            <a class="nav-link <?= $t === $type ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($typeSwitchParams, ['type' => $t])) ?>">
                <?= e(reportTypeLabel($t)) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card filter-card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end" id="reportFilterForm">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <div class="col-md-3">
                <label class="form-label small text-muted">Period</label>
                <select name="period" class="form-select" id="periodSelect">
                    <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Last Month</option>
                    <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
                    <option value="last_year" <?= $period === 'last_year' ? 'selected' : '' ?>>Last Year</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-2 custom-range-field <?= $period === 'custom' ? '' : 'd-none' ?>">
                <label class="form-label small text-muted">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($range['date_from']) ?>">
            </div>
            <div class="col-md-2 custom-range-field <?= $period === 'custom' ? '' : 'd-none' ?>">
                <label class="form-label small text-muted">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($range['date_to']) ?>">
            </div>
            <?php if ($type === 'citizens'): ?>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All</option>
                        <option value="Male" <?= $extraFilters['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $extraFilters['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" <?= $extraFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $extraFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            <?php elseif ($type === 'birth_certificates'): ?>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" <?= $extraFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="revoked" <?= $extraFilters['status'] === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                    </select>
                </div>
            <?php elseif ($type === 'national_ids'): ?>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" <?= $extraFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="expired" <?= $extraFilters['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="revoked" <?= $extraFilters['status'] === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                    </select>
                </div>
            <?php elseif ($type === 'applications'): ?>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <?php foreach (getDB()->query('SELECT code, label FROM application_status ORDER BY sort_order')->fetchAll() as $s): ?>
                            <option value="<?= e($s['code']) ?>" <?= $extraFilters['status'] === $s['code'] ? 'selected' : '' ?>><?= e($s['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2">
            <a href="<?= BASE_URL ?>reports/export.php?<?= http_build_query(array_merge($sharedParams, ['type' => $type, 'format' => 'csv'])) ?>" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-file-csv me-1"></i>Export CSV</a>
            <a href="<?= BASE_URL ?>reports/export.php?<?= http_build_query(array_merge($sharedParams, ['type' => $type, 'format' => 'pdf'])) ?>" class="btn btn-outline-danger btn-sm" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>Export PDF</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h6 mb-0"><?= e(reportTypeLabel($type)) ?></h2>
                <div class="text-muted small"><?= formatDate($range['date_from']) ?> &ndash; <?= formatDate($range['date_to']) ?></div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-4 mb-4">
            <?php foreach ($report['summary'] as $key => $value): ?>
                <div>
                    <div class="h5 mb-0"><?= number_format((int) $value) ?></div>
                    <div class="text-muted small text-capitalize"><?= e(str_replace('_', ' ', $key)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($previewRows)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-chart-column"></i>
                <p>No records match this report's filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <?php if ($type === 'citizens'): ?>
                                <th>Citizen ID</th><th>Name</th><th>Gender</th><th>DOB</th><th>Region</th><th>Status</th><th>Registered</th>
                            <?php elseif ($type === 'birth_certificates'): ?>
                                <th>Certificate #</th><th>Citizen ID</th><th>Name</th><th>Gender</th><th>Status</th><th>Registered</th>
                            <?php elseif ($type === 'national_ids'): ?>
                                <th>ID Number</th><th>Citizen ID</th><th>Name</th><th>Issued</th><th>Expires</th><th>Printed</th><th>Collected</th><th>Status</th>
                            <?php elseif ($type === 'applications'): ?>
                                <th>Application #</th><th>Citizen ID</th><th>Name</th><th>Applied</th><th>Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewRows as $row): ?>
                            <?php if ($type === 'citizens'): ?>
                                <tr>
                                    <td><?= e($row['citizen_uid']) ?></td>
                                    <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= e($row['gender']) ?></td>
                                    <td class="small"><?= formatDate($row['date_of_birth']) ?></td>
                                    <td class="small"><?= $row['region'] ? e($row['region']) : '—' ?></td>
                                    <td><?php renderStatusBadge(ucfirst($row['status']), $row['status'] === 'active' ? 'success' : 'secondary'); ?></td>
                                    <td class="small"><?= formatDate($row['registration_date']) ?></td>
                                </tr>
                            <?php elseif ($type === 'birth_certificates'): ?>
                                <tr>
                                    <td><?= e($row['certificate_number']) ?></td>
                                    <td><?= e($row['citizen_uid']) ?></td>
                                    <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= e($row['gender']) ?></td>
                                    <td><?php renderStatusBadge(ucfirst($row['status']), $row['status'] === 'active' ? 'success' : 'secondary'); ?></td>
                                    <td class="small"><?= formatDate($row['registration_date']) ?></td>
                                </tr>
                            <?php elseif ($type === 'national_ids'): ?>
                                <tr>
                                    <td><?= e($row['id_number']) ?></td>
                                    <td><?= e($row['citizen_uid']) ?></td>
                                    <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="small"><?= formatDate($row['issue_date']) ?></td>
                                    <td class="small"><?= formatDate($row['expiry_date']) ?></td>
                                    <td><?= $row['card_printed_at'] ? '<i class="fa-solid fa-check text-success"></i>' : '—' ?></td>
                                    <td><?= $row['collected_at'] ? '<i class="fa-solid fa-check text-success"></i>' : '—' ?></td>
                                    <td><?php renderStatusBadge(ucfirst($row['status']), $row['status'] === 'active' ? 'success' : ($row['status'] === 'expired' ? 'secondary' : 'danger')); ?></td>
                                </tr>
                            <?php elseif ($type === 'applications'): ?>
                                <tr>
                                    <td><?= e($row['application_number']) ?></td>
                                    <td><?= e($row['citizen_uid']) ?></td>
                                    <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="small"><?= formatDate($row['applied_date']) ?></td>
                                    <td><?php renderStatusBadge($row['status_label'], applicationStatusVariant($row['status_code'])); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalRows > PREVIEW_LIMIT): ?>
                <p class="text-muted small mb-0">Showing the first <?= PREVIEW_LIMIT ?> of <?= number_format($totalRows) ?> matching records. Export CSV or PDF to see them all.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 mb-3">Recent Exports</h2>
        <?php if (empty($recentReports)): ?>
            <p class="text-muted small mb-0">No CSV reports have been exported yet. PDF exports are generated live and aren't stored, so they don't appear here.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentReports as $r): ?>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><?= e(reportTypeLabel($r['report_type'])) ?></div>
                        <div class="text-muted small"><?= formatDate($r['date_from']) ?> &ndash; <?= formatDate($r['date_to']) ?> &middot; by <?= e($r['generated_by_name'] ?? 'System') ?> &middot; <?= timeAgo($r['generated_at']) ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>reports/download.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Download</a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageScript = 'reports/reports.js';
require ROOT_PATH . '/layouts/app.php';
