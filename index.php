<?php
/** Front entry point — routes to the dashboard or the login page. */
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}

redirect('auth/login.php');
