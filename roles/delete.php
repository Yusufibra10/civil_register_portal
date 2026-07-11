<?php
/**
 * POST-only action handler — deletes a custom role. No view of its own.
 * Refuses outright for any of the three built-in roles (the application
 * hardcodes their names), and refuses for any role that still has users
 * assigned (the FK is RESTRICT anyway — this is just the friendly message).
 */
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'roles.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/roles_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('roles/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('roles/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$role = $id > 0 ? findRoleById($id) : null;

if ($role === null) {
    setFlash('danger', 'That role was not found.');
    redirect('roles/index.php');
}

if (isCoreRole($role['name'])) {
    setFlash('danger', 'Built-in roles cannot be deleted.');
    redirect('roles/index.php');
}

if (roleHasUsers($id)) {
    setFlash('danger', 'This role still has users assigned to it and cannot be deleted.');
    redirect('roles/index.php');
}

deleteRole($id);
logActivity($_SESSION['user_id'], 'DELETE_ROLE', 'roles', "Deleted role {$role['name']}.");
setFlash('success', 'Role deleted successfully.');

redirect('roles/index.php');
