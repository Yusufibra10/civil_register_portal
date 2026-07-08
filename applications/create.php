<?php
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';
require_once ROOT_PATH . '/components/avatar.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'New ID Application';
$errors = [];

// ---------------------------------------------------------------------
// Step 1: pick the citizen — same search-and-select widget birth/create.php uses.
// ---------------------------------------------------------------------
$pickerBaseUrl = BASE_URL . 'applications/create.php';
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$selectedCitizenId = (int) ($_GET['citizen_id'] ?? 0);
$selectedCitizen = $selectedCitizenId > 0 ? findCitizenById($selectedCitizenId) : null;
$searchResults = [];

if ($selectedCitizenId > 0 && $selectedCitizen === null) {
    setFlash('danger', 'That citizen record was not found.');
    redirect('applications/create.php');
}

if ($selectedCitizen === null && $searchQuery !== '') {
    $searchResults = getCitizensList(['search' => $searchQuery], 1, 8)['rows'];
}

$eligibilityError = null;
if ($selectedCitizen !== null) {
    if (citizenHasOpenApplication((int) $selectedCitizen['id'])) {
        $eligibilityError = 'This citizen already has an application in progress. It must be resolved before a new one can be submitted.';
    } elseif (citizenHasNationalId((int) $selectedCitizen['id'])) {
        $eligibilityError = 'This citizen already has a National ID on file.';
    }
}

$appliedDate = date('Y-m-d');
$remarks = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selectedCitizen === null || $eligibilityError !== null) {
        redirect('applications/create.php');
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('applications/create.php?citizen_id=' . $selectedCitizen['id']);
    }

    $input = sanitizeArray($_POST);
    $appliedDate = $input['applied_date'] ?? $appliedDate;
    $remarks = $input['remarks'] ?? '';

    $errors = validate($input, [
        'applied_date' => 'required|date|not_future',
        'remarks'      => 'max:255',
    ]);

    if (empty($errors)) {
        $newId = createApplication(
            (int) $selectedCitizen['id'],
            $input['applied_date'],
            $remarks !== '' ? $remarks : null,
            (int) $_SESSION['user_id']
        );

        logActivity(
            $_SESSION['user_id'],
            'CREATE_APPLICATION',
            'applications',
            "Submitted a National ID application for {$selectedCitizen['first_name']} {$selectedCitizen['last_name']}."
        );
        setFlash('success', 'Application submitted successfully.');
        redirect('applications/view.php?id=' . $newId);
    }
}

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'ID Applications' => BASE_URL . 'applications/index.php', 'New' => null]); ?>

<h1 class="h4 mb-3">New ID Application</h1>

<?php if ($selectedCitizen === null || $eligibilityError !== null): ?>
    <?php
    // Not yet in a submittable state — the picker below renders its own
    // GET search form here, so this must stay outside any
    // <form method="post"> to avoid an invalid nested <form>.
    require ROOT_PATH . '/components/citizen_picker.php';
    ?>
    <?php if ($eligibilityError !== null): ?>
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($eligibilityError) ?>
            <a href="<?= BASE_URL ?>applications/index.php?search=<?= urlencode($selectedCitizen['citizen_uid']) ?>">View their applications</a>.
        </div>
    <?php endif; ?>
<?php else: ?>
    <form method="post" novalidate id="applicationForm">
        <?= csrfField() ?>
        <?php // Citizen already selected — this branch of citizen_picker.php renders only a summary + hidden input, no nested <form>. ?>
        <?php require ROOT_PATH . '/components/citizen_picker.php'; ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Application Details</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Applied Date <span class="text-danger">*</span></label>
                        <input type="date" name="applied_date" class="form-control <?= fieldInvalidClass($errors, 'applied_date') ?>" value="<?= e($appliedDate) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                        <?= fieldErrorHtml($errors, 'applied_date') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="remarks" class="form-control <?= fieldInvalidClass($errors, 'remarks') ?>" rows="3" maxlength="255"><?= e($remarks) ?></textarea>
                        <?= fieldErrorHtml($errors, 'remarks') ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Submit Application</button>
            <a href="<?= BASE_URL ?>applications/index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageScript = 'applications/applications.js';
require ROOT_PATH . '/layouts/app.php';
