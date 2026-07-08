<?php
/**
 * All read-only queries that power the dashboard. Kept in one file,
 * separate from the page itself, because the dashboard is a pure
 * consumer of aggregate data — it has no writes of its own.
 */

/** Citizens: running total plus how many were added this calendar month. */
function getCitizenStats(): array
{
    $stmt = getDB()->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month
         FROM citizens
         WHERE deleted_at IS NULL"
    );
    return $stmt->fetch();
}

/** Birth certificates: running total (active + revoked) plus this month's new records. */
function getBirthCertificateStats(): array
{
    $stmt = getDB()->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month
         FROM birth_certificates"
    );
    return $stmt->fetch();
}

/** National IDs: running total plus this month's new records. */
function getNationalIdStats(): array
{
    $stmt = getDB()->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS this_month
         FROM national_ids"
    );
    return $stmt->fetch();
}

/**
 * ID applications, bucketed by status in a single pass. Phase 5B expanded
 * the workflow to 7 steps (see helpers/applications_repository.php); the
 * dashboard still only needs three buckets, now mapped as:
 *   pending  = submitted + received + in_review (still awaiting a decision)
 *   approved = approved + card_printed + ready_for_collection (decision was yes)
 *   rejected = rejected
 * One query with conditional SUMs instead of four separate COUNT queries.
 */
function getApplicationStats(): array
{
    $stmt = getDB()->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN s.code IN ('submitted', 'received', 'in_review') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN s.code IN ('approved', 'card_printed', 'ready_for_collection') THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN s.code = 'rejected' THEN 1 ELSE 0 END) AS rejected
         FROM id_applications ia
         INNER JOIN application_status s ON s.id = ia.current_status_id"
    );
    return $stmt->fetch() ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

/** Full status breakdown (all five statuses) for the "Application Status Distribution" donut chart. */
function getApplicationStatusDistribution(): array
{
    $stmt = getDB()->query(
        "SELECT s.label, COUNT(ia.id) AS total
         FROM application_status s
         LEFT JOIN id_applications ia ON ia.current_status_id = s.id
         GROUP BY s.id, s.label, s.sort_order
         ORDER BY s.sort_order"
    );
    return $stmt->fetchAll();
}

/** New citizen registrations per month, for the "Monthly Registrations" trend chart. */
function getMonthlyRegistrations(int $months = 6): array
{
    // $months is developer-supplied (never raw user input), and the `int`
    // type hint above already rejects anything non-numeric, so it is safe
    // to interpolate directly — MySQL's INTERVAL clause otherwise rejects
    // a string-bound placeholder here under real (non-emulated) prepares.
    $monthsBack = max(0, $months - 1);

    $stmt = getDB()->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
         FROM citizens
         WHERE deleted_at IS NULL
           AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL {$monthsBack} MONTH)
         GROUP BY month
         ORDER BY month"
    );
    $rows = $stmt->fetchAll();

    // Fill in months with zero registrations so the chart has no gaps.
    $byMonth = array_column($rows, 'total', 'month');
    $filled = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-{$i} months"));
        $filled[$key] = (int) ($byMonth[$key] ?? 0);
    }

    return $filled;
}

/** Most recently submitted applications, with applicant name and current status. */
function getRecentApplications(int $limit = 5): array
{
    $stmt = getDB()->prepare(
        "SELECT ia.id, ia.application_number, ia.applied_date, c.first_name, c.last_name,
                s.label AS status_label, s.code AS status_code
         FROM id_applications ia
         INNER JOIN citizens c ON c.id = ia.citizen_id
         INNER JOIN application_status s ON s.id = ia.current_status_id
         ORDER BY ia.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Most recently registered citizens. */
function getRecentCitizens(int $limit = 5): array
{
    $stmt = getDB()->prepare(
        "SELECT citizen_uid, first_name, last_name, gender, date_of_birth, created_at
         FROM citizens
         WHERE deleted_at IS NULL
         ORDER BY created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Most recent activity log entries, with the acting user's name (or "System" if none). */
function getRecentActivityLogs(int $limit = 8): array
{
    $stmt = getDB()->prepare(
        "SELECT al.action, al.description, al.created_at, u.full_name
         FROM activity_logs al
         LEFT JOIN users u ON u.id = al.user_id
         ORDER BY al.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
