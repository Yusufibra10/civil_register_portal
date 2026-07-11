<?php
/**
 * POST-only action handler — records that the citizen physically picked
 * up their printed card. Deliberately separate from the application
 * workflow: a card can sit printed and "ready for collection" for weeks
 * before anyone actually comes to collect it, and that real-world delay
 * shouldn't be conflated with the application's own status.
 */
require_once __DIR__ . '/../config/config.php';
$allowedRoles = ['Admin', 'Officer'];
require_once __DIR__ . '/../middleware/role.php';
require_once ROOT_PATH . '/helpers/national_ids_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('national_id/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('national_id/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$nationalId = $id > 0 ? findNationalIdById($id) : null;

if ($nationalId === null || empty($nationalId['card_printed_at']) || !empty($nationalId['collected_at'])) {
    setFlash('danger', 'That National ID cannot be marked as collected right now.');
    redirect('national_id/index.php');
}

markNationalIdCollected($id);
logActivity($_SESSION['user_id'], 'COLLECT_NATIONAL_ID', 'national_id', "Marked {$nationalId['id_number']} as collected.");
setFlash('success', 'National ID marked as collected.');

redirect('national_id/view.php?id=' . $id);
