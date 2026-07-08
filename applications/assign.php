<?php
/**
 * POST-only action handler — assigns or unassigns the processing officer.
 * Independent of a status change: reassigning who's handling an
 * application doesn't itself move the workflow forward.
 */
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/applications_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('applications/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('applications/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0 || findApplicationById($id) === null) {
    setFlash('danger', 'That application was not found.');
    redirect('applications/index.php');
}

$newAssignee = $action === 'assign_me' ? (int) $_SESSION['user_id'] : null;
assignApplicationOfficer($id, $newAssignee);

logActivity(
    $_SESSION['user_id'],
    'ASSIGN_APPLICATION',
    'applications',
    $newAssignee ? 'Assigned application to self.' : 'Unassigned application.'
);
setFlash('success', $newAssignee ? 'Application assigned to you.' : 'Application unassigned.');

redirect('applications/view.php?id=' . $id);
