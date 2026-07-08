<?php
/**
 * Data-access layer for the citizens table. Every SQL statement the
 * Citizens module runs lives here — citizens/*.php pages call these
 * functions and never write SQL themselves. Mirrors the precedent set by
 * helpers/dashboard_stats.php in Phase 4.
 */

/** Form-writable citizen columns, in a single place so create/update can't drift apart. */
const CITIZEN_WRITABLE_FIELDS = [
    'national_id_number', 'first_name', 'middle_name', 'last_name', 'gender',
    'date_of_birth', 'place_of_birth', 'marital_status', 'nationality', 'occupation',
    'phone', 'email', 'address', 'region', 'district', 'village', 'status', 'registration_date',
];

/**
 * Validation rules shared by both the create and edit forms — one
 * definition, so the two pages can never quietly drift apart.
 */
function citizenValidationRules(): array
{
    return [
        'first_name'         => 'required|alpha_spaces|max:100',
        'middle_name'        => 'alpha_spaces|max:100',
        'last_name'          => 'required|alpha_spaces|max:100',
        'gender'             => 'required|in:Male,Female',
        'date_of_birth'      => 'required|date|not_future|age_max:120',
        'place_of_birth'     => 'required|max:150',
        'marital_status'     => 'in:Single,Married,Divorced,Widowed',
        'nationality'        => 'required|max:100',
        'occupation'         => 'max:100',
        'phone'              => 'phone|max:20',
        'email'              => 'email|max:150',
        'national_id_number' => 'alpha_num_dash|max:20',
        'address'            => 'max:255',
        'region'             => 'max:100',
        'district'           => 'max:100',
        'village'            => 'max:100',
        'status'             => 'required|in:active,inactive',
        'registration_date'  => 'required|date|not_future',
    ];
}

/**
 * Builds the exact $data array createCitizen()/updateCitizen() expect,
 * from raw (already sanitized) form input: restricts to the known
 * writable columns, in the right order, and — critically — turns a blank
 * optional field into NULL rather than an empty string.
 *
 * That last part isn't cosmetic: national_id_number and email are UNIQUE
 * columns. MySQL's UNIQUE constraint allows any number of NULLs but only
 * one row with any given non-NULL value — including ''. Without this
 * conversion, the second citizen saved with no national ID would collide
 * with the first on an empty string and fail to save at all.
 */
function normalizeCitizenData(array $input): array
{
    $nullableIfBlank = ['national_id_number', 'middle_name', 'marital_status', 'occupation',
        'phone', 'email', 'address', 'region', 'district', 'village'];

    $data = [];
    foreach (CITIZEN_WRITABLE_FIELDS as $field) {
        $value = trim((string) ($input[$field] ?? ''));
        $data[$field] = ($value === '' && in_array($field, $nullableIfBlank, true)) ? null : $value;
    }

    return $data;
}

/** True if another (non-deleted) citizen already has this national ID number. */
function citizenNationalIdExists(string $nationalId, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM citizens WHERE national_id_number = :national_id_number AND deleted_at IS NULL';
    $params = ['national_id_number' => $nationalId];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/** True if another (non-deleted) citizen already has this email. */
function citizenEmailExists(string $email, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM citizens WHERE email = :email AND deleted_at IS NULL';
    $params = ['email' => $email];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/**
 * Inserts a new citizen inside a transaction, retrying with a freshly
 * generated reference number if — and only if — the collision was on
 * citizen_uid specifically (retryOnDuplicateKey() below). Any other
 * integrity violation (a duplicate national ID or email that slipped
 * past the pre-check under a race) propagates as a real error instead
 * of being silently retried.
 */
function createCitizen(array $data, ?string $photoFilename, int $createdBy): int
{
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $newId = retryOnDuplicateKey(function () use ($pdo, $data, $photoFilename, $createdBy) {
            $columns = array_merge(['citizen_uid', 'photo_path', 'created_by'], CITIZEN_WRITABLE_FIELDS);
            $placeholders = implode(', ', array_map(fn ($c) => ":{$c}", $columns));

            $stmt = $pdo->prepare(
                'INSERT INTO citizens (' . implode(', ', $columns) . ')
                 VALUES (' . $placeholders . ')'
            );

            $stmt->execute(array_merge($data, [
                'citizen_uid' => generateReferenceNumber('CIT'),
                'photo_path'  => $photoFilename,
                'created_by'  => $createdBy,
            ]));

            return (int) $pdo->lastInsertId();
        }, 'uq_citizens_uid');

        $pdo->commit();

        return $newId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Updates an existing, non-deleted citizen. $data must contain every key
 * in CITIZEN_WRITABLE_FIELDS plus 'photo_path' (the caller resolves
 * whether that's a newly uploaded filename or the citizen's existing one).
 */
function updateCitizen(int $id, array $data): bool
{
    $pdo = getDB();

    $columns = array_merge(CITIZEN_WRITABLE_FIELDS, ['photo_path']);
    $assignments = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", $columns));

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "UPDATE citizens SET {$assignments} WHERE id = :id AND deleted_at IS NULL"
        );

        $stmt->execute(array_merge($data, ['id' => $id]));
        $pdo->commit();

        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Soft-delete: hides the record from normal views without losing data. */
function softDeleteCitizen(int $id): bool
{
    $stmt = getDB()->prepare('UPDATE citizens SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

/** Reverses a soft delete. */
function restoreCitizen(int $id): bool
{
    $stmt = getDB()->prepare('UPDATE citizens SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

/** Fetches one citizen, with the registering user's name joined in. */
function findCitizenById(int $id, bool $includeDeleted = false): ?array
{
    $sql = 'SELECT c.*, u.full_name AS registered_by_name
            FROM citizens c
            LEFT JOIN users u ON u.id = c.created_by
            WHERE c.id = :id';

    if (!$includeDeleted) {
        $sql .= ' AND c.deleted_at IS NULL';
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

/**
 * Search + filter + paginate. Returns ['rows' => [...], 'total' => int].
 *
 * $filters supports: search, gender, status, date_from, date_to, deleted (bool).
 * Every clause is built as parameterized SQL — nothing from $filters is
 * ever concatenated into the query string itself.
 */
function getCitizensList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    $where[] = !empty($filters['deleted']) ? 'c.deleted_at IS NOT NULL' : 'c.deleted_at IS NULL';

    if (!empty($filters['search'])) {
        // The same bound value is needed at six positions. Under PDO's
        // real (non-emulated) prepares, a single named placeholder cannot
        // be reused more than once in one statement, so each occurrence
        // gets its own name, all bound to the identical search term.
        $where[] = '(c.citizen_uid LIKE :search1 OR c.national_id_number LIKE :search2
                     OR CONCAT(c.first_name, " ", c.last_name) LIKE :search3
                     OR c.phone LIKE :search4 OR c.region LIKE :search5 OR c.district LIKE :search6)';
        $term = '%' . $filters['search'] . '%';
        foreach (range(1, 6) as $n) {
            $params["search{$n}"] = $term;
        }
    }

    if (!empty($filters['gender'])) {
        $where[] = 'c.gender = :gender';
        $params['gender'] = $filters['gender'];
    }

    if (!empty($filters['status'])) {
        $where[] = 'c.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'c.registration_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'c.registration_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = getDB()->prepare("SELECT COUNT(*) FROM citizens c {$whereSql}");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT c.id, c.citizen_uid, c.national_id_number, c.first_name, c.middle_name, c.last_name,
                c.gender, c.phone, c.region, c.district, c.status, c.registration_date,
                c.photo_path, c.deleted_at
         FROM citizens c
         {$whereSql}
         ORDER BY c.created_at DESC
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
