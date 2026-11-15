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

$decodeDetails = static function ($details): ?array {
    if (is_string($details)) {
        $details = json_decode($details, true);
    }
    return is_array($details) ? $details : null;
};
$recordName = static function (string $entity, ?array $snapshot, $recordId): string {
    $name = match ($entity) {
        'User' => $snapshot['full_name'] ?? null,
        'Farmer' => trim(($snapshot['first_name'] ?? '') . ' ' . ($snapshot['last_name'] ?? '')),
        'Farm' => $snapshot['farm_name'] ?? null,
        'Crop type' => $snapshot['crop_name'] ?? null,
        'Crop variety' => $snapshot['variety_name'] ?? null,
        default => null,
    };
    if (is_string($name) && trim($name) !== '') {
        return $entity . ' "' . trim($name) . '"';
    }
    $fallback = $entity === 'Planting' ? 'Planting' : ($entity === 'Harvest' ? 'Harvest' : $entity);
    return $fallback . ' #' . ($recordId ?? '?');
};
$formatValue = static function ($value, string $key): string {
    if ($value === null || $value === '') return 'empty';
    if ($key === 'is_active') return $value ? 'active' : 'inactive';
    if (is_bool($value)) return $value ? 'yes' : 'no';
    if (str_ends_with($key, '_id') && is_numeric($value)) return '#' . $value;
    return (string) $value;
};
$changeSummary = static function (array $log) use ($decodeDetails, $recordName, $formatValue): string {
    $details = $decodeDetails($log['details']);
    $action = strtolower((string) $log['action']);
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        return '';
    }
    $snapshotKey = $action === 'delete' ? 'before' : 'after';
    $snapshot = isset($details[$snapshotKey]) && is_array($details[$snapshotKey])
        ? $details[$snapshotKey]
        : null;
    $subject = $recordName((string) $log['entity'], $snapshot, $log['entity_record_id']);
    if ($action === 'create') {
        return $subject . ' created';
    }
    if ($action === 'delete') {
        return $subject . ' removed';
    }
    if (isset($details['status']) && is_array($details['status'])) {
        $before = (string) ($details['status']['before'] ?? 'unknown');
        $after = (string) ($details['status']['after'] ?? 'unknown');
        $statusName = static fn (string $value): string => $value === 'ready'
            ? 'ready to harvest'
            : str_replace('_', ' ', $value);
        return $subject . ' status: ' . $statusName($before) . ' → ' . $statusName($after);
    }
    $before = $details['before'] ?? null;
    $after = $details['after'] ?? null;
    if (!is_array($before) || !is_array($after)) {
        return $subject . ' updated';
    }
    $ignore = ['created_at', 'updated_at', 'password_hash'];
    $changes = [];
    foreach ($after as $key => $newValue) {
        if (in_array($key, $ignore, true) || !array_key_exists($key, $before)) continue;
        if (str_ends_with((string) $key, '_id') && array_key_exists(substr((string) $key, 0, -3), $after)) continue;
        if ((string) ($before[$key] ?? '') === (string) ($newValue ?? '')) continue;
        $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', (string) $key)));
        $changes[] = $label . ': ' . $formatValue($before[$key], (string) $key)
            . ' → ' . $formatValue($newValue, (string) $key);
        if (count($changes) === 2) break;
    }
    if (!empty($details['password_reset'])) {
        $changes[] = 'password reset';
    }
    return $subject . ($changes ? ' — ' . implode('; ', $changes) : ' updated');
};
$actionOptions = $pdo->query('SELECT label FROM audit_action ORDER BY sort_order, label')->fetchAll(PDO::FETCH_COLUMN);
$moduleOptions = $pdo->query('SELECT label FROM audit_entity ORDER BY sort_order, label')->fetchAll(PDO::FETCH_COLUMN);
$pageQuery = ['page' => 'audit', 'sort' => $sort, 'dir' => strtolower($direction)] + array_filter($filters, static fn ($value) => $value !== '');
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR ONLY</p>
        <h1 class="title is-3">Detailed audit logs</h1>
        <p class="subtitle is-6">Filter activity by user, action, module, change details, and date period.</p>
        <div class="box table-card">
            <form method="get" class="columns is-multiline is-vcentered mb-5">
                <input type="hidden" name="page" value="audit">
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-user">User</label>
                    <input class="input is-small" id="audit-user" name="user" value="<?= e($filters['user']) ?>" placeholder="Name or email">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-action">Action</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-action" name="action">
                            <option value="">All actions</option>
                            <?php foreach ($actionOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $filters['action'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-module">Module</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-module" name="module">
                            <option value="">All modules</option>
                            <?php foreach ($moduleOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= $filters['module'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-detail">Change details</label>
                    <input class="input is-small" id="audit-detail" name="detail" value="<?= e($filters['detail']) ?>" placeholder="Filter details">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-from">From date</label>
                    <input class="input is-small" type="date" id="audit-from" name="from" value="<?= e($filters['from']) ?>">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-to">To date</label>
                    <input class="input is-small" type="date" id="audit-to" name="to" value="<?= e($filters['to']) ?>">
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-sort">Order by</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-sort" name="sort">
                            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Date and time</option>
                            <option value="user" <?= $sort === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="action" <?= $sort === 'action' ? 'selected' : '' ?>>Action</option>
                            <option value="module" <?= $sort === 'module' ? 'selected' : '' ?>>Module</option>
                        </select>
                    </div>
                </div>
                <div class="column is-one-quarter-desktop is-half-tablet">
                    <label class="label is-small" for="audit-direction">Direction</label>
                    <div class="select is-small is-fullwidth">
                        <select id="audit-direction" name="dir">
                            <option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                </div>
                <div class="column is-full">
                    <button class="button is-small">Apply filters</button>
                    <a class="button is-small is-light" href="<?= e(url('page=audit')) ?>">Clear filters</a>
                </div>
            </form>
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable">
                    <thead><tr><th>Date and time</th><th>User</th><th>Action / module</th><th>Change details</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php $summary = $changeSummary($log); ?>
                            <tr>
                                <td><?= e((new DateTimeImmutable($log['occurred_at']))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y g:i:s A')) ?></td>
                                <td><?= e($log['full_name'] ?? 'Deleted user') ?><br><small><?= e($log['email'] ?? '') ?></small></td>
                                <td><?= e($log['action'] . ' · ' . $log['entity']) ?></td>
                                <td><?= $summary !== '' ? e($summary) : '<span class="has-text-grey">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?><tr><td colspan="4" class="has-text-centered">No audit events found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <nav class="pagination is-right" aria-label="Audit log pages">
                <?php $previousQuery = $pageQuery + ['p' => max(1, $pageNumber - 1)]; ?>
                <?php $nextQuery = $pageQuery + ['p' => min($totalPages, $pageNumber + 1)]; ?>
                <a class="pagination-previous" href="<?= e(url(http_build_query($previousQuery))) ?>" <?= $pageNumber <= 1 ? 'disabled' : '' ?>>Previous</a>
                <a class="pagination-next" href="<?= e(url(http_build_query($nextQuery))) ?>" <?= $pageNumber >= $totalPages ? 'disabled' : '' ?>>Next</a>
                <span class="pagination-list">Page <?= $pageNumber ?> of <?= $totalPages ?> · <?= number_format($total) ?> events</span>
            </nav>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
