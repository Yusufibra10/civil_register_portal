<?php
/**
 * POST-only action handler — reverses a soft delete. No view of its own.
 */
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/citizens_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('citizens/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('citizens/index.php?deleted=1');
}

$id = (int) ($_POST['id'] ?? 0);
$citizen = $id > 0 ? findCitizenById($id, true) : null;

if ($citizen === null || $citizen['deleted_at'] === null) {
    setFlash('danger', 'That citizen record was not found, or is not deleted.');
    redirect('citizens/index.php?deleted=1');
}

restoreCitizen($id);
logActivity($_SESSION['user_id'], 'RESTORE_CITIZEN', 'citizens', "Restored {$citizen['first_name']} {$citizen['last_name']}.");
setFlash('success', 'Citizen restored successfully.');

redirect('citizens/view.php?id=' . $id);
