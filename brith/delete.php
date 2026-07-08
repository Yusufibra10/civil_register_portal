<?php
/**
 * POST-only action handler — revokes (soft-deletes) a birth certificate.
 * No view of its own.
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

if ($certificate === null || $certificate['status'] !== 'active') {
    setFlash('danger', 'That certificate was not found, or is already revoked.');
    redirect('birth/index.php');
}

revokeBirthCertificate($id);
logActivity($_SESSION['user_id'], 'REVOKE_BIRTH_CERTIFICATE', 'birth', "Revoked certificate {$certificate['certificate_number']}.");
setFlash('success', 'Certificate revoked.');

redirect('birth/view.php?id=' . $id);
