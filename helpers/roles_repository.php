<?php
/**
 * Data-access layer for roles, permissions, and role_permissions.
 *
 * CORE_ROLE_NAMES matters more than it looks: Admin/Officer/Viewer are
 * not just seed data — every $allowedRoles = ['Admin', 'Officer'] check
 * across Phases 4 and 5 hardcodes these exact strings. If one were
 * renamed or deleted through this module, every hasRole()/hasAnyRole()
 * check built on it would silently stop matching. Their *permission
 * grants* are fully editable (that's the point of this module); their
 * *names* are not.
 */
const CORE_ROLE_NAMES = ['Admin', 'Officer', 'Viewer'];

function isCoreRole(string $roleName): bool
{
    return in_array($roleName, CORE_ROLE_NAMES, true);
}

function roleNameExists(string $name, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM roles WHERE name = :name';
    $params = ['name' => $name];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/** Every permission, grouped by module, in a stable order — the shape the create/edit checkbox UI is built around. */
function getPermissionsGroupedByModule(): array
{
    $rows = getDB()->query('SELECT id, name, module, description FROM permissions ORDER BY module, name')->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['module']][] = $row;
    }

    return $grouped;
}

/** IDs of the permissions currently granted to a role — pre-checks the checkboxes on edit.php. */
function getRolePermissionIds(int $roleId): array
{
    $stmt = getDB()->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :role_id');
    $stmt->execute(['role_id' => $roleId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Replaces a role's entire permission set with $permissionIds in one
 * transaction — simpler and less error-prone than diffing which boxes
 * were checked/unchecked, and the table has no history worth preserving
 * row-by-row (unlike id_application_status_history, a grant has no
 * meaningful "reason" to log per-change beyond the one activity_logs
 * entry the calling page already writes).
 */
function syncRolePermissions(int $roleId, array $permissionIds): void
{
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);

        if (!empty($permissionIds)) {
            $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
            foreach ($permissionIds as $permissionId) {
                $stmt->execute(['role_id' => $roleId, 'permission_id' => (int) $permissionId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function createRole(string $name, ?string $description, array $permissionIds): int
{
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('INSERT INTO roles (name, description) VALUES (:name, :description)');
        $stmt->execute(['name' => $name, 'description' => $description]);
        $newId = (int) $pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    syncRolePermissions($newId, $permissionIds);

    return $newId;
}

/** $name is only ever applied for a non-core role — callers must not offer the name field for core roles in the first place. */
function updateRole(int $id, string $name, ?string $description, array $permissionIds): bool
{
    $stmt = getDB()->prepare('UPDATE roles SET name = :name, description = :description WHERE id = :id');
    $stmt->execute(['name' => $name, 'description' => $description, 'id' => $id]);

    syncRolePermissions($id, $permissionIds);

    return true;
}

/** Description-only update, for the core roles whose name must never change. */
function updateRoleDescription(int $id, ?string $description, array $permissionIds): bool
{
    $stmt = getDB()->prepare('UPDATE roles SET description = :description WHERE id = :id');
    $stmt->execute(['description' => $description, 'id' => $id]);

    syncRolePermissions($id, $permissionIds);

    return true;
}

/** True if any (non-deleted or deleted) user still references this role — the FK is RESTRICT, this is just the friendly pre-check. */
function roleHasUsers(int $roleId): bool
{
    $stmt = getDB()->prepare('SELECT id FROM users WHERE role_id = :role_id LIMIT 1');
    $stmt->execute(['role_id' => $roleId]);

    return (bool) $stmt->fetchColumn();
}

function deleteRole(int $id): bool
{
    $stmt = getDB()->prepare('DELETE FROM roles WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

function findRoleById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM roles WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

/** All roles with their user count and permission count, for the list page. */
function getRolesList(): array
{
    return getDB()->query(
        'SELECT r.id, r.name, r.description,
                (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count,
                (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
         FROM roles r
         ORDER BY r.id'
    )->fetchAll();
}
