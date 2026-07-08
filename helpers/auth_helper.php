<?php
/**
 * Session-backed authentication helpers. These answer "who is logged in
 * and what can they do" — middleware/*.php use them to decide whether to
 * let a request through.
 */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Fetches (and memoizes for the request) the logged-in user with their role name. */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $stmt = getDB()->prepare(
            'SELECT u.id, u.full_name, u.email, u.role_id, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }

    return $user;
}

/** Case-insensitive role check, e.g. hasRole('Admin'). */
function hasRole(string $roleName): bool
{
    $user = currentUser();
    return $user !== null && strcasecmp($user['role_name'], $roleName) === 0;
}

/** True if the logged-in user holds any of the given roles, e.g. hasAnyRole(['Admin', 'Officer']). */
function hasAnyRole(array $roleNames): bool
{
    foreach ($roleNames as $roleName) {
        if (hasRole($roleName)) {
            return true;
        }
    }
    return false;
}

/**
 * Every permission name granted to the logged-in user's role, via
 * role_permissions (Phase 6). Memoized per request — permission grants
 * don't change mid-request.
 */
function getUserPermissions(): array
{
    $user = currentUser();
    if ($user === null) {
        return [];
    }

    static $permissions = null;

    if ($permissions === null) {
        $stmt = getDB()->prepare(
            'SELECT p.name
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id'
        );
        $stmt->execute(['role_id' => $user['role_id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return $permissions;
}

/**
 * True if the logged-in user's role grants $permissionName, e.g.
 * hasPermission('users.manage'). This is data-driven (role_permissions),
 * unlike hasRole()/hasAnyRole() which check the role's name directly —
 * the two mechanisms coexist deliberately (see middleware/permission.php).
 */
function hasPermission(string $permissionName): bool
{
    return in_array($permissionName, getUserPermissions(), true);
}
