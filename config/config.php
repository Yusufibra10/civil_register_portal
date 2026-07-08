<?php
/**
 * Application bootstrap. Every entry-point script requires this file first
 * and gets: environment-aware error reporting, timezone, app constants,
 * a hardened session with idle-timeout enforcement, remember-me
 * auto-login, and every helper function loaded and ready to call.
 */

// ---------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------
// Auto-detected from the requesting host rather than hand-toggled, so a
// forgotten flag can never ship a production box with stack traces and
// DB errors visible to visitors. Only recognized local hosts (or the CLI,
// e.g. database/seed.php) get 'development' — everything else, including
// any host we don't recognize, fails safe to 'production'.
$__localHosts = ['localhost', '127.0.0.1', '::1'];
$__requestHost = strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
$__isLocalEnv = PHP_SAPI === 'cli' || in_array($__requestHost, $__localHosts, true);
define('ENVIRONMENT', $__isLocalEnv ? 'development' : 'production'); // 'development' | 'production'
unset($__localHosts, $__requestHost, $__isLocalEnv);

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/app.log');
}

/**
 * Global safety net. Any exception that escapes a try/catch (or a fatal
 * type error) is logged, never shown to the visitor in production, and
 * never leaks a stack trace, file path, or query into the response.
 */
set_exception_handler(function (Throwable $e): void {
    error_log(sprintf('Uncaught %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    if (ENVIRONMENT === 'development') {
        throw $e;
    }

    http_response_code(500);
    echo 'A system error occurred. Please try again later.';
    exit;
});

/**
 * Belt-and-braces net for errors set_exception_handler() never sees:
 * E_ERROR/E_PARSE/E_CORE_ERROR/E_COMPILE_ERROR (e.g. a fatal type error
 * or exhausted memory limit) terminate the script directly rather than
 * throwing, so they're only observable here, at shutdown.
 */
register_shutdown_function(function (): void {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if ($error === null || !in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    error_log(sprintf('Fatal error: %s in %s:%d', $error['message'], $error['file'], $error['line']));

    if (ENVIRONMENT === 'production' && !headers_sent()) {
        http_response_code(500);
        echo 'A system error occurred. Please try again later.';
    }
});

// ---------------------------------------------------------------------
// Timezone
// ---------------------------------------------------------------------
date_default_timezone_set('Africa/Mogadishu');

// ---------------------------------------------------------------------
// Application constants
// ---------------------------------------------------------------------
define('APP_NAME', 'Civil Registry Portal');
define('APP_VERSION', '1.0.0');
define('ROOT_PATH', dirname(__DIR__));
define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minutes of inactivity

/**
 * True if the original client request was HTTPS — checked directly for a
 * server terminating its own SSL, or via X-Forwarded-Proto for a reverse
 * proxy / load balancer in front of Apache (common on shared hosting and
 * VPS setups), where $_SERVER['HTTPS'] is never set on the backend even
 * though the visitor's connection was secure.
 */
function isHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

// Adjust the path segment below if the project folder name changes or
// moves to a domain root in production.
$protocol = isHttps() ? 'https://' : 'http://';
define('BASE_URL', $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/civil_register_portal/');

// ---------------------------------------------------------------------
// Dependencies needed before the session/timeout logic below can run
// ---------------------------------------------------------------------
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/helpers/redirect.php';
require_once ROOT_PATH . '/helpers/flash.php';

// ---------------------------------------------------------------------
// Session (hardened cookie params, must be set before session_start).
// Skipped under the CLI SAPI (e.g. database/seed.php) where there is no
// browser session to manage.
// ---------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => isHttps(),
    ]);
    session_start();
}

// ---------------------------------------------------------------------
// Idle session timeout. Checked on every authenticated request, before
// any page logic runs. A stale session is destroyed outright rather than
// merely logged out of, so a shared/public computer can't be resumed.
// ---------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && !empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_destroy();
        session_start();
        setFlash('warning', 'Your session expired due to inactivity. Please log in again.');
        redirect('auth/login.php');
    }
    $_SESSION['last_activity'] = time();
}

// ---------------------------------------------------------------------
// Remaining helpers
// ---------------------------------------------------------------------
require_once ROOT_PATH . '/helpers/functions.php';
require_once ROOT_PATH . '/helpers/sanitize.php';
require_once ROOT_PATH . '/helpers/validation.php';
require_once ROOT_PATH . '/helpers/auth_helper.php';
require_once ROOT_PATH . '/helpers/csrf.php';
require_once ROOT_PATH . '/helpers/remember_me.php';

// Phase 6: settings are read by shared chrome (header, sidebar, login)
// on every single request, not just a "Settings" module page — that
// reach is why setting() is loaded globally rather than per-module.
require_once ROOT_PATH . '/helpers/settings_repository.php';

// ---------------------------------------------------------------------
// Remember-me auto-login: only attempted when there is no active session
// and a remember-me cookie is present.
// ---------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && empty($_SESSION['user_id']) && !empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
    attemptRememberLogin();
}
