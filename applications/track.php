<?php
/**
 * Public application tracking — deliberately no auth middleware. A
 * citizen who only knows their own application number can check its
 * progress without an account. Staff identity (who changed what) is
 * intentionally left out of the public timeline, even though the same
 * history powers the internal applications/view.php page too.
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';
require_once ROOT_PATH . '/components/status_badge.php';
require_once ROOT_PATH . '/components/timeline.php';

$pageTitle = 'Track Your Application';
$applicationNumber = trim((string) ($_GET['application_number'] ?? ''));
$searched = $applicationNumber !== '';
$application = null;
$rateLimited = false;

if ($searched) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (isRateLimited('APP_TRACK', $clientIp, 20, 15)) {
        $rateLimited = true;
    } else {
        logActivity(null, 'APP_TRACK', 'applications', $clientIp);
        $application = findApplicationByNumber($applicationNumber);
    }
}

$timelineEvents = [];
if ($application !== null) {
    $timelineEvents = array_map(static function (array $row): array {
        return [
            'label'   => $row['status_label'],
            'variant' => applicationStatusVariant($row['status_code']),
            'at'      => $row['changed_at'],
            'remarks' => $row['remarks'],
        ];
    }, getApplicationTimeline($application['id']));
}

ob_start();
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="fa-solid fa-magnifying-glass-location fs-1 text-primary"></i>
            <h1 class="h4 mt-2 mb-0">Track Your Application</h1>
            <p class="text-muted small">Enter your application number to see its current status.</p>
        </div>

        <form method="get" class="d-flex gap-2 mb-3">
            <input type="text" name="application_number" class="form-control" placeholder="e.g. APP-2026-4821" value="<?= e($applicationNumber) ?>" required>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <?php if ($rateLimited): ?>
            <div class="empty-state">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p><strong>Too many lookups.</strong><br>Please wait a few minutes before trying again.</p>
            </div>
        <?php elseif ($searched && $application): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-semibold"><?= e($application['first_name'] . ' ' . $application['last_name']) ?></div>
                    <div class="text-muted small"><?= e($application['application_number']) ?></div>
                </div>
                <?php renderStatusBadge($application['status_label'], applicationStatusVariant($application['status_code'])); ?>
            </div>
            <dl class="row small">
                <dt class="col-5 text-muted fw-normal">Submitted</dt>
                <dd class="col-7"><?= formatDate($application['applied_date']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Last Update</dt>
                <dd class="col-7"><?= formatDate($application['updated_at'], 'd M Y, g:i A') ?></dd>
            </dl>
            <h2 class="h6 mt-4 mb-3">Processing History</h2>
            <?php renderTimeline($timelineEvents); ?>
        <?php elseif ($searched): ?>
            <div class="empty-state">
                <i class="fa-solid fa-circle-question"></i>
                <p><strong>Application Not Found</strong><br>No application matches that number. Double-check it against your receipt and try again.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/guest.php';
