<?php
/**
 * Generic role gate. Set $allowedRoles before requiring this file:
 *
 *   $allowedRoles = ['Admin', 'Officer'];
 *   require __DIR__ . '/../middleware/role.php';
 *
 * Runs auth.php first, so an anonymous visitor is sent to the login page
 * (not shown an "access denied" message that would confirm the page exists).
 */
require_once __DIR__ . '/auth.php';

if (!hasAnyRole($allowedRoles ?? [])) {
    setFlash('danger', 'You do not have permission to access that page.');
    logActivity(
        $_SESSION['user_id'] ?? null,
        'ACCESS_DENIED',
        'role_middleware',
        'Attempted to access a page without the required role.'
    );
    redirect('dashboard/index.php');
}
