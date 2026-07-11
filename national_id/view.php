<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/national_ids_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';
require_once ROOT_PATH . '/components/status_badge.php';

$id = (int) ($_GET['id'] ?? 0);
$nationalId = $id > 0 ? findNationalIdById($id) : null;

if ($nationalId === null) {
    setFlash('danger', 'That National ID was not found.');
    redirect('national_id/index.php');
}

$pageTitle = 'National ID ' . $nationalId['id_number'];
$canManage = hasAnyRole(['Admin', 'Officer']);
$isExpired = strtotime($nationalId['expiry_date']) < strtotime('today');

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard'     => BASE_URL . 'dashboard/index.php',
    'National IDs'  => BASE_URL . 'national_id/index.php',
    $nationalId['id_number'] => null,
]); ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <?php renderAvatar($nationalId['photo_path'], 'citizens', $nationalId['first_name'], $nationalId['last_name'], 'lg'); ?>
        <div class="flex-grow-1">
            <h1 class="h4 mb-1"><?= e(implode(' ', array_filter([$nationalId['first_name'], $nationalId['middle_name'], $nationalId['last_name']]))) ?></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border"><?= e($nationalId['id_number']) ?></span>
                <span class="badge text-bg-light border"><?= e($nationalId['citizen_uid']) ?></span>
                <?php renderStatusBadge(ucfirst($nationalId['status']), nationalIdStatusVariant($nationalId['status'])); ?>
                <?php if ($isExpired && $nationalId['status'] === 'active'): ?>
                    <span class="badge text-bg-warning">Past expiry date</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>national_id/print.php?id=<?= (int) $nationalId['id'] ?>" class="btn btn-outline-primary" target="_blank"><i class="fa-solid fa-print me-1"></i>Print</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Card Details</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Issue Date</dt>
                    <dd class="col-7"><?= formatDate($nationalId['issue_date']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Expiry Date</dt>
                    <dd class="col-7"><?= formatDate($nationalId['expiry_date']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Issued By</dt>
                    <dd class="col-7"><?= $nationalId['issued_by_name'] ? e($nationalId['issued_by_name']) : 'System' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Printing &amp; Collection</h2>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Card Printed</dt>
                    <dd class="col-7"><?= $nationalId['card_printed_at'] ? formatDate($nationalId['card_printed_at'], 'd M Y, g:i A') : '<span class="text-muted">Not yet printed</span>' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Collected</dt>
                    <dd class="col-7"><?= $nationalId['collected_at'] ? formatDate($nationalId['collected_at'], 'd M Y, g:i A') : '<span class="text-muted">Not yet collected</span>' ?></dd>
                </dl>
                <?php if ($canManage && $nationalId['card_printed_at'] && !$nationalId['collected_at']): ?>
                    <form action="<?= BASE_URL ?>national_id/collect.php" method="post" class="mt-3 pt-3 border-top">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $nationalId['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-hand-holding me-1"></i>Mark as Collected</button>
                    </form>
                <?php elseif (!$nationalId['card_printed_at']): ?>
                    <p class="text-muted small mt-3 pt-3 border-top mb-0">Collection can be recorded once the card has been printed (via the linked application's workflow).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/app.php';
