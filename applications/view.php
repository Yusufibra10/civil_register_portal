<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';
require_once ROOT_PATH . '/components/timeline.php';

$id = (int) ($_GET['id'] ?? 0);
$application = $id > 0 ? findApplicationById($id) : null;

if ($application === null) {
    setFlash('danger', 'That application was not found.');
    redirect('applications/index.php');
}

$pageTitle = 'Application ' . $application['application_number'];
$canManage = hasAnyRole(['Admin', 'Officer']);
$currentUser = currentUser();

$timelineEvents = array_map(static function (array $row): array {
    return [
        'label'   => $row['status_label'],
        'variant' => applicationStatusVariant($row['status_code']),
        'by'      => $row['changed_by_name'],
        'at'      => $row['changed_at'],
        'remarks' => $row['remarks'],
    ];
}, getApplicationTimeline($id));

$statusLabels = [];
foreach (getDB()->query('SELECT code, label FROM application_status')->fetchAll() as $row) {
    $statusLabels[$row['code']] = $row['label'];
}
$allowedNext = APPLICATION_STATUS_TRANSITIONS[$application['status_code']] ?? [];

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard'       => BASE_URL . 'dashboard/index.php',
    'ID Applications' => BASE_URL . 'applications/index.php',
    $application['application_number'] => null,
]); ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?php renderAvatar($application['photo_path'], 'citizens', $application['first_name'], $application['last_name'], 'lg'); ?>
        <div class="flex-grow-1">
            <h1 class="h4 mb-1"><?= e(implode(' ', array_filter([$application['first_name'], $application['middle_name'], $application['last_name']]))) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border"><?= e($application['application_number']) ?></span>
                <span class="badge text-bg-light border"><?= e($application['citizen_uid']) ?></span>
                <?php renderStatusBadge($application['status_label'], applicationStatusVariant($application['status_code'])); ?>
            </div>
        </div>
        <a href="<?= BASE_URL ?>citizens/view.php?id=<?= (int) $application['citizen_id'] ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-user me-1"></i>View Citizen</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Details</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Applied Date</dt>
                    <dd class="col-7"><?= formatDate($application['applied_date']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Processing Officer</dt>
                    <dd class="col-7"><?= $application['assigned_to_name'] ? e($application['assigned_to_name']) : '<span class="text-muted">Unassigned</span>' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Notes</dt>
                    <dd class="col-7"><?= $application['remarks'] ? e($application['remarks']) : '—' ?></dd>
                </dl>
                <?php if ($canManage): ?>
                    <form action="<?= BASE_URL ?>applications/assign.php" method="post" class="mt-3 pt-3 border-top">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $application['id'] ?>">
                        <?php if ((int) ($application['assigned_to'] ?? 0) === (int) $currentUser['id']): ?>
                            <button type="submit" name="action" value="unassign" class="btn btn-sm btn-outline-secondary">Unassign Myself</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="assign_me" class="btn btn-sm btn-outline-primary">Assign to Me</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canManage && !empty($allowedNext)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Update Status</h2>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($allowedNext as $nextCode): ?>
                        <button type="button" class="btn btn-sm <?= $nextCode === 'rejected' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                data-bs-toggle="modal" data-bs-target="#statusModal"
                                data-status-code="<?= e($nextCode) ?>" data-status-label="<?= e($statusLabels[$nextCode] ?? $nextCode) ?>">
                            Move to &ldquo;<?= e($statusLabels[$nextCode] ?? $nextCode) ?>&rdquo;
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php elseif ($canManage): ?>
        <div class="alert alert-secondary mb-0"><i class="fa-solid fa-circle-check me-1"></i>This application has reached a final state.</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Status History</h2>
                <?php renderTimeline($timelineEvents); ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage && !empty($allowedNext)): ?>
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>applications/update_status.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $application['id'] ?>">
                <input type="hidden" name="target_status_code" id="statusModalCode" value="">
                <div class="modal-header">
                    <h2 class="modal-title h5">Move to <span id="statusModalLabel"></span></h2>
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
