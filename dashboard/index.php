<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/dashboard_stats.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Dashboard';
$user = currentUser();
$canAct = hasAnyRole(['Admin', 'Officer']); // Viewer is read-only

$citizenStats  = getCitizenStats();
$birthStats    = getBirthCertificateStats();
$nidStats      = getNationalIdStats();
$appStats      = getApplicationStats();
$statusDist    = getApplicationStatusDistribution();
$monthlyRegs   = getMonthlyRegistrations(6);
$recentApps    = getRecentApplications(5);
$recentCitizens = getRecentCitizens(5);
$recentLogs    = getRecentActivityLogs(8);

ob_start();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
        <p class="text-muted mb-0">Here's what's happening with the civil registry today &middot; <?= e(date('l, d F Y')) ?></p>
    </div>
    <span class="badge rounded-pill text-bg-light border"><i class="fa-solid fa-user-shield me-1"></i><?= e($user['role_name']) ?></span>
</div>

<!-- Stat cards -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="text-muted small">Total Citizens</div>
                    <div class="h4 mb-0"><?= number_format((int) $citizenStats['total']) ?></div>
                    <div class="small text-success">+<?= (int) $citizenStats['this_month'] ?> this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-baby"></i></div>
                <div>
                    <div class="text-muted small">Birth Certificates</div>
                    <div class="h4 mb-0"><?= number_format((int) $birthStats['total']) ?></div>
                    <div class="small text-success">+<?= (int) $birthStats['this_month'] ?> this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-purple-subtle text-purple"><i class="fa-solid fa-id-card"></i></div>
                <div>
                    <div class="text-muted small">National IDs Issued</div>
                    <div class="h4 mb-0"><?= number_format((int) $nidStats['total']) ?></div>
                    <div class="small text-success">+<?= (int) $nidStats['this_month'] ?> this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-secondary-subtle text-secondary"><i class="fa-solid fa-file-circle-check"></i></div>
                <div>
                    <div class="text-muted small">Total Applications</div>
                    <div class="h4 mb-0"><?= number_format((int) $appStats['total']) ?></div>
                    <div class="small text-muted">All time</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="text-muted small">Pending Applications</div>
                    <div class="h4 mb-0"><?= number_format((int) $appStats['pending']) ?></div>
                    <div class="small text-muted">Awaiting a decision</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="text-muted small">Approved Applications</div>
                    <div class="h4 mb-0"><?= number_format((int) $appStats['approved']) ?></div>
                    <div class="small text-muted">Approved or ready for collection</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark"></i></div>
                <div>
                    <div class="text-muted small">Rejected Applications</div>
                    <div class="h4 mb-0"><?= number_format((int) $appStats['rejected']) ?></div>
                    <div class="small text-muted">All time</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Monthly Registrations</h2>
                <div class="chart-wrap">
                    <div class="chart-loading" data-chart-loading="monthlyRegistrationsChart"><span class="spinner-border spinner-border-sm"></span></div>
                    <canvas id="monthlyRegistrationsChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Application Status Distribution</h2>
                <div class="chart-wrap">
                    <div class="chart-loading" data-chart-loading="statusDistributionChart"><span class="spinner-border spinner-border-sm"></span></div>
                    <canvas id="statusDistributionChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Recent Applications</h2>
                    <a href="<?= BASE_URL ?>applications/index.php" class="small">View all</a>
                </div>
                <?php if (empty($recentApps)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>No applications have been submitted yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th>Application #</th>
                                    <th>Applicant</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApps as $app): ?>
                                <tr>
                                    <td class="fw-semibold"><a href="<?= BASE_URL ?>applications/view.php?id=<?= (int) $app['id'] ?>"><?= e($app['application_number']) ?></a></td>
                                    <td><?= e($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                    <td><?= formatDate($app['applied_date']) ?></td>
                                    <td><?php renderStatusBadge($app['status_label'], applicationStatusVariant($app['status_code'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Quick Actions</h2>
                <?php if ($canAct): ?>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>birth/create.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-baby me-2"></i>Register Birth</a>
                        <a href="<?= BASE_URL ?>applications/create.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-file-circle-plus me-2"></i>New ID Application</a>
                        <a href="<?= BASE_URL ?>citizens/index.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-magnifying-glass me-2"></i>Search Citizen</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-eye"></i>
                        <p>Your Viewer role has read-only access. Quick actions are available to Officers and Admins.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Recent Citizens</h2>
                    <a href="<?= BASE_URL ?>citizens/index.php" class="small">View all</a>
                </div>
                <?php if (empty($recentCitizens)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-address-card"></i>
                        <p>No citizens have been registered yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentCitizens as $citizen): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold"><?= e($citizen['first_name'] . ' ' . $citizen['last_name']) ?></div>
                                <div class="text-muted small"><?= e($citizen['citizen_uid']) ?> &middot; <?= e($citizen['gender']) ?></div>
                            </div>
                            <span class="text-muted small"><?= timeAgo($citizen['created_at']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Latest Activity Logs</h2>
                <?php if (empty($recentLogs)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-clock"></i>
                        <p>No activity recorded yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= e($log['full_name'] ?? 'System') ?></span>
                                <span class="text-muted small"><?= timeAgo($log['created_at']) ?></span>
                            </div>
                            <div class="text-muted small"><?= e($log['action']) ?><?= $log['description'] ? ' — ' . e($log['description']) : '' ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="dashboardData"><?= json_encode([
    'monthlyRegistrations' => [
        'labels' => array_map(fn ($m) => date('M Y', strtotime($m . '-01')), array_keys($monthlyRegs)),
        'data'   => array_values($monthlyRegs),
    ],
    'statusDistribution' => [
        'labels' => array_column($statusDist, 'label'),
        'data'   => array_map('intval', array_column($statusDist, 'total')),
    ],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php
$content = ob_get_clean();
$pageScript = 'dashboard/dashboard.js';
require ROOT_PATH . '/layouts/app.php';
