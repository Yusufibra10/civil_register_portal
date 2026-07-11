<?php
/**
 * POST-only action handler — reverses a soft delete. No view of its own.
 */
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'users.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/users_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('users/index.php?deleted=1');
}

$id = (int) ($_POST['id'] ?? 0);
$user = $id > 0 ? findUserById($id, true) : null;

if ($user === null || $user['deleted_at'] === null) {
    setFlash('danger', 'That user was not found, or is not deleted.');
    redirect('users/index.php?deleted=1');
}

restoreUser($id);
logActivity($_SESSION['user_id'], 'RESTORE_USER', 'users', "Restored user {$user['full_name']} ({$user['email']}).");
setFlash('success', 'User restored successfully.');

redirect('users/view.php?id=' . $id);
