<?php
/**
 * Data-access layer for the National ID application workflow
 * (id_applications + id_application_status_history), including the
 * transition graph that decides which status changes are legal and the
 * side effects certain transitions trigger in national_ids.
 */
require_once __DIR__ . '/national_ids_repository.php';

/**
 * The workflow graph. Key = current status code, value = the list of
 * status codes it may legally move to next. Anything not listed here —
 * including every backward jump, e.g. "approved" -> "submitted" — is
 * refused by transitionApplicationStatus() regardless of who asks.
 *
 * rejected is reachable from any pre-decision state (submitted, received,
 * in_review), matching how registries actually work: an application can
 * be turned away for missing documents well before a full review. Once
 * rejected or ready_for_collection, an application is terminal.
 */
const APPLICATION_STATUS_TRANSITIONS = [
    'submitted'            => ['received', 'rejected'],
    'received'             => ['in_review', 'rejected'],
    'in_review'            => ['approved', 'rejected'],
    'approved'             => ['card_printed'],
    'rejected'             => [],
    'card_printed'         => ['ready_for_collection'],
    'ready_for_collection' => [],
];

/** Bootstrap badge variant for a status code — one mapping, reused by the list, detail, and timeline views. */
function applicationStatusVariant(string $code): string
{
    return match ($code) {
        'submitted', 'received'                             => 'secondary',
        'in_review'                                          => 'warning',
        'approved', 'card_printed', 'ready_for_collection'   => 'success',
        'rejected'                                            => 'danger',
        default                                               => 'secondary',
    };
}

/** Looks up an application_status.id by its code, memoized for the request — this lookup table never changes mid-request. */
function getApplicationStatusId(string $code): ?int
{
    static $cache = [];

    if (!array_key_exists($code, $cache)) {
        $stmt = getDB()->prepare('SELECT id FROM application_status WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();
        $cache[$code] = $id !== false ? (int) $id : null;
    }

    return $cache[$code];
}

/** True if this citizen has an application still in progress (anything but rejected/ready_for_collection). */
function citizenHasOpenApplication(int $citizenId): bool
{
    $stmt = getDB()->prepare(
        "SELECT ia.id FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id
         WHERE ia.citizen_id = :citizen_id AND s.code NOT IN ('rejected', 'ready_for_collection')"
    );
    $stmt->execute(['citizen_id' => $citizenId]);
    return (bool) $stmt->fetchColumn();
}

/** Inserts a new application (status: submitted) and its first history row, retrying only on an application_number collision. */
function createApplication(int $citizenId, string $appliedDate, ?string $remarks, int $createdBy): int
{
    $pdo = getDB();
    $submittedStatusId = getApplicationStatusId('submitted');

    $pdo->beginTransaction();

    try {
        $newId = retryOnDuplicateKey(function () use ($pdo, $citizenId, $appliedDate, $remarks, $submittedStatusId) {
            $stmt = $pdo->prepare(
                'INSERT INTO id_applications (application_number, citizen_id, current_status_id, applied_date, remarks)
                 VALUES (:application_number, :citizen_id, :current_status_id, :applied_date, :remarks)'
            );
            $stmt->execute([
                'application_number' => generateReferenceNumber('APP'),
                'citizen_id'         => $citizenId,
                'current_status_id'  => $submittedStatusId,
                'applied_date'       => $appliedDate,
                'remarks'            => $remarks,
            ]);

            return (int) $pdo->lastInsertId();
        }, 'uq_app_number');

        $pdo->prepare(
            'INSERT INTO id_application_status_history (application_id, status_id, changed_by, remarks)
             VALUES (:application_id, :status_id, :changed_by, :remarks)'
        )->execute([
            'application_id' => $newId,
            'status_id'      => $submittedStatusId,
            'changed_by'     => $createdBy,
            'remarks'        => 'Application submitted.',
        ]);

        $pdo->commit();

        return $newId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Moves an application to $targetCode. Validates the move against
 * APPLICATION_STATUS_TRANSITIONS first — this is the one place every
 * illegal jump (including "approved" -> "submitted") is refused,
 * regardless of which page or handler is calling it. Records the change
 * in id_application_status_history, and triggers the national_ids side
 * effect an approval or a print requires, all inside one transaction.
 *
 * @throws InvalidArgumentException if the application doesn't exist or the transition isn't allowed.
 */
function transitionApplicationStatus(int $applicationId, string $targetCode, ?string $remarks, int $changedBy): void
{
    $application = findApplicationById($applicationId);

    if ($application === null) {
        throw new InvalidArgumentException('Application not found.');
    }

    $currentCode = $application['status_code'];
    $allowedNext = APPLICATION_STATUS_TRANSITIONS[$currentCode] ?? [];

    if (!in_array($targetCode, $allowedNext, true)) {
        throw new InvalidArgumentException("Cannot move an application from \"{$currentCode}\" to \"{$targetCode}\".");
    }

    $targetStatusId = getApplicationStatusId($targetCode);
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $pdo->prepare('UPDATE id_applications SET current_status_id = :status_id WHERE id = :id')
            ->execute(['status_id' => $targetStatusId, 'id' => $applicationId]);

        $pdo->prepare(
            'INSERT INTO id_application_status_history (application_id, status_id, changed_by, remarks)
             VALUES (:application_id, :status_id, :changed_by, :remarks)'
        )->execute([
            'application_id' => $applicationId,
            'status_id'      => $targetStatusId,
            'changed_by'     => $changedBy,
            'remarks'        => $remarks,
        ]);

        // Side effects the brief specifies: approval issues the physical
        // record, card_printed stamps it as produced. Both are no-ops if
        // the citizen already has a national ID row (see
        // helpers/national_ids_repository.php for why that's safe).
        if ($targetCode === 'approved') {
            issueNationalIdForCitizen((int) $application['citizen_id'], $changedBy);
        } elseif ($targetCode === 'card_printed') {
            markNationalIdCardPrinted((int) $application['citizen_id']);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Reassigns (or unassigns, with null) the processing officer. Independent of a status change. */
function assignApplicationOfficer(int $applicationId, ?int $userId): bool
{
    $stmt = getDB()->prepare('UPDATE id_applications SET assigned_to = :assigned_to WHERE id = :id');
    $stmt->execute(['assigned_to' => $userId, 'id' => $applicationId]);
    return $stmt->rowCount() > 0;
}

/** One application, with citizen and assigned-officer details joined in. */
function findApplicationById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT ia.*, s.code AS status_code, s.label AS status_label,
                c.citizen_uid, c.first_name, c.middle_name, c.last_name, c.gender, c.photo_path,
                u.full_name AS assigned_to_name
         FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id
         INNER JOIN citizens c ON c.id = ia.citizen_id
         LEFT JOIN users u ON u.id = ia.assigned_to
         WHERE ia.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Same shape, looked up by the number a citizen would actually have in
 * hand — this is what powers the public tracking page. Deliberately
 * leaner than findApplicationById(): only what's safe to show someone
 * who has proven nothing beyond knowing this one number.
 */
function findApplicationByNumber(string $applicationNumber): ?array
{
    $stmt = getDB()->prepare(
        'SELECT ia.id, ia.application_number, ia.applied_date, ia.updated_at,
                s.code AS status_code, s.label AS status_label,
                c.first_name, c.last_name
         FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id
         INNER JOIN citizens c ON c.id = ia.citizen_id
         WHERE ia.application_number = :application_number'
    );
    $stmt->execute(['application_number' => $applicationNumber]);
    return $stmt->fetch() ?: null;
}

/** Full status history for the timeline UI, oldest first. */
function getApplicationTimeline(int $applicationId): array
{
    $stmt = getDB()->prepare(
        'SELECT h.remarks, h.changed_at, s.code AS status_code, s.label AS status_label,
                u.full_name AS changed_by_name
         FROM id_application_status_history h
         INNER JOIN application_status s ON s.id = h.status_id
         LEFT JOIN users u ON u.id = h.changed_by
         WHERE h.application_id = :application_id
         ORDER BY h.changed_at ASC'
    );
    $stmt->execute(['application_id' => $applicationId]);
    return $stmt->fetchAll();
}

/** Search + filter + paginate. Returns ['rows' => [...], 'total' => int]. */
function getApplicationsList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 's.code = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(ia.application_number LIKE :search1
                     OR CONCAT(c.first_name, " ", c.last_name) LIKE :search2
                     OR c.citizen_uid LIKE :search3)';
        $term = '%' . $filters['search'] . '%';
        $params['search1'] = $term;
        $params['search2'] = $term;
        $params['search3'] = $term;
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'ia.applied_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'ia.applied_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = getDB()->prepare(
        "SELECT COUNT(*) FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id
         INNER JOIN citizens c ON c.id = ia.citizen_id {$whereSql}"
    );
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT ia.id, ia.application_number, ia.applied_date, s.code AS status_code, s.label AS status_label,
                c.citizen_uid, c.first_name, c.last_name, c.photo_path
         FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id
         INNER JOIN citizens c ON c.id = ia.citizen_id
         {$whereSql}
         ORDER BY ia.created_at DESC
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
