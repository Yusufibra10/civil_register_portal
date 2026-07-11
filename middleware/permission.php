<?php
/**
 * Generic permission gate — the Phase 6 counterpart to middleware/role.php.
 * Set $requiredPermission before requiring this file:
 *
 *   $requiredPermission = 'users.manage';
 *   require __DIR__ . '/../middleware/permission.php';
 *
 * Why this coexists with role.php rather than replacing it: Phase 5's
 * modules (Citizens, Birth Certificates, National ID, Applications) gate
 * on role name via $allowedRoles, and stay that way in this phase per
 * the brief's "don't modify Phase 5 unless a bug fix requires it." This
 * file is what Phase 6's own new modules (Users, Roles, Settings,
 * Reports) use instead, since those are exactly the modules the new
 * role_permissions data model was built to describe. Both mechanisms
 * read the same underlying role — hasRole() checks its name directly,
 * hasPermission() checks what it's been granted — so they never disagree
 * about who's logged in, only about how a page expresses its own rule.
 */
require_once __DIR__ . '/auth.php';

if (!hasPermission($requiredPermission ?? '')) {
    setFlash('danger', 'You do not have permission to access that page.');
    logActivity(
        $_SESSION['user_id'] ?? null,
        'ACCESS_DENIED',
        'permission_middleware',
        'Attempted to access a page without the required permission (' . ($requiredPermission ?? 'unknown') . ').'
    );
    redirect('dashboard/index.php');
}
