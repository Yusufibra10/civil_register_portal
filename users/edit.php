<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'users.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/users_repository.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';
require_once ROOT_PATH . '/helpers/uploads.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$id = (int) ($_GET['id'] ?? 0);
$existing = $id > 0 ? findUserById($id) : null;

if ($existing === null) {
    setFlash('danger', 'That user was not found, or has been deleted.');
    redirect('users/index.php');
}

$pageTitle = 'Edit User';
$errors = [];
$isCreate = false;
$isSelf = $id === (int) $_SESSION['user_id'];
$roles = getRolesList();

$user = array_intersect_key($existing, array_flip(USER_WRITABLE_FIELDS));
$user['profile_photo'] = $existing['profile_photo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('users/edit.php?id=' . $id);
    }

    $input = sanitizeArray($_POST);
    $user = array_merge($user, array_intersect_key($input, array_flip(USER_WRITABLE_FIELDS)));
    $user['profile_photo'] = $existing['profile_photo'];

    $errors = validate($input, userValidationRules(false));
    $data = normalizeUserData($input);

    // Defense in depth: even if the disabled fields were somehow
    // tampered with client-side, a self-edit can never change its own
    // role or status — that's enforced here, not just in the UI.
    if ($isSelf) {
        $data['role_id'] = (int) $existing['role_id'];
        $data['status'] = $existing['status'];
    }

    if (empty($errors['email']) && userEmailExists($data['email'], $id)) {
        $errors['email'][] = 'This email is already in use.';
    }
    if (empty($errors['username']) && userUsernameExists($data['username'], $id)) {
        $errors['username'][] = 'This username is already in use.';
    }

    $changingPassword = !empty($input['password']);
    if ($changingPassword && $input['password'] !== ($input['password_confirmation'] ?? '')) {
        $errors['password_confirmation'][] = 'Passwords do not match.';
    }

    $photoErrors = validateImageUpload($_FILES['photo'] ?? []);
    if (!empty($photoErrors)) {
        $errors['photo'] = $photoErrors;
    }

    if (empty($errors)) {
        $oldPhoto = $existing['profile_photo'];
        $newPhotoFilename = $oldPhoto;
        $replacingPhoto = false;

        if (!empty($_FILES['photo']['name'])) {
            $newPhotoFilename = storeUploadedImage($_FILES['photo'], 'users');
            $replacingPhoto = true;
        } elseif (!empty($input['remove_photo'])) {
            $newPhotoFilename = null;
            $replacingPhoto = true;
        }

        $data['profile_photo'] = $newPhotoFilename;

        try {
            updateUser($id, $data);
            if ($changingPassword) {
                updateUserPassword($id, $input['password']);
            }
        } catch (Throwable $e) {
            if ($replacingPhoto && $newPhotoFilename !== null) {
                deleteUploadedImage($newPhotoFilename, 'users');
            }
            throw $e;
        }

        if ($replacingPhoto && !empty($oldPhoto)) {
            deleteUploadedImage($oldPhoto, 'users');
        }

        logActivity($_SESSION['user_id'], 'UPDATE_USER', 'users', "Updated user {$data['full_name']} ({$data['email']})." . ($changingPassword ? ' Password changed.' : ''));
        setFlash('success', 'User updated successfully.');
        redirect('users/view.php?id=' . $id);
    }
}

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard' => BASE_URL . 'dashboard/index.php',
    'Users'     => BASE_URL . 'users/index.php',
    'Edit'      => null,
]); ?>

<h1 class="h4 mb-3">Edit User</h1>

<form method="post" enctype="multipart/form-data" novalidate id="userForm">
    <?= csrfField() ?>
    <?php require ROOT_PATH . '/users/_form_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Changes</button>
        <a href="<?= BASE_URL ?>users/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'users/users.js';
require ROOT_PATH . '/layouts/app.php';
