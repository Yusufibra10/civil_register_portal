<?php
/**
 * POST-only action handler — reverses a revoke. No view of its own.
 */
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/birth_certificates_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('birth/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('birth/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$certificate = $id > 0 ? findBirthCertificateById($id) : null;

if ($certificate === null || $certificate['status'] !== 'revoked') {
    setFlash('danger', 'That certificate was not found, or is not revoked.');
    redirect('birth/index.php');
}

restoreBirthCertificate($id);
logActivity($_SESSION['user_id'], 'RESTORE_BIRTH_CERTIFICATE', 'birth', "Restored certificate {$certificate['certificate_number']}.");
setFlash('success', 'Certificate restored.');

redirect('birth/view.php?id=' . $id);
