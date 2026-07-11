<?php
/**
 * Dedicated login page shell — split-screen branding + card design.
 *
 * Deliberately separate from layouts/guest.php (which birth/verify.php
 * and applications/track.php also use): restyling guest.php in place
 * would have changed those two public pages too. This file exists so
 * the login redesign can't touch anything else.
 *
 * Same $pageTitle / $content contract as the other layouts.
 */
require_once ROOT_PATH . '/includes/header.php';
?>
<link href="<?= asset('css/login.css') ?>" rel="stylesheet">

<div class="login-page d-flex flex-column flex-md-row">
    <div class="login-branding d-none d-md-flex flex-md-column justify-content-between col-md-6 col-lg-7">
        <div>
            <?php if ($systemLogo): ?>
                <img src="<?= e(asset('uploads/settings/' . $systemLogo)) ?>" alt="" class="login-branding__logo mb-4">
            <?php else: ?>
                <div class="login-branding__logo d-flex align-items-center justify-content-center mb-4">
                    <i class="fa-solid fa-landmark fs-3"></i>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <p class="login-branding__subtitle mb-2">E-Government System</p>
            <h1 class="login-branding__title mb-3"><?= e($systemName) ?></h1>
            <p class="login-branding__description">
                Access birth registration, national ID services, and civil records through a secure, government-managed portal.
            </p>
        </div>
        <p class="login-branding__footer mb-0">Government of Somaliland</p>
    </div>

    <div class="login-form-side d-flex flex-grow-1 align-items-center justify-content-center p-4">
        <div class="w-100" style="max-width: 440px;">
            <div class="d-flex d-md-none align-items-center gap-2 mb-4 login-mobile-brand">
                <i class="fa-solid fa-landmark fs-4"></i>
                <span class="fw-bold text-uppercase small"><?= e($systemName) ?></span>
            </div>

            <?php require ROOT_PATH . '/components/alert.php'; ?>
            <?= $content ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
<?php if (!empty($pageScript)): ?>
<script src="<?= BASE_URL . ltrim($pageScript, '/') ?>"></script>
<?php endif; ?>
