<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';

$id = (int) ($_GET['id'] ?? 0);
$certificate = $id > 0 ? findBirthCertificateById($id) : null;

if ($certificate === null) {
    setFlash('danger', 'That certificate was not found.');
    redirect('birth/index.php');
}

$pageTitle = 'Certificate ' . $certificate['certificate_number'];
$backUrl = BASE_URL . 'birth/index.php';

ob_start();
?>
<div class="certificate-border text-center">
    <div class="certificate-seal mb-2"><i class="fa-solid fa-landmark"></i></div>
    <div class="text-uppercase small text-muted" style="letter-spacing:0.1em;">Government of Somaliland</div>
    <h1 class="h4 mt-1 mb-0">Certificate of Birth Registration</h1>
    <div class="text-muted small mb-4">Certificate No. <?= e($certificate['certificate_number']) ?></div>

    <p class="mb-4">This is to certify that the birth of</p>
    <h2 class="h3 mb-1"><?= e(implode(' ', array_filter([$certificate['first_name'], $certificate['middle_name'], $certificate['last_name']]))) ?></h2>
    <p class="text-muted mb-4">
        <?= e($certificate['gender']) ?>, born <?= formatDate($certificate['date_of_birth']) ?> in <?= e($certificate['place_of_birth']) ?>
    </p>
    <p class="mb-4">has been duly registered with the Civil Registry, as recorded below.</p>

    <div class="row text-start g-3 mt-3 mx-auto" style="max-width:520px;">
        <div class="col-6"><span class="text-muted small">Citizen ID</span><br><?= e($certificate['citizen_uid']) ?></div>
        <div class="col-6"><span class="text-muted small">Registration Date</span><br><?= formatDate($certificate['registration_date']) ?></div>
        <div class="col-6"><span class="text-muted small">Place of Registration</span><br><?= $certificate['place_of_registration'] ? e($certificate['place_of_registration']) : '—' ?></div>
        <div class="col-6"><span class="text-muted small">Father</span><br><?= $certificate['father_full_name'] ? e($certificate['father_full_name']) : '—' ?></div>
        <div class="col-6"><span class="text-muted small">Mother</span><br><?= $certificate['mother_full_name'] ? e($certificate['mother_full_name']) : '—' ?></div>
    </div>

    <div class="row text-start g-3 mt-4 pt-4 mx-auto border-top" style="max-width:520px;">
        <div class="col-6">
            <div style="border-bottom:1px solid #333;height:40px;"></div>
            <span class="text-muted small">Registrar's Signature</span>
        </div>
        <div class="col-6">
            <div style="border-bottom:1px solid #333;height:40px;"></div>
            <span class="text-muted small">Official Seal</span>
        </div>
    </div>

    <p class="text-muted small mt-4 mb-0">
        Verify this certificate at <?= e(BASE_URL) ?>birth/verify.php using certificate number <?= e($certificate['certificate_number']) ?>.
    </p>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/print.php';
