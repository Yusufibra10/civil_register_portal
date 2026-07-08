<?php
/**
 * Data-access layer for birth_certificates. Every SQL statement the Birth
 * Certificate module runs lives here, following the precedent set by
 * helpers/citizens_repository.php in Phase 5A.
 *
 * Schema note: birth_certificates has no deleted_at column — Phase 2
 * modeled its lifecycle as status ENUM('active','revoked') instead, since
 * a certificate is never truly discarded, only invalidated. "Soft delete"
 * for this module means revoke(); "restore" means reactivate().
 */

/** Form-writable columns. certificate_number, citizen_id, issued_by, and status are set by the functions below, never directly from a form. */
const BIRTH_CERTIFICATE_WRITABLE_FIELDS = [
    'registration_date', 'place_of_registration',
    'father_full_name', 'father_national_id',
    'mother_full_name', 'mother_national_id',
];

function birthCertificateValidationRules(): array
{
    return [
        'registration_date'     => 'required|date|not_future',
        'place_of_registration' => 'max:150',
        'father_full_name'      => 'alpha_spaces|max:150',
        'father_national_id'    => 'alpha_num_dash|max:20',
        'mother_full_name'      => 'alpha_spaces|max:150',
        'mother_national_id'    => 'alpha_num_dash|max:20',
    ];
}

/** Builds the exact $data array create/update expect, blanking optional fields to NULL rather than ''. */
function normalizeBirthCertificateData(array $input): array
{
    $data = [];
    foreach (BIRTH_CERTIFICATE_WRITABLE_FIELDS as $field) {
        $value = trim((string) ($input[$field] ?? ''));
        $data[$field] = $value === '' ? null : $value;
    }
    return $data;
}

/**
 * The schema's UNIQUE KEY uq_bc_citizen already guarantees at most one
 * certificate per citizen, ever (active or revoked) — a person only has
 * one birth. This is the friendly pre-check — used both as a yes/no
 * gate and, via its return value, to link straight to the existing
 * certificate instead of just saying "no" — the constraint itself is
 * only the backstop if a race slips past it.
 */
function findBirthCertificateByCitizenId(int $citizenId): ?array
{
    $stmt = getDB()->prepare('SELECT id, certificate_number, status FROM birth_certificates WHERE citizen_id = :citizen_id');
    $stmt->execute(['citizen_id' => $citizenId]);
    return $stmt->fetch() ?: null;
}

/** Inserts a new certificate, retrying only on a certificate_number collision. */
function createBirthCertificate(array $data, int $citizenId, int $issuedBy): int
{
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $newId = retryOnDuplicateKey(function () use ($pdo, $data, $citizenId, $issuedBy) {
            $columns = array_merge(['certificate_number', 'citizen_id', 'issued_by'], BIRTH_CERTIFICATE_WRITABLE_FIELDS);
            $placeholders = implode(', ', array_map(fn ($c) => ":{$c}", $columns));

            $stmt = $pdo->prepare(
                'INSERT INTO birth_certificates (' . implode(', ', $columns) . ')
                 VALUES (' . $placeholders . ')'
            );

            $stmt->execute(array_merge($data, [
                'certificate_number' => generateReferenceNumber('BC'),
                'citizen_id'         => $citizenId,
                'issued_by'          => $issuedBy,
            ]));

            return (int) $pdo->lastInsertId();
        }, 'uq_bc_certificate_number');

        $pdo->commit();

        return $newId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Updates an active certificate's details. The citizen link itself is never editable. */
function updateBirthCertificate(int $id, array $data): bool
{
    $columns = BIRTH_CERTIFICATE_WRITABLE_FIELDS;
    $assignments = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", $columns));

    $stmt = getDB()->prepare("UPDATE birth_certificates SET {$assignments} WHERE id = :id AND status = 'active'");
    $stmt->execute(array_merge($data, ['id' => $id]));

    return $stmt->rowCount() > 0;
}

/** "Soft delete": revokes an active certificate without discarding it. */
function revokeBirthCertificate(int $id): bool
{
    $stmt = getDB()->prepare("UPDATE birth_certificates SET status = 'revoked' WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}

/** Reverses a revoke. */
function restoreBirthCertificate(int $id): bool
{
    $stmt = getDB()->prepare("UPDATE birth_certificates SET status = 'active' WHERE id = :id AND status = 'revoked'");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}

/** One certificate, with the subject citizen's details and the issuing officer's name joined in. */
function findBirthCertificateById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT bc.*, c.citizen_uid, c.first_name, c.middle_name, c.last_name, c.gender,
                c.date_of_birth, c.place_of_birth, c.photo_path, u.full_name AS issued_by_name
         FROM birth_certificates bc
         INNER JOIN citizens c ON c.id = bc.citizen_id
         LEFT JOIN users u ON u.id = bc.issued_by
         WHERE bc.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * One certificate by its printed number, for the public verification page.
 * Same shape as findBirthCertificateById() — verify.php decides which
 * fields are safe to actually display publicly, keeping that a
 * presentation decision rather than a data-access one.
 */
function findBirthCertificateByCertificateNumber(string $certificateNumber): ?array
{
    $stmt = getDB()->prepare(
        'SELECT bc.*, c.citizen_uid, c.first_name, c.middle_name, c.last_name, c.gender, c.date_of_birth, c.place_of_birth
         FROM birth_certificates bc
         INNER JOIN citizens c ON c.id = bc.citizen_id
         WHERE bc.certificate_number = :certificate_number'
    );
    $stmt->execute(['certificate_number' => $certificateNumber]);
    return $stmt->fetch() ?: null;
}

/** Search + filter + paginate. Returns ['rows' => [...], 'total' => int]. */
function getBirthCertificatesList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'bc.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(bc.certificate_number LIKE :search1
                     OR CONCAT(c.first_name, " ", c.last_name) LIKE :search2
                     OR c.citizen_uid LIKE :search3)';
        $term = '%' . $filters['search'] . '%';
        $params['search1'] = $term;
        $params['search2'] = $term;
        $params['search3'] = $term;
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'bc.registration_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'bc.registration_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = getDB()->prepare(
        "SELECT COUNT(*) FROM birth_certificates bc INNER JOIN citizens c ON c.id = bc.citizen_id {$whereSql}"
    );
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT bc.id, bc.certificate_number, bc.registration_date, bc.status,
                c.citizen_uid, c.first_name, c.last_name, c.gender, c.photo_path
         FROM birth_certificates bc
         INNER JOIN citizens c ON c.id = bc.citizen_id
         {$whereSql}
         ORDER BY bc.created_at DESC
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
