<?php
require_admin();
$layoutTitle = 'Audit logs';
$pdo = db();
$pageNumber = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;
$filters = [
    'user' => trim((string) ($_GET['user'] ?? '')),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'module' => trim((string) ($_GET['module'] ?? '')),
    'detail' => trim((string) ($_GET['detail'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];
$conditions = [];
$params = [];
if ($filters['user'] !== '') {
    $conditions[] = "(COALESCE(account.full_name, 'Deleted user') ILIKE ? OR COALESCE(account.email, '') ILIKE ?)";
    $params[] = '%' . $filters['user'] . '%';
    $params[] = '%' . $filters['user'] . '%';
}
if ($filters['action'] !== '') {
    $conditions[] = 'action.label = ?';
    $params[] = $filters['action'];
}
if ($filters['module'] !== '') {
    $conditions[] = 'entity.label = ?';
    $params[] = $filters['module'];
}
if ($filters['detail'] !== '') {
    $conditions[] = "COALESCE(log.details::text, '') ILIKE ?";
    $params[] = '%' . $filters['detail'] . '%';
}
foreach (['from', 'to'] as $dateKey) {
    if ($filters[$dateKey] !== '') {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$dateKey]);
        if (!$parsed || $parsed->format('Y-m-d') !== $filters[$dateKey]) {
            $conditions[] = 'FALSE';
        } elseif ($dateKey === 'from') {
            $conditions[] = "log.occurred_at >= (?::date::timestamp AT TIME ZONE 'Asia/Manila')";
            $params[] = $filters[$dateKey];
        } else {
            $conditions[] = "log.occurred_at < ((?::date + 1)::timestamp AT TIME ZONE 'Asia/Manila')";
            $params[] = $filters[$dateKey];
        }
    }
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sortColumns = [
    'date' => 'log.occurred_at',
    'user' => 'account.full_name',
    'action' => 'action.label',
    'module' => 'entity.label',
];
$sort = (string) ($_GET['sort'] ?? 'date');
if (!isset($sortColumns[$sort])) {
    $sort = 'date';
}
$direction = strtolower((string) ($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$baseFrom = ' FROM audit_log log
     LEFT JOIN app_user account ON account.user_id = log.user_id
     JOIN audit_action action ON action.action_id = log.action_id
     JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id ';
$count = $pdo->prepare('SELECT COUNT(*)' . $baseFrom . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;
$orderBy = $sortColumns[$sort];
$logs = $pdo->prepare(
    'SELECT log.audit_id, log.occurred_at, log.entity_record_id, log.details,
            account.full_name, account.email, action.label AS action, entity.label AS entity
     ' . $baseFrom . $where . '
     ORDER BY ' . $orderBy . ' ' . $direction . ' NULLS LAST, log.audit_id DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset
);
$logs->execute($params);
$logs = $logs->fetchAll();

$actionOptions = $pdo->query('SELECT label FROM audit_action ORDER BY sort_order, label')->fetchAll(PDO::FETCH_COLUMN);
$moduleOptions = $pdo->query('SELECT label FROM audit_entity ORDER BY sort_order, label')->fetchAll(PDO::FETCH_COLUMN);
$pageQuery = ['page' => 'audit', 'sort' => $sort, 'dir' => strtolower($direction)] + array_filter($filters, static fn ($value) => $value !== '');
