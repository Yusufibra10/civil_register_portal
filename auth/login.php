<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/guest.php';

$pageTitle = 'Login';
$systemName = setting('system_name', 'Civil Registry Portal');
$systemLogo = setting('system_logo');

$pageScript = 'assets/js/login.js';

ob_start();
?>
<div class="card login-card shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <div class="mb-4">
            <h1 class="h3 mb-1">Welcome Back</h1>
            <p class="text-muted">Sign in to <?= e($systemName) ?></p>
        </div>
        <form action="<?= BASE_URL ?>auth/auth.php" method="post" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control border-end-0" id="password" name="password" required>
                    <button class="btn btn-outline-secondary login-password-toggle border-start-0" type="button" id="togglePassword" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>
                <a href="#" class="small text-decoration-none">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Login
            </button>
        </form>
        <p class="text-center text-muted small mt-4 mb-0">
            Need an account? Contact your system administrator.
        </p>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/login.php';
