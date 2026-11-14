<?php
$layoutTitle = 'Administrator overview';
$pdo = db();
$summary = [
    'User accounts' => (int) $pdo->query('SELECT COUNT(*) FROM app_user')->fetchColumn(),
    'Active users' => (int) $pdo->query('SELECT COUNT(*) FROM app_user WHERE is_active')->fetchColumn(),
    'Farmers' => (int) $pdo->query('SELECT COUNT(*) FROM farmer')->fetchColumn(),
    'Farms' => (int) $pdo->query('SELECT COUNT(*) FROM farm')->fetchColumn(),
    'Crop types' => (int) $pdo->query('SELECT COUNT(*) FROM crop')->fetchColumn(),
    'Varieties' => (int) $pdo->query('SELECT COUNT(*) FROM crop_variety')->fetchColumn(),
    'Plantings' => (int) $pdo->query('SELECT COUNT(*) FROM planting')->fetchColumn(),
    'Harvest records' => (int) $pdo->query('SELECT COUNT(*) FROM harvest')->fetchColumn(),
];
$recentAudit = $pdo->query(
    'SELECT log.audit_id, log.occurred_at, log.entity_record_id,
            account.full_name, action.label AS action, entity.label AS entity
     FROM audit_log log
     LEFT JOIN app_user account ON account.user_id = log.user_id
     JOIN audit_action action ON action.action_id = log.action_id
     JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id
     ORDER BY log.occurred_at DESC, log.audit_id DESC
     LIMIT 8'
)->fetchAll();
require __DIR__ . '/layout-top.php';
?>
<section class="hero is-small welcome">
    <div class="hero-body">
        <div class="container dashboard-hero">
            <div>
                <p class="eyebrow">AGRIINFO / SYSTEM ADMINISTRATION</p>
                <h1 class="title is-2">Administrator overview</h1>
                <p class="subtitle is-6"><?= date('l, F j, Y') ?> · User access, records, and system activity.</p>
            </div>
            <div class="buttons dashboard-hero-actions">
                <a class="button is-primary" href="<?= e(url('page=users')) ?>">Manage users</a>
                <a class="button is-light" href="<?= e(url('page=audit')) ?>">View audit logs</a>
            </div>
        </div>
    </div>
</section>
<section class="section dashboard-section">
    <div class="container">
        <div class="dashboard-section-heading">
            <p class="eyebrow">SYSTEM SUMMARY</p>
            <h2 class="title is-4">Accounts and records</h2>
        </div>
        <div class="columns is-multiline dashboard-stats">
            <?php foreach ($summary as $label => $count): ?>
                <div class="column is-2">
                    <div class="box stat-card">
                        <div class="stat-label"><?= e($label) ?></div>
                        <div class="stat-number"><?= number_format($count) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <section class="box table-card recent-harvest-panel">
            <div class="panel-heading-row">
                <div>
                    <p class="eyebrow">SECURITY AND CHANGE HISTORY</p>
                    <h2 class="title is-5">Recent audit activity</h2>
                </div>
                <a class="button is-small is-light" href="<?= e(url('page=audit')) ?>">All audit logs</a>
            </div>
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable">
                    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentAudit as $entry): ?>
                            <tr>
                                <td><?= e(date('M j, Y g:i A', strtotime($entry['occurred_at']))) ?></td>
                                <td><?= e($entry['full_name'] ?? 'Deleted user') ?></td>
                                <td><?= e($entry['action'] . ' · ' . $entry['entity']) ?></td>
                                <td>#<?= e($entry['entity_record_id'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentAudit): ?>
                            <tr><td colspan="4" class="has-text-grey">No audit events have been recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
