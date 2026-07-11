<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';

$id = (int) ($_GET['id'] ?? 0);
$certificate = $id > 0 ? findBirthCertificateById($id) : null;

if ($certificate === null) {
    setFlash('danger', 'That certificate was not found.');
    redirect('birth/index.php');
}

$pageTitle = 'Certificate ' . $certificate['certificate_number'];
$canManage = hasAnyRole(['Admin', 'Officer']);
$isRevoked = $certificate['status'] === 'revoked';

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard'          => BASE_URL . 'dashboard/index.php',
    'Birth Certificates' => BASE_URL . 'birth/index.php',
    $certificate['certificate_number'] => null,
]); ?>

<?php if ($isRevoked): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-ban me-1"></i>This certificate has been revoked. It is kept on file but no longer considered valid.</span>
    <?php if ($canManage): ?>
        <form action="<?= BASE_URL ?>birth/restore.php" method="post" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $certificate['id'] ?>">
            <button type="submit" class="btn btn-sm btn-warning"><i class="fa-solid fa-rotate-left me-1"></i>Restore</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?php renderAvatar($certificate['photo_path'], 'citizens', $certificate['first_name'], $certificate['last_name'], 'lg'); ?>
        <div class="flex-grow-1">
            <h1 class="h4 mb-1"><?= e(implode(' ', array_filter([$certificate['first_name'], $certificate['middle_name'], $certificate['last_name']]))) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border"><?= e($certificate['certificate_number']) ?></span>
                <span class="badge text-bg-light border"><?= e($certificate['citizen_uid']) ?></span>
                <?php renderStatusBadge(ucfirst($certificate['status']), $isRevoked ? 'secondary' : 'success'); ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>birth/print.php?id=<?= (int) $certificate['id'] ?>" class="btn btn-outline-primary" target="_blank"><i class="fa-solid fa-print me-1"></i>Print</a>
            <?php if ($canManage && !$isRevoked): ?>
                <a href="<?= BASE_URL ?>birth/edit.php?id=<?= (int) $certificate['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#revokeModal"><i class="fa-solid fa-ban me-1"></i>Revoke</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Registration</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Date of Birth</dt>
                    <dd class="col-7"><?= formatDate($certificate['date_of_birth']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Place of Birth</dt>
                    <dd class="col-7"><?= e($certificate['place_of_birth']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Gender</dt>
                    <dd class="col-7"><?= e($certificate['gender']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Registration Date</dt>
                    <dd class="col-7"><?= formatDate($certificate['registration_date']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Place of Registration</dt>
                    <dd class="col-7"><?= $certificate['place_of_registration'] ? e($certificate['place_of_registration']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Issued By</dt>
                    <dd class="col-7"><?= $certificate['issued_by_name'] ? e($certificate['issued_by_name']) : 'System' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Parents</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Father</dt>
                    <dd class="col-7"><?= $certificate['father_full_name'] ? e($certificate['father_full_name']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Father's National ID</dt>
                    <dd class="col-7"><?= $certificate['father_national_id'] ? e($certificate['father_national_id']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Mother</dt>
                    <dd class="col-7"><?= $certificate['mother_full_name'] ? e($certificate['mother_full_name']) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Mother's National ID</dt>
                    <dd class="col-7"><?= $certificate['mother_national_id'] ? e($certificate['mother_national_id']) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage && !$isRevoked): ?>
<div class="modal fade" id="revokeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>birth/delete.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $certificate['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5">Revoke Certificate</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Revoke certificate <strong><?= e($certificate['certificate_number']) ?></strong>? It will be kept on file and can be restored later, but will no longer be considered valid.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Revoke</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/app.php';
