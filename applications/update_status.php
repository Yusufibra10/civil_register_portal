<?php
/**
 * POST-only action handler — moves an application to a new status.
 * No view of its own. This is the one place a transition can actually
 * happen, so it's also the one place an illegal transition (a tampered
 * request submitting a target_status_code the UI never offered for the
 * application's current state) gets caught and refused.
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
$targetCode = trim((string) ($_POST['target_status_code'] ?? ''));
$remarks = trim((string) ($_POST['remarks'] ?? ''));

if ($id <= 0 || $targetCode === '') {
    redirect('applications/index.php');
}

try {
    transitionApplicationStatus($id, $targetCode, $remarks !== '' ? $remarks : null, (int) $_SESSION['user_id']);
} catch (InvalidArgumentException $e) {
    // A transition the workflow graph doesn't allow — including any
    // attempt at a backward jump like "approved" -> "submitted".
    setFlash('danger', $e->getMessage());
    redirect('applications/view.php?id=' . $id);
}

logActivity(
    $_SESSION['user_id'],
    'UPDATE_APPLICATION_STATUS',
    'applications',
    "Moved application to \"{$targetCode}\"." . ($remarks !== '' ? " Notes: {$remarks}" : '')
);
setFlash('success', 'Application status updated.');

redirect('applications/view.php?id=' . $id);
