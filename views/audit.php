<?php
require_admin();
$layoutTitle = 'Audit logs';
$pdo = db();
$search = trim((string) ($_GET['q'] ?? ''));
$pageNumber = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE COALESCE(account.full_name, 'Deleted user') ILIKE ?
        OR COALESCE(account.email, '') ILIKE ?
        OR action.label ILIKE ? OR entity.label ILIKE ?
        OR CAST(log.entity_record_id AS TEXT) ILIKE ?
        OR COALESCE(log.details::text, '') ILIKE ?";
    $needle = '%' . $search . '%';
    $params = array_fill(0, 6, $needle);
}
$count = $pdo->prepare(
    'SELECT COUNT(*) FROM audit_log log
     LEFT JOIN app_user account ON account.user_id = log.user_id
     JOIN audit_action action ON action.action_id = log.action_id
     JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id ' . $where
);
$count->execute($params);
$total = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;
$logs = $pdo->prepare(
    'SELECT log.audit_id, log.occurred_at, log.entity_record_id, log.details,
            account.full_name, account.email, action.label AS action, entity.label AS entity
     FROM audit_log log
     LEFT JOIN app_user account ON account.user_id = log.user_id
     JOIN audit_action action ON action.action_id = log.action_id
     JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id ' . $where . '
     ORDER BY log.occurred_at DESC, log.audit_id DESC
     LIMIT ' . $perPage . ' OFFSET ' . $offset
);
$logs->execute($params);
$logs = $logs->fetchAll();
$summarizeChanges = static function (?array $details): string {
    if (!$details) {
        return '';
    }
    if (isset($details['status']) && is_array($details['status'])) {
        $status = $details['status'];
        $statusLabel = static function ($value): string {
            $value = (string) $value;
            return $value === 'ready' ? 'Ready to harvest' : ucwords(str_replace('_', ' ', $value ?: 'Unknown'));
        };
        return 'Status: ' . $statusLabel($status['before'] ?? 'Unknown')
            . ' → ' . $statusLabel($status['after'] ?? 'Unknown');
    }
    $before = $details['before'] ?? null;
    $after = $details['after'] ?? null;
    if (!is_array($before) || !is_array($after)) {
        return '';
    }
    $ignored = ['created_at', 'updated_at'];
    $changes = [];
    foreach ($after as $key => $newValue) {
        if (in_array($key, $ignored, true) || !array_key_exists($key, $before)) {
            continue;
        }
        if (str_ends_with((string) $key, '_id') && array_key_exists(substr((string) $key, 0, -3), $after)) {
            continue;
        }
        $oldValue = $before[$key];
        if ((string) ($oldValue ?? '') === (string) ($newValue ?? '')) {
            continue;
        }
        $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', (string) $key)));
        $format = static function ($value) use ($key): string {
            if ($value === null || $value === '') return 'Empty';
            if ($key === 'is_active') return $value ? 'Active' : 'Inactive';
            if (is_bool($value)) return $value ? 'Yes' : 'No';
            return ucwords(str_replace('_', ' ', (string) $value));
        };
        $changes[] = $label . ': ' . $format($oldValue) . ' → ' . $format($newValue);
    }
    return implode('; ', $changes);
};
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR ONLY</p>
        <h1 class="title is-3">Detailed audit logs</h1>
        <p class="subtitle is-6">Review user activity and concise summaries of record updates.</p>
        <div class="box table-card">
            <form method="get" class="field has-addons">
                <input type="hidden" name="page" value="audit">
                <div class="control is-expanded"><input class="input" name="q" value="<?= e($search) ?>" placeholder="Search user, action, record, or details"></div>
                <div class="control"><button class="button">Search</button></div>
            </form>
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable">
                    <thead><tr><th>Date and time</th><th>User</th><th>Action</th><th>Record ID</th><th>Change details</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $details = $log['details'];
                            if (is_string($details)) {
                                $details = json_decode($details, true) ?: null;
                            }
                            $changeSummary = $log['action'] === 'Update'
                                ? $summarizeChanges(is_array($details) ? $details : null)
                                : '';
                            ?>
                            <tr>
                                <td><?= e((new DateTimeImmutable($log['occurred_at']))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y g:i:s A')) ?></td>
                                <td><?= e($log['full_name'] ?? 'Deleted user') ?><br><small><?= e($log['email'] ?? '') ?></small></td>
                                <td><?= e($log['action'] . ' · ' . $log['entity']) ?></td>
                                <td>#<?= e($log['entity_record_id'] ?? '—') ?></td>
                                <td><?= $changeSummary !== '' ? e($changeSummary) : '<span class="has-text-grey">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?><tr><td colspan="5" class="has-text-centered">No audit events found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <nav class="pagination is-right" aria-label="Audit log pages">
                <a class="pagination-previous" href="<?= e(url('page=audit&p=' . max(1, $pageNumber - 1) . '&q=' . urlencode($search))) ?>" <?= $pageNumber <= 1 ? 'disabled' : '' ?>>Previous</a>
                <a class="pagination-next" href="<?= e(url('page=audit&p=' . min($totalPages, $pageNumber + 1) . '&q=' . urlencode($search))) ?>" <?= $pageNumber >= $totalPages ? 'disabled' : '' ?>>Next</a>
                <span class="pagination-list">Page <?= $pageNumber ?> of <?= $totalPages ?> · <?= number_format($total) ?> events</span>
            </nav>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
