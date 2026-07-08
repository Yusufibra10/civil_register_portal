<?php
/**
 * Public certificate verification — deliberately no auth middleware.
 * Anyone holding a physical certificate can confirm it's genuine by its
 * printed number. Only shows what's already printed on the certificate
 * itself (name, DOB, status, registration date) — never phone, email,
 * address, or photo, even though those exist on the underlying record.
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';
require_once ROOT_PATH . '/components/status_badge.php';

$pageTitle = 'Verify Birth Certificate';
$certificateNumber = trim((string) ($_GET['certificate_number'] ?? ''));
$searched = $certificateNumber !== '';
$certificate = null;
$rateLimited = false;

if ($searched) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (isRateLimited('CERT_VERIFY', $clientIp, 20, 15)) {
        $rateLimited = true;
    } else {
        logActivity(null, 'CERT_VERIFY', 'birth', $clientIp);
        $certificate = findBirthCertificateByCertificateNumber($certificateNumber);
    }
}

ob_start();
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="fa-solid fa-shield-halved fs-1 text-primary"></i>
            <h1 class="h4 mt-2 mb-0">Verify Birth Certificate</h1>
            <p class="text-muted small">Enter the certificate number printed on the document.</p>
        </div>

        <form method="get" class="d-flex gap-2 mb-3">
            <input type="text" name="certificate_number" class="form-control" placeholder="e.g. BC-2026-4821" value="<?= e($certificateNumber) ?>" required>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <?php if ($rateLimited): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation me-1"></i><strong>Too many lookups.</strong> Please wait a few minutes before trying again.
            </div>
        <?php elseif ($searched && $certificate): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check me-1"></i><strong>Certificate found.</strong>
            </div>
            <dl class="row mb-0 small">
                <dt class="col-5 text-muted fw-normal">Certificate No.</dt>
                <dd class="col-7"><?= e($certificate['certificate_number']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Name</dt>
                <dd class="col-7"><?= e(implode(' ', array_filter([$certificate['first_name'], $certificate['middle_name'], $certificate['last_name']]))) ?></dd>
                <dt class="col-5 text-muted fw-normal">Gender</dt>
                <dd class="col-7"><?= e($certificate['gender']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Place of Birth</dt>
                <dd class="col-7"><?= e($certificate['place_of_birth']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Date of Birth</dt>
                <dd class="col-7"><?= formatDate($certificate['date_of_birth']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Registration Date</dt>
                <dd class="col-7"><?= formatDate($certificate['registration_date']) ?></dd>
                <dt class="col-5 text-muted fw-normal">Status</dt>
                <dd class="col-7"><?php renderStatusBadge(ucfirst($certificate['status']), $certificate['status'] === 'active' ? 'success' : 'secondary'); ?></dd>
            </dl>
        <?php elseif ($searched): ?>
            <div class="empty-state">
                <i class="fa-solid fa-circle-question"></i>
                <p><strong>Certificate Not Found</strong><br>No certificate matches that number. Check it against the printed document and try again.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/guest.php';
