<?php
/**
 * Print-friendly page shell — deliberately not layouts/app.php: no
 * sidebar, navbar, or dashboard scripts, since a printed certificate or
 * ID card should never carry portal navigation onto paper.
 *
 * Same $pageTitle / $content contract as the other layouts. Used by
 * birth/print.php and national_id/print.php.
 *
 * The on-screen Print/Back bar is wrapped in .no-print, which
 * assets/css/print.css hides specifically under @media print — so what
 * actually prints is only $content.
 *
 * Optionally set $backUrl to the page the Back button should return to
 * (e.g. BASE_URL . 'national_id/index.php'). Without it, Back falls back
 * to browser history — less predictable if the print page was opened
 * directly or in a new tab, which is why both current callers set it.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/print.css') ?>" rel="stylesheet">
</head>
<body class="print-body">
    <div class="no-print d-flex justify-content-center gap-2 py-3 border-bottom bg-light">
        <button type="button" class="btn btn-primary" id="printPageButton"><i class="fa-solid fa-print me-1"></i>Print</button>
        <a href="<?= isset($backUrl) ? e($backUrl) : '#' ?>" class="btn btn-outline-secondary" id="printBackLink">Back</a>
    </div>
    <div class="print-sheet mx-auto">
        <?= $content ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
