<?php
/**
 * Data-access layer for the Reports module. Every query here is a plain
 * read — reports never write to the tables they summarize, only to the
 * `reports` table itself (a log of what was generated, not the data).
 */

const REPORT_TYPES = ['citizens', 'birth_certificates', 'national_ids', 'applications'];

function reportTypeLabel(string $type): string
{
    return match ($type) {
        'citizens'            => 'Citizens Report',
        'birth_certificates'  => 'Birth Certificates Report',
        'national_ids'        => 'National ID Report',
        'applications'        => 'Applications Report',
        default               => 'Report',
    };
}

/**
 * Turns a period preset into a concrete date range. 'custom' falls back
 * to "this month so far" if the caller didn't supply usable dates. The
 * range is always clamped so date_to never runs past today and date_from
 * never lands after date_to — a report can't be asked to cover the future.
 */
function resolveReportPeriod(string $period, string $customFrom = '', string $customTo = ''): array
{
    $today = new DateTimeImmutable('today');

    switch ($period) {
        case 'this_month':
            $from = $today->modify('first day of this month');
            $to   = $today->modify('last day of this month');
            break;
        case 'last_month':
            $from = $today->modify('first day of last month');
            $to   = $today->modify('last day of last month');
            break;
        case 'this_year':
            $from = new DateTimeImmutable($today->format('Y') . '-01-01');
            $to   = new DateTimeImmutable($today->format('Y') . '-12-31');
            break;
        case 'last_year':
            $lastYear = ((int) $today->format('Y')) - 1;
            $from = new DateTimeImmutable($lastYear . '-01-01');
            $to   = new DateTimeImmutable($lastYear . '-12-31');
            break;
        default:
            $from = ($customFrom !== '' && strtotime($customFrom) !== false)
                ? new DateTimeImmutable($customFrom)
                : $today->modify('first day of this month');
            $to = ($customTo !== '' && strtotime($customTo) !== false)
                ? new DateTimeImmutable($customTo)
                : $today;
            break;
    }

    if ($to > $today) {
        $to = $today;
    }
    if ($from > $to) {
        $from = $to;
    }

    return ['date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')];
}

/** Citizens registered in the period, optionally by gender/status, plus summary counts. */
function getCitizensReport(array $filters): array
{
    $where = ['deleted_at IS NULL', 'registration_date BETWEEN :date_from AND :date_to'];
    $params = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];

    if (!empty($filters['gender'])) {
        $where[] = 'gender = :gender';
        $params['gender'] = $filters['gender'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params['status'] = $filters['status'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = getDB()->prepare(
        "SELECT citizen_uid, first_name, last_name, gender, date_of_birth, region, district, status, registration_date
         FROM citizens {$whereSql}
         ORDER BY registration_date DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $summaryStmt = getDB()->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male_count,
                SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female_count
         FROM citizens {$whereSql}"
    );
    $summaryStmt->execute($params);

    return ['rows' => $rows, 'summary' => $summaryStmt->fetch()];
}

/** Birth certificates registered in the period, optionally by status, plus summary counts. */
function getBirthCertificatesReport(array $filters): array
{
    $where = ['bc.registration_date BETWEEN :date_from AND :date_to'];
    $params = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];

    if (!empty($filters['status'])) {
        $where[] = 'bc.status = :status';
        $params['status'] = $filters['status'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = getDB()->prepare(
        "SELECT bc.certificate_number, c.citizen_uid, c.first_name, c.last_name, c.gender,
                bc.registration_date, bc.status
         FROM birth_certificates bc
         INNER JOIN citizens c ON c.id = bc.citizen_id
         {$whereSql}
         ORDER BY bc.registration_date DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $summaryStmt = getDB()->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN bc.status = 'active' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN bc.status = 'revoked' THEN 1 ELSE 0 END) AS revoked_count
         FROM birth_certificates bc
         INNER JOIN citizens c ON c.id = bc.citizen_id
         {$whereSql}"
    );
    $summaryStmt->execute($params);

    return ['rows' => $rows, 'summary' => $summaryStmt->fetch()];
}

/** National IDs issued in the period, optionally by status, plus summary counts. */
function getNationalIdsReport(array $filters): array
{
    $where = ['n.issue_date BETWEEN :date_from AND :date_to'];
    $params = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];

    if (!empty($filters['status'])) {
        $where[] = 'n.status = :status';
        $params['status'] = $filters['status'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = getDB()->prepare(
        "SELECT n.id_number, c.citizen_uid, c.first_name, c.last_name,
                n.issue_date, n.expiry_date, n.card_printed_at, n.collected_at, n.status
         FROM national_ids n
         INNER JOIN citizens c ON c.id = n.citizen_id
         {$whereSql}
         ORDER BY n.issue_date DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $summaryStmt = getDB()->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN n.status = 'active' THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN n.status = 'expired' THEN 1 ELSE 0 END) AS expired_count,
                SUM(CASE WHEN n.status = 'revoked' THEN 1 ELSE 0 END) AS revoked_count,
                SUM(CASE WHEN n.card_printed_at IS NOT NULL THEN 1 ELSE 0 END) AS printed_count,
                SUM(CASE WHEN n.collected_at IS NOT NULL THEN 1 ELSE 0 END) AS collected_count
         FROM national_ids n
         INNER JOIN citizens c ON c.id = n.citizen_id
         {$whereSql}"
    );
    $summaryStmt->execute($params);

    return ['rows' => $rows, 'summary' => $summaryStmt->fetch()];
}

/** Applications submitted in the period, optionally by current status, plus summary counts. */
function getApplicationsReport(array $filters): array
{
    $where = ['ia.applied_date BETWEEN :date_from AND :date_to'];
    $params = ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']];

    if (!empty($filters['status'])) {
        $where[] = 's.code = :status';
        $params['status'] = $filters['status'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = getDB()->prepare(
        "SELECT ia.application_number, c.citizen_uid, c.first_name, c.last_name,
                ia.applied_date, s.label AS status_label, s.code AS status_code
         FROM id_applications ia
         INNER JOIN citizens c ON c.id = ia.citizen_id
         INNER JOIN application_status s ON s.id = ia.current_status_id
         {$whereSql}
         ORDER BY ia.applied_date DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $summaryStmt = getDB()->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN s.code = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN s.code = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                SUM(CASE WHEN s.code = 'ready_for_collection' THEN 1 ELSE 0 END) AS completed_count
         FROM id_applications ia
         INNER JOIN citizens c ON c.id = ia.citizen_id
         INNER JOIN application_status s ON s.id = ia.current_status_id
         {$whereSql}"
    );
    $summaryStmt->execute($params);

    return ['rows' => $rows, 'summary' => $summaryStmt->fetch()];
}

/**
 * Logs that a report was generated. CSV exports pass a real $filePath
 * (under storage/reports/, re-downloadable later); PDF "exports" pass
 * null, since that output is a browser print rendering that never
 * touches the server as a file — there's nothing to re-download.
 */
function recordReportGeneration(string $reportType, string $dateFrom, string $dateTo, array $filters, ?string $filePath, int $generatedBy): int
{
    $stmt = getDB()->prepare(
        'INSERT INTO reports (report_type, date_from, date_to, filters, file_path, generated_by)
         VALUES (:report_type, :date_from, :date_to, :filters, :file_path, :generated_by)'
    );
    $stmt->execute([
        'report_type'  => $reportType,
        'date_from'    => $dateFrom,
        'date_to'      => $dateTo,
        'filters'      => json_encode($filters),
        'file_path'    => $filePath,
        'generated_by' => $generatedBy,
    ]);

    return (int) getDB()->lastInsertId();
}

function findReportById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM reports WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

/** Only ever-downloadable (CSV) exports show up here — a PDF preview has no file to re-download. */
function getRecentReports(int $limit = 10): array
{
    $stmt = getDB()->prepare(
        'SELECT r.id, r.report_type, r.date_from, r.date_to, r.generated_at, u.full_name AS generated_by_name
         FROM reports r
         LEFT JOIN users u ON u.id = r.generated_by
         WHERE r.file_path IS NOT NULL
         ORDER BY r.generated_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
