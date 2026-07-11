<?php
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$id = (int) ($_GET['id'] ?? 0);
$existing = $id > 0 ? findBirthCertificateById($id) : null;

if ($existing === null) {
    setFlash('danger', 'That certificate was not found.');
    redirect('birth/index.php');
}

if ($existing['status'] !== 'active') {
    setFlash('danger', 'A revoked certificate must be restored before it can be edited.');
    redirect('birth/view.php?id=' . $id);
}

$pageTitle = 'Edit Birth Certificate';
$errors = [];
$certificate = array_intersect_key($existing, array_flip(BIRTH_CERTIFICATE_WRITABLE_FIELDS));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('birth/edit.php?id=' . $id);
    }

    $input = sanitizeArray($_POST);
    $certificate = array_merge($certificate, array_intersect_key($input, array_flip(BIRTH_CERTIFICATE_WRITABLE_FIELDS)));

    $errors = validate($input, birthCertificateValidationRules());
    $data = normalizeBirthCertificateData($input);

    if (empty($errors)) {
        updateBirthCertificate($id, $data);

        logActivity(
            $_SESSION['user_id'],
            'UPDATE_BIRTH_CERTIFICATE',
            'birth',
            "Updated birth certificate {$existing['certificate_number']}."
        );
        setFlash('success', 'Birth certificate updated successfully.');
        redirect('birth/view.php?id=' . $id);
    }
}

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard'          => BASE_URL . 'dashboard/index.php',
    'Birth Certificates' => BASE_URL . 'birth/index.php',
    'Edit'               => null,
]); ?>

<h1 class="h4 mb-3">Edit Birth Certificate</h1>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex align-items-center gap-3">
        <?php renderAvatar($existing['photo_path'], 'citizens', $existing['first_name'], $existing['last_name'], 'sm'); ?>
        <div>
            <div class="fw-semibold"><?= e($existing['first_name'] . ' ' . $existing['last_name']) ?></div>
            <div class="text-muted small"><?= e($existing['citizen_uid']) ?> &middot; Certificate <?= e($existing['certificate_number']) ?></div>
        </div>
    </div>
</div>

<form method="post" novalidate id="birthForm">
    <?= csrfField() ?>
    <?php require ROOT_PATH . '/birth/_form_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Changes</button>
        <a href="<?= BASE_URL ?>birth/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'birth/birth.js';
require ROOT_PATH . '/layouts/app.php';
