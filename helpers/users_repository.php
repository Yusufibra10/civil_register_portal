<?php
/**
 * Data-access layer for the users table. Every SQL statement the User
 * Management module runs lives here, following the precedent set by
 * helpers/citizens_repository.php in Phase 5A.
 */

/** Form-writable columns. password and profile_photo are handled by their own functions below, never generically. */
const USER_WRITABLE_FIELDS = ['role_id', 'full_name', 'email', 'username', 'phone', 'status'];

function userValidationRules(bool $requirePassword): array
{
    $rules = [
        'full_name' => 'required|alpha_spaces|max:150',
        'email'     => 'required|email|max:150',
        'username'  => 'required|alpha_num_dash|max:50',
        'phone'     => 'phone|max:20',
        'role_id'   => 'required|numeric',
        'status'    => 'required|in:active,inactive,suspended',
    ];

    $rules['password'] = $requirePassword ? 'required|min:8|max:100' : 'min:8|max:100';

    return $rules;
}

/** Builds the exact $data array createUser()/updateUser() expect, from raw sanitized input. */
function normalizeUserData(array $input): array
{
    $data = [];
    foreach (USER_WRITABLE_FIELDS as $field) {
        $data[$field] = trim((string) ($input[$field] ?? ''));
    }
    $data['role_id'] = (int) $data['role_id'];
    $data['phone'] = $data['phone'] === '' ? null : $data['phone'];

    return $data;
}

function userEmailExists(string $email, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM users WHERE email = :email AND deleted_at IS NULL';
    $params = ['email' => $email];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

function userUsernameExists(string $username, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM users WHERE username = :username AND deleted_at IS NULL';
    $params = ['username' => $username];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/** Inserts a new user with a bcrypt-hashed password. Usernames/emails are pre-checked by the caller; the UNIQUE keys are the backstop. */
function createUser(array $data, string $plainPassword, ?string $photoFilename): int
{
    $stmt = getDB()->prepare(
        'INSERT INTO users (role_id, full_name, email, username, password, phone, profile_photo, status)
         VALUES (:role_id, :full_name, :email, :username, :password, :phone, :profile_photo, :status)'
    );
    $stmt->execute(array_merge($data, [
        'password'      => password_hash($plainPassword, PASSWORD_BCRYPT),
        'profile_photo' => $photoFilename,
    ]));

    return (int) getDB()->lastInsertId();
}

/** Updates profile fields (not password) for a non-deleted user. $data must include 'profile_photo'. */
function updateUser(int $id, array $data): bool
{
    $stmt = getDB()->prepare(
        'UPDATE users SET
            role_id = :role_id, full_name = :full_name, email = :email, username = :username,
            phone = :phone, status = :status, profile_photo = :profile_photo
         WHERE id = :id AND deleted_at IS NULL'
    );
    $stmt->execute(array_merge($data, ['id' => $id]));

    return $stmt->rowCount() > 0;
}

/** Separate from updateUser() since a password change is optional on every edit — most saves don't touch it. */
function updateUserPassword(int $id, string $plainPassword): bool
{
    $stmt = getDB()->prepare('UPDATE users SET password = :password WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['password' => password_hash($plainPassword, PASSWORD_BCRYPT), 'id' => $id]);

    return $stmt->rowCount() > 0;
}

function softDeleteUser(int $id): bool
{
    $stmt = getDB()->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

function restoreUser(int $id): bool
{
    $stmt = getDB()->prepare('UPDATE users SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

/**
 * Flips 'active' <-> 'inactive' only. A 'suspended' account is a harder,
 * more deliberate block and is only reachable through edit.php's status
 * field — this toggle deliberately won't touch it, so a quick click from
 * the list page can never accidentally lift a suspension.
 */
function toggleUserStatus(int $id): ?string
{
    $user = findUserById($id);
    if ($user === null || !in_array($user['status'], ['active', 'inactive'], true)) {
        return null;
    }

    $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
    getDB()->prepare('UPDATE users SET status = :status WHERE id = :id')
        ->execute(['status' => $newStatus, 'id' => $id]);

    return $newStatus;
}

/** Recent activity_logs rows for one user — used on their profile page. */
function getUserActivityLogs(int $userId, int $limit = 10): array
{
    $stmt = getDB()->prepare(
        'SELECT action, module, description, created_at
         FROM activity_logs
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/** Fetches one user, with their role name joined in. */
function findUserById(int $id, bool $includeDeleted = false): ?array
{
    $sql = 'SELECT u.id, u.role_id, u.full_name, u.email, u.username, u.phone, u.profile_photo,
                   u.status, u.last_login_at, u.created_at, u.updated_at, u.deleted_at,
                   r.name AS role_name
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id';

    if (!$includeDeleted) {
        $sql .= ' AND u.deleted_at IS NULL';
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

/** Search + filter + paginate. Returns ['rows' => [...], 'total' => int]. */
function getUsersList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    $where[] = !empty($filters['deleted']) ? 'u.deleted_at IS NOT NULL' : 'u.deleted_at IS NULL';

    if (!empty($filters['search'])) {
        $where[] = '(u.full_name LIKE :search1 OR u.email LIKE :search2 OR u.username LIKE :search3)';
        $term = '%' . $filters['search'] . '%';
        $params['search1'] = $term;
        $params['search2'] = $term;
        $params['search3'] = $term;
    }

    if (!empty($filters['role_id'])) {
        $where[] = 'u.role_id = :role_id';
        $params['role_id'] = $filters['role_id'];
    }

    if (!empty($filters['status'])) {
        $where[] = 'u.status = :status';
        $params['status'] = $filters['status'];
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = getDB()->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT u.id, u.full_name, u.email, u.username, u.phone, u.status, u.profile_photo,
                u.last_login_at, u.created_at, u.deleted_at, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         {$whereSql}
         ORDER BY u.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}
