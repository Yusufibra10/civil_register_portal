<?php
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'Register Birth Certificate';
$errors = [];

// ---------------------------------------------------------------------
// Step 1: pick the citizen this certificate belongs to. The brief
// requires the citizen to already exist, so this is search-and-select,
// never an inline "create citizen" shortcut.
// ---------------------------------------------------------------------
$pickerBaseUrl = BASE_URL . 'birth/create.php';
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$selectedCitizenId = (int) ($_GET['citizen_id'] ?? 0);
$selectedCitizen = $selectedCitizenId > 0 ? findCitizenById($selectedCitizenId) : null;
$searchResults = [];

if ($selectedCitizenId > 0 && $selectedCitizen === null) {
    setFlash('danger', 'That citizen record was not found.');
    redirect('birth/create.php');
}

if ($selectedCitizen === null && $searchQuery !== '') {
    $searchResults = getCitizensList(['search' => $searchQuery], 1, 8)['rows'];
}

$existingCertificate = $selectedCitizen !== null
    ? findBirthCertificateByCitizenId((int) $selectedCitizen['id'])
    : null;

$certificate = array_fill_keys(BIRTH_CERTIFICATE_WRITABLE_FIELDS, '');
$certificate['registration_date'] = date('Y-m-d');

// ---------------------------------------------------------------------
// Step 2: the certificate's own fields, only reachable once a citizen
// without an existing certificate has been selected.
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selectedCitizen === null || $existingCertificate !== null) {
        redirect('birth/create.php');
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('birth/create.php?citizen_id=' . $selectedCitizen['id']);
    }

    $input = sanitizeArray($_POST);
    $certificate = array_merge($certificate, array_intersect_key($input, $certificate));

    $errors = validate($input, birthCertificateValidationRules());
    $data = normalizeBirthCertificateData($input);

    if (empty($errors)) {
        $newId = createBirthCertificate($data, (int) $selectedCitizen['id'], (int) $_SESSION['user_id']);

        logActivity(
            $_SESSION['user_id'],
            'CREATE_BIRTH_CERTIFICATE',
            'birth',
            "Registered a birth certificate for {$selectedCitizen['first_name']} {$selectedCitizen['last_name']}."
        );
        setFlash('success', 'Birth certificate registered successfully.');
        redirect('birth/view.php?id=' . $newId);
    }
}

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Birth Certificates' => BASE_URL . 'birth/index.php', 'Register' => null]); ?>

<h1 class="h4 mb-3">Register Birth Certificate</h1>


<?php if ($selectedCitizen === null || $existingCertificate !== null): ?>
    <?php
    // Not yet in a submittable state (still searching, or the selected
    // citizen is ineligible) — the picker below renders its own GET
    // search form here, so this must stay outside any <form method="post">
    // to avoid an invalid nested <form>.
    require ROOT_PATH . '/components/citizen_picker.php';
    ?>
    <?php if ($existingCertificate !== null): ?>
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            This citizen already has a birth certificate
            (<strong><?= e($existingCertificate['certificate_number']) ?></strong>, <?= e($existingCertificate['status']) ?>).
            <a href="<?= BASE_URL ?>birth/view.php?id=<?= (int) $existingCertificate['id'] ?>">View it</a> instead of registering a new one.
        </div>
    <?php endif; ?>
<?php else: ?>
    <form method="post" novalidate id="birthForm">
        <?= csrfField() ?>
        <?php // Citizen already selected — this branch of citizen_picker.php renders only a summary + hidden input, no nested <form>. ?>
        <?php require ROOT_PATH . '/components/citizen_picker.php'; ?>
        <?php require ROOT_PATH . '/birth/_form_fields.php'; ?>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Certificate</button>
            <a href="<?= BASE_URL ?>birth/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageScript = 'birth/birth.js';
require ROOT_PATH . '/layouts/app.php';
