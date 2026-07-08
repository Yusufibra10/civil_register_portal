<?php
/** Expects $pageTitle to optionally be set by the calling page before this include. */
$pageTitle = $pageTitle ?? APP_NAME;
$systemName = setting('system_name', APP_NAME);

// Phase 6's "optional" theme setting: recolors the sidebar/avatar/accent
// tokens this project's own CSS defines (see assets/css/style.css). It
// deliberately does not attempt to re-theme Bootstrap's own utility
// classes (.btn-primary, etc.) — verifying that works reliably against
// the CDN build is a bigger claim than "optional" should make.
$themePalettes = [
    'navy'   => ['#123a6b', '#0a2647', '#c79a3f'],
    'forest' => ['#1b4332', '#12301f', '#c79a3f'],
    'maroon' => ['#5c1a1a', '#411010', '#c79a3f'],
    'slate'  => ['#2b3a4a', '#1e2a36', '#c79a3f'],
];
$theme = $themePalettes[setting('theme_color', 'navy')] ?? $themePalettes['navy'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e($systemName) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <style>
        :root {
            --brand-navy: <?= e($theme[0]) ?>;
            --brand-navy-dark: <?= e($theme[1]) ?>;
            --brand-gold: <?= e($theme[2]) ?>;
        }
    </style>
</head>
<body>
