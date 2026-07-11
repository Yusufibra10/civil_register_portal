<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'roles.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$id = (int) ($_GET['id'] ?? 0);
$existing = $id > 0 ? findRoleById($id) : null;

if ($existing === null) {
    setFlash('danger', 'That role was not found.');
    redirect('roles/index.php');
}

$pageTitle = 'Edit Role';
$errors = [];
$isCore = isCoreRole($existing['name']);
$role = ['name' => $existing['name'], 'description' => (string) $existing['description']];
$checkedPermissionIds = getRolePermissionIds($id);
$permissionsGrouped = getPermissionsGroupedByModule();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('roles/edit.php?id=' . $id);
    }

    $input = sanitizeArray($_POST);
    $role['description'] = trim($input['description'] ?? '');
    $checkedPermissionIds = array_map('intval', $_POST['permission_ids'] ?? []);

    $errors = validate($input, ['description' => 'max:255']);

    // The name field is only ever editable for a non-core (custom) role —
    // core role names are structurally load-bearing (see CORE_ROLE_NAMES).
    if (!$isCore) {
        $role['name'] = trim($input['name'] ?? '');
        $errors = array_merge($errors, validate($input, ['name' => 'required|alpha_spaces|max:50']));
        if (empty($errors['name']) && roleNameExists($role['name'], $id)) {
            $errors['name'][] = 'A role with this name already exists.';
        }
    }

    if (empty($errors)) {
        $description = $role['description'] !== '' ? $role['description'] : null;

        if ($isCore) {
            updateRoleDescription($id, $description, $checkedPermissionIds);
        } else {
            updateRole($id, $role['name'], $description, $checkedPermissionIds);
        }

        logActivity($_SESSION['user_id'], 'UPDATE_ROLE', 'roles', "Updated role {$role['name']} — " . count($checkedPermissionIds) . ' permission(s) granted.');
        setFlash('success', 'Role updated successfully.');
        redirect('roles/index.php');
    }
}

ob_start();
?>
<?php renderBreadcrumb([
    'Dashboard'            => BASE_URL . 'dashboard/index.php',
    'Roles & Permissions'  => BASE_URL . 'roles/index.php',
    'Edit'                 => null,
]); ?>

<h1 class="h4 mb-3">Edit Role</h1>

<form method="post" novalidate id="roleForm">
    <?= csrfField() ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Role Details</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control <?= fieldInvalidClass($errors, 'name') ?>" value="<?= e($role['name']) ?>" required maxlength="50" <?= $isCore ? 'disabled' : '' ?>>
                    <?php if ($isCore): ?>
                        <input type="hidden" name="name" value="<?= e($role['name']) ?>">
                        <div class="form-text">Built-in role names cannot be changed — the application relies on them.</div>
                    <?php endif; ?>
                    <?= fieldErrorHtml($errors, 'name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control <?= fieldInvalidClass($errors, 'description') ?>" value="<?= e($role['description']) ?>" maxlength="255">
                    <?= fieldErrorHtml($errors, 'description') ?>
                </div>
            </div>
        </div>
    </div>
    <?php require ROOT_PATH . '/roles/_permissions_fields.php'; ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Changes</button>
        <a href="<?= BASE_URL ?>roles/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
$pageScript = 'roles/roles.js';
require ROOT_PATH . '/layouts/app.php';
