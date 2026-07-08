<?php
/**
 * Data-access layer for browsing activity_logs (Phase 6's "Audit Logs
 * Expansion" viewer). The table itself and logActivity() have existed
 * since Phase 3/4 — this file only adds a way to search and page
 * through what's already being recorded.
 */

/** Search + filter + paginate. Returns ['rows' => [...], 'total' => int]. */
function getActivityLogsList(array $filters, int $page, int $perPage): array
{
    $where = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = '(al.action LIKE :search1 OR al.description LIKE :search2 OR u.full_name LIKE :search3)';
        $term = '%' . $filters['search'] . '%';
        $params['search1'] = $term;
        $params['search2'] = $term;
        $params['search3'] = $term;
    }

    if (!empty($filters['module'])) {
        $where[] = 'al.module = :module';
        $params['module'] = $filters['module'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'al.created_at >= :date_from';
        $params['date_from'] = $filters['date_from'] . ' 00:00:00';
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'al.created_at <= :date_to';
        $params['date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = getDB()->prepare("SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id {$whereSql}");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $stmt = getDB()->prepare(
        "SELECT al.action, al.module, al.description, al.ip_address, al.created_at, u.full_name AS user_name
         FROM activity_logs al
         LEFT JOIN users u ON u.id = al.user_id
         {$whereSql}
         ORDER BY al.created_at DESC
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

/** Distinct module values already in use, for the filter dropdown — never a hardcoded list that could drift. */
function getActivityLogModules(): array
{
    return getDB()->query(
        "SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module"
    )->fetchAll(PDO::FETCH_COLUMN);
}
