<?php
/**
 * POST-only action handler — soft-deletes a citizen. No view of its own;
 * always redirects back to where the request came from.
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
    redirect('citizens/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$citizen = $id > 0 ? findCitizenById($id) : null;

if ($citizen === null) {
    setFlash('danger', 'That citizen record was not found, or is already deleted.');
    redirect('citizens/index.php');
}

softDeleteCitizen($id);
logActivity($_SESSION['user_id'], 'DELETE_CITIZEN', 'citizens', "Deleted {$citizen['first_name']} {$citizen['last_name']} (soft delete).");
setFlash('success', 'Citizen moved to Deleted Citizens.');

redirect('citizens/index.php');
