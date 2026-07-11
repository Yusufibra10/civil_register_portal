<?php
/**
 * POST-only action handler — soft-deletes a user account. No view of its
 * own. Refuses to let a user delete their own account, which would
 * either lock them out mid-session or (worse) let the last remaining
 * Admin delete themselves with no one left to undo it.
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
    redirect('users/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) $_SESSION['user_id']) {
    setFlash('danger', 'You cannot delete your own account.');
    redirect('users/view.php?id=' . $id);
}

$user = $id > 0 ? findUserById($id) : null;

if ($user === null) {
    setFlash('danger', 'That user was not found, or is already deleted.');
    redirect('users/index.php');
}

softDeleteUser($id);
logActivity($_SESSION['user_id'], 'DELETE_USER', 'users', "Deleted user {$user['full_name']} ({$user['email']}).");
setFlash('success', 'User moved to Deleted Users.');

redirect('users/index.php');
