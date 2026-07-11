<?php
/**
 * Require an authenticated session. `require` this at the very top of any
 * protected page, after config.php, before any output is written.
 */
if (!isLoggedIn()) {
    setFlash('warning', 'Please log in to continue.');
    redirect('auth/login.php');
}
