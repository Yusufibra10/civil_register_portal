<?php
/**
 * Data-access layer for national_ids. There is no standalone "create"
 * form for this module — a row here only ever comes into existence as
 * the automatic outcome of an approved application (see
 * helpers/applications_repository.php's transitionApplicationStatus()).
 */

const NATIONAL_ID_VALIDITY_YEARS = 10;

/** Bootstrap badge variant for a status value — one mapping, reused by the list and detail views. */
function nationalIdStatusVariant(string $status): string
{
    return match ($status) {
        'active'  => 'success',
        'expired' => 'secondary',
        'revoked' => 'danger',
        default   => 'secondary',
    };
}

/** The schema's UNIQUE KEY uq_nid_citizen allows at most one row per citizen, ever. */
function citizenHasNationalId(int $citizenId): bool
{
    $stmt = getDB()->prepare('SELECT id FROM national_ids WHERE citizen_id = :citizen_id');
    $stmt->execute(['citizen_id' => $citizenId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Called when an application reaches 'approved'. Returns null (a no-op)
 * if the citizen already has a national ID row rather than throwing —
 * this keeps a second approval on some future workflow path safe instead
 * of surfacing a confusing duplicate-key error to whoever triggered it.
 */
function issueNationalIdForCitizen(int $citizenId, int $issuedBy): ?int
{
    if (citizenHasNationalId($citizenId)) {
        return null;
    }

    $pdo = getDB();

    return retryOnDuplicateKey(function () use ($pdo, $citizenId, $issuedBy) {
        $stmt = $pdo->prepare(
            'INSERT INTO national_ids (id_number, citizen_id, issue_date, expiry_date, issued_by)
             VALUES (:id_number, :citizen_id, :issue_date, :expiry_date, :issued_by)'
        );
        $stmt->execute([
            'id_number'   => generateReferenceNumber('NID'),
            'citizen_id'  => $citizenId,
            'issue_date'  => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+' . NATIONAL_ID_VALIDITY_YEARS . ' years')),
            'issued_by'   => $issuedBy,
        ]);

        return (int) $pdo->lastInsertId();
    }, 'uq_nid_number');
}

/** Called when an application reaches 'card_printed'. */
function markNationalIdCardPrinted(int $citizenId): void
{
    getDB()->prepare(
        'UPDATE national_ids SET card_printed_at = NOW() WHERE citizen_id = :citizen_id AND card_printed_at IS NULL'
    )->execute(['citizen_id' => $citizenId]);
}

/** Called from national_id/collect.php when staff confirm the citizen picked up their card. */
function markNationalIdCollected(int $id): bool
{
    $stmt = getDB()->prepare('UPDATE national_ids SET collected_at = NOW() WHERE id = :id AND collected_at IS NULL');
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}

/** One national ID, with the citizen's details and the issuing officer's name joined in. */
function findNationalIdById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT n.*, c.citizen_uid, c.first_name, c.middle_name, c.last_name, c.gender,
                c.date_of_birth, c.photo_path, u.full_name AS issued_by_name
         FROM national_ids n
         INNER JOIN citizens c ON c.id = n.citizen_id
         LEFT JOIN users u ON u.id = n.issued_by
         WHERE n.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function findNationalIdByCitizenId(int $citizenId): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM national_ids WHERE citizen_id = :citizen_id');
    $stmt->execute(['citizen_id' => $citizenId]);
    return $stmt->fetch() ?: null;
}

/** Search + filter + paginate. Returns ['rows' => [...], 'total' => int]. */
function getNationalIdsList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'n.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(n.id_number LIKE :search1 OR CONCAT(c.first_name, " ", c.last_name) LIKE :search2 OR c.citizen_uid LIKE :search3)';
        $term = '%' . $filters['search'] . '%';
        $params['search1'] = $term;
        $params['search2'] = $term;
        $params['search3'] = $term;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = getDB()->prepare(
        "SELECT COUNT(*) FROM national_ids n INNER JOIN citizens c ON c.id = n.citizen_id {$whereSql}"
    );
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT n.id, n.id_number, n.issue_date, n.expiry_date, n.status, n.card_printed_at, n.collected_at,
                c.citizen_uid, c.first_name, c.last_name, c.gender, c.photo_path
         FROM national_ids n
         INNER JOIN citizens c ON c.id = n.citizen_id
         {$whereSql}
         ORDER BY n.created_at DESC
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
