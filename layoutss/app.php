<?php
/**
 * Authenticated page shell.
 *
 * The calling script must, before requiring this file:
 *   1. require config/config.php and middleware/auth.php
 *   2. set $pageTitle
 *   3. ob_start(), print its own page markup, then $content = ob_get_clean();
 *
 * Optionally set $pageScript to a path relative to BASE_URL (e.g.
 * 'dashboard/dashboard.js') to load one extra script after app.js —
 * for page-specific behavior, such as this page's Chart.js setup.
 *
 * This is what makes header/footer/navbar/sidebar reusable across every
 * module without copy-pasting them into every page.
 */
require_once ROOT_PATH . '/includes/header.php';
?>
<div class="d-flex">
    <?php require ROOT_PATH . '/includes/sidebar.php'; ?>
    <div class="flex-grow-1 main-content">
        <?php require ROOT_PATH . '/includes/navbar.php'; ?>
        <main class="p-4">
            <?php require ROOT_PATH . '/components/alert.php'; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
<?php if (!empty($pageScript)): ?>
<script src="<?= BASE_URL . ltrim($pageScript, '/') ?>"></script>
<?php endif; ?>
