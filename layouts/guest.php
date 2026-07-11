<?php
/**
 * Unauthenticated page shell (login screen). No sidebar/navbar — same
 * $pageTitle / $content contract as layouts/app.php.
 */
require_once ROOT_PATH . '/includes/header.php';
?>
<div class="guest-layout min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
    <div class="w-100" style="max-width: 420px;">
        <?php require ROOT_PATH . '/components/alert.php'; ?>
        <?= $content ?>
    </div>
</div>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
