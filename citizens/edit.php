<?php
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';
require_once ROOT_PATH . '/helpers/uploads.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$id = (int) ($_GET['id'] ?? 0);
$existing = $id > 0 ? findCitizenById($id) : null;

if ($existing === null) {
    setFlash('danger', 'That citizen record was not found, or has been deleted.');
    redirect('citizens/index.php');
}

$pageTitle = 'Edit Citizen';
$errors = [];

$citizen = array_intersect_key($existing, array_flip(CITIZEN_WRITABLE_FIELDS));
$citizen['photo_path'] = $existing['photo_path'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('citizens/edit.php?id=' . $id);
    }

    $input = sanitizeArray($_POST);
    $citizen = array_merge($citizen, array_intersect_key($input, array_flip(CITIZEN_WRITABLE_FIELDS)));
    $citizen['photo_path'] = $existing['photo_path'];

    $errors = validate($input, citizenValidationRules());

    $data = normalizeCitizenData($input);

    if (!empty($data['national_id_number']) && citizenNationalIdExists($data['national_id_number'], $id)) {
        $errors['national_id_number'][] = 'This National ID number is already registered to another citizen.';
    }

    if (!empty($data['email']) && citizenEmailExists($data['email'], $id)) {
        $errors['email'][] = 'This email is already registered to another citizen.';
    }

    $photoErrors = validateImageUpload($_FILES['photo'] ?? []);
    if (!empty($photoErrors)) {
        $errors['photo'] = $photoErrors;
    }

    if (empty($errors)) {
        $oldPhoto = $existing['photo_path'];
        $newPhotoFilename = $oldPhoto;
        $replacingPhoto = false;

        if (!empty($_FILES['photo']['name'])) {
            $newPhotoFilename = storeUploadedImage($_FILES['photo'], 'citizens');
            $replacingPhoto = true;
        } elseif (!empty($input['remove_photo'])) {
            $newPhotoFilename = null;
            $replacingPhoto = true;
        }

        $data['photo_path'] = $newPhotoFilename;

        try {
            updateCitizen($id, $data);
        } catch (Throwable $e) {
            // A newly uploaded replacement is already on disk; if the
            // update failed, remove it rather than leave it orphaned.
            // The pre-existing photo (if any) was never touched, so
            // there's nothing to restore on the "old" side.
            if ($replacingPhoto && $newPhotoFilename !== null) {
                deleteUploadedImage($newPhotoFilename, 'citizens');
            }
            throw $e;
        }

        // Only remove the previous file once the update has committed —
        // never delete before we know the new state was saved.
        if ($replacingPhoto && !empty($oldPhoto)) {
            deleteUploadedImage($oldPhoto, 'citizens');
        }

        logActivity($_SESSION['user_id'], 'UPDATE_CITIZEN', 'citizens', "Updated {$data['first_name']} {$data['last_name']}.");
        setFlash('success', 'Citizen updated successfully.');
        redirect('citizens/view.php?id=' . $id);
    }
}

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard' => BASE_URL . 'dashboard/index.php',
    'Citizens'  => BASE_URL . 'citizens/index.php',
    'Edit'      => null,
]); ?>

<h1 class="h4 mb-3">Edit Citizen</h1>

<form method="post" enctype="multipart/form-data" novalidate id="citizenForm">
    <?= csrfField() ?>
    <?php require ROOT_PATH . '/citizens/_form_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Changes</button>
        <a href="<?= BASE_URL ?>citizens/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'citizens/citizens.js';
require ROOT_PATH . '/layouts/app.php';
