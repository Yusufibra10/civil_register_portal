<?php
/**
 * Require an unauthenticated session. `require` this at the top of pages
 * that should never be seen while logged in — currently just the login page.
 */
if (isLoggedIn()) {
    redirect('dashboard/index.php');
}
