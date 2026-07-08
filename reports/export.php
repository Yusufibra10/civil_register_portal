<?php
/**
 * Handles both export formats from one entry point:
 *
 *   format=csv — builds a real file under storage/reports/ (outside the
 *                web root's reach, see storage/.htaccess), records it in
 *                the `reports` table, and streams it as a download. It
 *                can be re-downloaded later from the "Recent Exports"
 *                list via reports/download.php.
 *
 *   format=pdf — renders the same data through layouts/print.php (the
 *                shell built in Phase 5B for certificates and ID cards).
 *                There is no PDF file on the server at all — the
 *                visitor's browser produces the actual PDF via its own
 *                print-to-PDF, the same as printing any other document
 *                in this system. Nothing is stored, so nothing is listed
 *                under "Recent Exports" for this run.
 */
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'reports.view';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/reports_repository.php';

$type = in_array($_GET['type'] ?? '', REPORT_TYPES, true) ? $_GET['type'] : null;
$format = in_array($_GET['format'] ?? '', ['csv', 'pdf'], true) ? $_GET['format'] : null;

if ($type === null || $format === null) {
    setFlash('danger', 'Invalid report request.');
    redirect('reports/index.php');
}

$period = in_array($_GET['period'] ?? '', ['this_month', 'last_month', 'this_year', 'last_year', 'custom'], true)
    ? $_GET['period'] : 'this_month';
$range = resolveReportPeriod($period, (string) ($_GET['date_from'] ?? ''), (string) ($_GET['date_to'] ?? ''));

$extraFilters = [
    'gender' => in_array($_GET['gender'] ?? '', ['Male', 'Female'], true) ? $_GET['gender'] : '',
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$queryFilters = array_merge($range, $extraFilters);

$report = match ($type) {
    'citizens'           => getCitizensReport($queryFilters),
    'birth_certificates' => getBirthCertificatesReport($queryFilters),
    'national_ids'       => getNationalIdsReport($queryFilters),
    'applications'       => getApplicationsReport($queryFilters),
};

$columns = match ($type) {
    'citizens' => [
        'citizen_uid' => 'Citizen ID', 'first_name' => 'First Name', 'last_name' => 'Last Name',
        'gender' => 'Gender', 'date_of_birth' => 'Date of Birth', 'region' => 'Region',
        'district' => 'District', 'status' => 'Status', 'registration_date' => 'Registration Date',
    ],
    'birth_certificates' => [
        'certificate_number' => 'Certificate Number', 'citizen_uid' => 'Citizen ID',
        'first_name' => 'First Name', 'last_name' => 'Last Name', 'gender' => 'Gender',
        'registration_date' => 'Registration Date', 'status' => 'Status',
    ],
    'national_ids' => [
        'id_number' => 'ID Number', 'citizen_uid' => 'Citizen ID', 'first_name' => 'First Name',
        'last_name' => 'Last Name', 'issue_date' => 'Issue Date', 'expiry_date' => 'Expiry Date',
        'card_printed_at' => 'Printed At', 'collected_at' => 'Collected At', 'status' => 'Status',
    ],
    'applications' => [
        'application_number' => 'Application Number', 'citizen_uid' => 'Citizen ID',
        'first_name' => 'First Name', 'last_name' => 'Last Name',
        'applied_date' => 'Applied Date', 'status_label' => 'Status',
    ],
};

if ($format === 'csv') {
    $filename = $type . '_' . date('Ymd_His') . '.csv';
    $filePath = ROOT_PATH . '/storage/reports/' . $filename;

    $handle = fopen($filePath, 'w');
    fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads non-ASCII names correctly
    fputcsv($handle, array_values($columns));
    foreach ($report['rows'] as $row) {
        $line = [];
        foreach (array_keys($columns) as $col) {
            $line[] = $row[$col] ?? '';
        }
        fputcsv($handle, $line);
    }
    fclose($handle);

    recordReportGeneration($type, $range['date_from'], $range['date_to'], $extraFilters, $filename, (int) $_SESSION['user_id']);
    logActivity(
        $_SESSION['user_id'],
        'EXPORT_REPORT',
        'reports',
        reportTypeLabel($type) . ' exported as CSV (' . count($report['rows']) . ' rows).'
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// format === 'pdf'
recordReportGeneration($type, $range['date_from'], $range['date_to'], $extraFilters, null, (int) $_SESSION['user_id']);
logActivity(
    $_SESSION['user_id'],
    'EXPORT_REPORT',
    'reports',
    reportTypeLabel($type) . ' exported as PDF (' . count($report['rows']) . ' rows).'
);

$pageTitle = reportTypeLabel($type);

ob_start();
?>
<h1 class="h4 mb-1"><?= e(reportTypeLabel($type)) ?></h1>
<p class="text-muted small mb-4"><?= formatDate($range['date_from']) ?> &ndash; <?= formatDate($range['date_to']) ?> &middot; <?= count($report['rows']) ?> record(s)</p>
<table class="table table-sm table-bordered">
    <thead>
        <tr><?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
        <?php foreach ($report['rows'] as $row): ?>
        <tr>
            <?php foreach (array_keys($columns) as $col): ?>
                <td><?= e((string) ($row[$col] ?? '')) ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/print.php';
