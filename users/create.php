<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'users.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/users_repository.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';
require_once ROOT_PATH . '/helpers/uploads.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'Add User';
$errors = [];
$isCreate = true;
$isSelf = false;
$roles = getRolesList();

$user = array_fill_keys(USER_WRITABLE_FIELDS, '');
$user['status'] = 'active';
$user['profile_photo'] = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('users/create.php');
    }

    $input = sanitizeArray($_POST);
    $user = array_merge($user, array_intersect_key($input, $user));

    $errors = validate($input, userValidationRules(true));
    $data = normalizeUserData($input);

    if (empty($errors['email']) && userEmailExists($data['email'])) {
        $errors['email'][] = 'This email is already in use.';
    }
    if (empty($errors['username']) && userUsernameExists($data['username'])) {
        $errors['username'][] = 'This username is already in use.';
    }
    if (($input['password'] ?? '') !== ($input['password_confirmation'] ?? '')) {
        $errors['password_confirmation'][] = 'Passwords do not match.';
    }

    $photoErrors = validateImageUpload($_FILES['photo'] ?? []);
    if (!empty($photoErrors)) {
        $errors['photo'] = $photoErrors;
    }

    if (empty($errors)) {
        $photoFilename = null;
        if (!empty($_FILES['photo']['name'])) {
            $photoFilename = storeUploadedImage($_FILES['photo'], 'users');
        }

        try {
            $newId = createUser($data, $input['password'], $photoFilename);
        } catch (Throwable $e) {
            if ($photoFilename !== null) {
                deleteUploadedImage($photoFilename, 'users');
            }
            throw $e;
        }

        logActivity($_SESSION['user_id'], 'CREATE_USER', 'users', "Created user {$data['full_name']} ({$data['email']}).");
        setFlash('success', 'User created successfully.');
        redirect('users/view.php?id=' . $newId);
    }
}

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Users' => BASE_URL . 'users/index.php', 'Add User' => null]); ?>

<h1 class="h4 mb-3">Add User</h1>

<form method="post" enctype="multipart/form-data" novalidate id="userForm">
    <?= csrfField() ?>
    <?php require ROOT_PATH . '/users/_form_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save User</button>
        <a href="<?= BASE_URL ?>users/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'users/users.js';
require ROOT_PATH . '/layouts/app.php';
