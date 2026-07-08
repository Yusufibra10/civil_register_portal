<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';

$id = (int) ($_GET['id'] ?? 0);
$citizen = $id > 0 ? findCitizenById($id, true) : null;

if ($citizen === null) {
    setFlash('danger', 'That citizen record was not found.');
    redirect('citizens/index.php');
}

$pageTitle = $citizen['first_name'] . ' ' . $citizen['last_name'];
$canManage = hasAnyRole(['Admin', 'Officer']);
$isDeleted = $citizen['deleted_at'] !== null;

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard' => BASE_URL . 'dashboard/index.php',
    'Citizens'  => BASE_URL . 'citizens/index.php',
    $pageTitle  => null,
]); ?>

<?php if ($isDeleted): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-trash-can me-1"></i>This record was deleted <?= timeAgo($citizen['deleted_at']) ?>. It is hidden from the active Citizens list.</span>
    <?php if ($canManage): ?>
        <form action="<?= BASE_URL ?>citizens/restore.php" method="post" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $citizen['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fa-solid fa-rotate-left me-1"></i>Restore</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?php renderAvatar($citizen['photo_path'], 'citizens', $citizen['first_name'], $citizen['last_name'], 'lg'); ?>
        <div class="flex-grow-1">
            <h1 class="h4 mb-1"><?= e(implode(' ', array_filter([$citizen['first_name'], $citizen['middle_name'], $citizen['last_name']]))) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border"><?= e($citizen['citizen_uid']) ?></span>
                <span class="badge text-bg-light border"><?= e($citizen['gender']) ?></span>
                <?php if (!$isDeleted): ?>
                    <?php renderStatusBadge(ucfirst($citizen['status']), $citizen['status'] === 'active' ? 'success' : 'secondary'); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($canManage && !$isDeleted): ?>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>citizens/edit.php?id=<?= (int) $citizen['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fa-solid fa-trash me-1"></i>Delete</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Personal Information</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Date of Birth</dt>
                    <dd class="col-7"><?= formatDate($citizen['date_of_birth']) ?> (<?= calculateAge($citizen['date_of_birth']) ?> years)</dd>
                    <dt class="col-5 text-muted fw-normal">Place of Birth</dt>
                    <dd class="col-7"><?= e($citizen['place_of_birth']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Marital Status</dt>
                    <dd class="col-7"><?= $citizen['marital_status'] ? e($citizen['marital_status']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Nationality</dt>
                    <dd class="col-7"><?= e($citizen['nationality']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Occupation</dt>
                    <dd class="col-7"><?= $citizen['occupation'] ? e($citizen['occupation']) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Contact &amp; Location</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7"><?= $citizen['phone'] ? e($citizen['phone']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7"><?= $citizen['email'] ? e($citizen['email']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Address</dt>
                    <dd class="col-7"><?= $citizen['address'] ? e($citizen['address']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Region</dt>
                    <dd class="col-7"><?= $citizen['region'] ? e($citizen['region']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">District</dt>
                    <dd class="col-7"><?= $citizen['district'] ? e($citizen['district']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Village</dt>
                    <dd class="col-7"><?= $citizen['village'] ? e($citizen['village']) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Identification</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">National ID Number</dt>
                    <dd class="col-7"><?= $citizen['national_id_number'] ? e($citizen['national_id_number']) : '<span class="text-muted">Not yet issued</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Registration</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Registered By</dt>
                    <dd class="col-7"><?= $citizen['registered_by_name'] ? e($citizen['registered_by_name']) : 'System' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Registration Date</dt>
                    <dd class="col-7"><?= formatDate($citizen['registration_date']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Last Updated</dt>
                    <dd class="col-7"><?= formatDate($citizen['updated_at'], 'd M Y, g:i A') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage && !$isDeleted): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>citizens/delete.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $citizen['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5">Delete Citizen</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete <strong><?= e($citizen['first_name'] . ' ' . $citizen['last_name']) ?></strong>? This moves the record to Deleted Citizens, where it can be restored later.</p>
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
