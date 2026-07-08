<?php
/**
 * The only way a previously generated CSV can be fetched — never a
 * direct static link, since storage/reports/ sits outside the web root's
 * reach entirely (see storage/.htaccess). This script is the gate:
 * confirms the request is authenticated and permitted, looks up the
 * report row, and only then streams the file from disk.
 */
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'reports.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/reports_repository.php';

$id = (int) ($_GET['id'] ?? 0);
$report = $id > 0 ? findReportById($id) : null;

if ($report === null || empty($report['file_path'])) {
    setFlash('danger', 'That report file was not found.');
    redirect('reports/index.php');
}

// basename() defends against the stored value ever being used to escape
// storage/reports/, even though it is never attacker-controlled — the
// same defense-in-depth pattern used for uploaded photos since Phase 5A.
$filePath = ROOT_PATH . '/storage/reports/' . basename($report['file_path']);

if (!is_file($filePath)) {
    setFlash('danger', 'That report file is no longer available.');
    redirect('reports/index.php');
}

logActivity(
    $_SESSION['user_id'],
    'DOWNLOAD_REPORT',
    'reports',
    reportTypeLabel($report['report_type']) . ' re-downloaded.'
);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
