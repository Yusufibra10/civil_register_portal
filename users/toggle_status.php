<?php
/**
 * POST-only action handler — flips a user between active and inactive.
 * No view of its own. Cannot be used on your own account, and cannot
 * touch a 'suspended' account (that's a deliberate edit.php-only action).
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
    setFlash('danger', 'You cannot change your own status.');
    redirect('users/view.php?id=' . $id);
}

$user = $id > 0 ? findUserById($id) : null;

if ($user === null) {
    setFlash('danger', 'That user was not found.');
    redirect('users/index.php');
}

$newStatus = toggleUserStatus($id);

if ($newStatus === null) {
    setFlash('danger', 'A suspended account must be reactivated from the edit page.');
    redirect('users/view.php?id=' . $id);
}

logActivity($_SESSION['user_id'], 'TOGGLE_USER_STATUS', 'users', "Set {$user['full_name']} to {$newStatus}.");
setFlash('success', "User is now {$newStatus}.");

redirect('users/view.php?id=' . $id);
