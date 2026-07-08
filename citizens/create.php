<?php
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/helpers/uploads.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'Add Citizen';
$errors = [];

$citizen = array_fill_keys(CITIZEN_WRITABLE_FIELDS, '');
$citizen['nationality'] = 'Somaliland';
$citizen['status'] = 'active';
$citizen['registration_date'] = date('Y-m-d');
$citizen['photo_path'] = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('citizens/create.php');
    }

    $input = sanitizeArray($_POST);
    $citizen = array_merge($citizen, array_intersect_key($input, $citizen));

    $errors = validate($input, citizenValidationRules());

    $data = normalizeCitizenData($input);

    if (!empty($data['national_id_number']) && citizenNationalIdExists($data['national_id_number'])) {
        $errors['national_id_number'][] = 'This National ID number is already registered to another citizen.';
    }

    if (!empty($data['email']) && citizenEmailExists($data['email'])) {
        $errors['email'][] = 'This email is already registered to another citizen.';
    }

    $photoErrors = validateImageUpload($_FILES['photo'] ?? []);
    if (!empty($photoErrors)) {
        $errors['photo'] = $photoErrors;
    }

    if (empty($errors)) {
        $photoFilename = null;
        if (!empty($_FILES['photo']['name'])) {
            $photoFilename = storeUploadedImage($_FILES['photo'], 'citizens');
        }

        try {
            $newId = createCitizen($data, $photoFilename, (int) $_SESSION['user_id']);
        } catch (Throwable $e) {
            // The photo is already on disk at this point; if the insert
            // failed, don't leave it orphaned with nothing pointing to it.
            if ($photoFilename !== null) {
                deleteUploadedImage($photoFilename, 'citizens');
            }
            throw $e;
        }

        logActivity($_SESSION['user_id'], 'CREATE_CITIZEN', 'citizens', "Registered {$data['first_name']} {$data['last_name']}.");
        setFlash('success', 'Citizen registered successfully.');
        redirect('citizens/view.php?id=' . $newId);
    }
}

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Citizens' => BASE_URL . 'citizens/index.php', 'Add Citizen' => null]); ?>

<h1 class="h4 mb-3">Add Citizen</h1>

<form method="post" enctype="multipart/form-data" novalidate id="citizenForm">
    <?= csrfField() ?>
    <?php require ROOT_PATH . '/citizens/_form_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Citizen</button>
        <a href="<?= BASE_URL ?>citizens/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'citizens/citizens.js';
require ROOT_PATH . '/layouts/app.php';
