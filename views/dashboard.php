<?php
$isAdmin = (user()['role'] ?? '') === 'Administrator';
$layoutTitle = 'Overview';
$pdo = db();
$manilaToday = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$todayDate = $manilaToday->format('Y-m-d');
$watchPerPage = 7;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'advance_planting') {
    check_csrf();
    $plantingId = filter_input(INPUT_POST, 'planting_id', FILTER_VALIDATE_INT);
    $advance = $pdo->prepare(
        "UPDATE planting p
         SET status_id = next_status.status_id, updated_at = now()
         FROM planting_status current_status, planting_status next_status
         WHERE p.planting_id = ?
           AND current_status.status_id = p.status_id
           AND next_status.code = CASE current_status.code
               WHEN 'planting' THEN 'growing'
               WHEN 'growing' THEN 'ready'
           END
           AND ((current_status.code = 'planting' AND p.planting_date < ?::date)
             OR (current_status.code = 'growing'
                 AND p.planting_date <= ?::date
                 AND p.expected_harvest_date <= ?::date + INTERVAL '20 days'))
         RETURNING p.planting_id"
    );
    $advance->execute([$plantingId, $todayDate, $todayDate, $todayDate]);
    $advancedId = $advance->fetchColumn();
    if ($advancedId) {
        $statusLookup = $pdo->prepare(
            'SELECT status.code FROM planting JOIN planting_status status USING (status_id)
             WHERE planting.planting_id = ?'
        );
        $statusLookup->execute([(int) $advancedId]);
        $afterStatus = $statusLookup->fetchColumn();
        audit('UPDATE', 'planting', (int) $advancedId, [
            'status' => [
                'before' => $afterStatus === 'growing' ? 'planting' : 'growing',
                'after' => $afterStatus,
            ],
        ]);
        flash('success', 'Planting #' . (int) $advancedId . ' status updated.');
    } else {
        flash('danger', 'This planting no longer meets the date rule for a status update.');
    }
    go('page=dashboard');
}

$stats = [];
$tables = [
    'farmers' => 'farmer',
    'farms' => 'farm',
    'harvests' => 'harvest',
];

foreach ($tables as $label => $table) {
    $stats[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$stats['active plantings'] = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     WHERE s.code IN ('planting', 'growing', 'ready')"
)->fetchColumn();
if ($isAdmin) {
    $stats['active users'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM app_user
         WHERE is_active
           AND signed_out_at IS NULL
           AND last_activity_at >= now() - INTERVAL '8 hours'"
    )->fetchColumn();
}
$totalPlantings = (int) $pdo->query('SELECT COUNT(*) FROM planting')->fetchColumn();

$totalHarvest = (float) $pdo
    ->query('SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest')
    ->fetchColumn();

$statusSummary = $pdo->query(
    'SELECT s.code, s.label, COUNT(p.planting_id) AS record_count
     FROM planting_status s
     LEFT JOIN planting p ON p.status_id = s.status_id
     GROUP BY s.status_id, s.code, s.label, s.sort_order
     ORDER BY s.sort_order, s.label'
)->fetchAll();

$statusWatchPage = max(1, (int) ($_GET['status_watch_page'] ?? 1));
$statusWatchCondition = "((s.code = 'planting' AND p.planting_date < ?::date)
    OR (s.code = 'growing' AND p.planting_date <= ?::date
        AND p.expected_harvest_date <= ?::date + INTERVAL '20 days'))";
$statusWatchCount = $pdo->prepare(
    'SELECT COUNT(*) FROM planting p JOIN planting_status s ON s.status_id = p.status_id WHERE ' . $statusWatchCondition
);
$statusWatchCount->execute([$todayDate, $todayDate, $todayDate]);
$statusWatchTotal = (int) $statusWatchCount->fetchColumn();
$statusWatchPages = max(1, (int) ceil($statusWatchTotal / $watchPerPage));
$statusWatchPage = min($statusWatchPage, $statusWatchPages);
$statusWatch = $pdo->prepare(
    "SELECT p.planting_id, p.planting_date, p.expected_harvest_date, s.code, s.label AS status,
            c.crop_name, v.variety_name, f.farm_name
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE {$statusWatchCondition}
     ORDER BY CASE WHEN s.code = 'planting' THEN 0 ELSE 1 END,
              p.expected_harvest_date NULLS LAST, p.planting_id
     LIMIT {$watchPerPage} OFFSET " . (($statusWatchPage - 1) * $watchPerPage)
);
$statusWatch->execute([$todayDate, $todayDate, $todayDate]);
$statusWatch = $statusWatch->fetchAll();

$harvestWatchPage = max(1, (int) ($_GET['harvest_watch_page'] ?? 1));
$harvestWatchCount = $pdo->prepare(
    "SELECT COUNT(*) FROM planting p JOIN planting_status s ON s.status_id = p.status_id
     WHERE p.expected_harvest_date <= ?::date + INTERVAL '20 days' AND s.code = 'ready'"
);
$harvestWatchCount->execute([$todayDate]);
$harvestWatchTotal = (int) $harvestWatchCount->fetchColumn();
$harvestWatchPages = max(1, (int) ceil($harvestWatchTotal / $watchPerPage));
$harvestWatchPage = min($harvestWatchPage, $harvestWatchPages);
$harvestWatch = $pdo->prepare(
    "SELECT p.planting_id, p.expected_harvest_date, s.label AS status,
            c.crop_name, v.variety_name, f.farm_name
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE p.expected_harvest_date <= ?::date + INTERVAL '20 days'
       AND s.code = 'ready'
     ORDER BY p.expected_harvest_date, p.planting_id
     LIMIT {$watchPerPage} OFFSET " . (($harvestWatchPage - 1) * $watchPerPage)
);
$harvestWatch->execute([$todayDate]);
$harvestWatch = $harvestWatch->fetchAll();

$recentHarvestPage = max(1, (int) ($_GET['recent_harvest_page'] ?? 1));
$sixMonthsAgo = $manilaToday->modify('-6 months')->format('Y-m-d');
$recentHarvestCount = $pdo->prepare('SELECT COUNT(*) FROM harvest WHERE harvest_date BETWEEN ? AND ?');
$recentHarvestCount->execute([$sixMonthsAgo, $todayDate]);
$recentHarvestTotal = (int) $recentHarvestCount->fetchColumn();
$recentHarvestPages = max(1, (int) ceil($recentHarvestTotal / $watchPerPage));
$recentHarvestPage = min($recentHarvestPage, $recentHarvestPages);
$recentHarvests = $pdo->prepare(
    'SELECT h.harvest_date, h.quantity_kg,
            h.harvest_id,
            c.crop_name || \' / \' || v.variety_name AS crop_display,
            f.farm_name
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE h.harvest_date BETWEEN ? AND ?
     ORDER BY h.harvest_date DESC, h.harvest_id DESC
     LIMIT ' . $watchPerPage . ' OFFSET ' . (($recentHarvestPage - 1) * $watchPerPage)
);
$recentHarvests->execute([$sixMonthsAgo, $todayDate]);
$recentHarvests = $recentHarvests->fetchAll();
$dashboardPageState = [
    'page' => 'dashboard',
    'status_watch_page' => $statusWatchPage,
    'harvest_watch_page' => $harvestWatchPage,
    'recent_harvest_page' => $recentHarvestPage,
];
$renderDashboardPagination = static function (string $parameter, int $currentPage, int $totalPages) use ($dashboardPageState): string {
    if ($totalPages <= 1) {
        return '';
    }
    $previous = $dashboardPageState;
    $previous[$parameter] = max(1, $currentPage - 1);
    $next = $dashboardPageState;
    $next[$parameter] = min($totalPages, $currentPage + 1);
    return '<nav class="pagination is-small is-centered mt-3" aria-label="Dashboard list pages">'
        . '<a class="pagination-previous" href="' . e(url(http_build_query($previous))) . '"'
        . ($currentPage <= 1 ? ' disabled' : '') . '>Previous</a>'
        . '<a class="pagination-next" href="' . e(url(http_build_query($next))) . '"'
        . ($currentPage >= $totalPages ? ' disabled' : '') . '>Next</a>'
        . '<span class="pagination-list">Page ' . $currentPage . ' of ' . $totalPages . '</span>'
        . '</nav>';
};

$todayAudit = [];
if ($isAdmin) {
    $todayAudit = $pdo->query(
        "SELECT log.audit_id, log.occurred_at, log.entity_record_id,
                account.full_name, action.label AS action, entity.label AS entity
         FROM audit_log log
         LEFT JOIN app_user account ON account.user_id = log.user_id
         JOIN audit_action action ON action.action_id = log.action_id
         JOIN audit_entity entity ON entity.entity_type_id = log.entity_type_id
         WHERE (log.occurred_at AT TIME ZONE 'Asia/Manila')::date =
               (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Manila')::date
         ORDER BY log.occurred_at DESC, log.audit_id DESC
         LIMIT 10"
    )->fetchAll();
}

require __DIR__ . '/layout-top.php';
?>
<section class="hero is-small welcome">
    <div class="hero-body">
        <div class="container dashboard-hero">
            <div>
                <p class="eyebrow">AGRIINFO / <?= $isAdmin ? 'ADMINISTRATOR' : 'STAFF' ?> / DAILY OPERATIONS</p>
                <h1 class="title is-2"><?= $isAdmin ? 'Operations and administration, at a glance.' : 'Field operations, at a glance.' ?></h1>
                <p class="subtitle is-6">
                    <?= date('l, F j, Y') ?> &#183; Your current farm activity and harvest outlook.
                </p>
            </div>
            <div class="buttons dashboard-hero-actions">
                <a class="button is-primary" href="<?= e(url('page=plantings')) ?>">
                    + Record planting
                </a>
                <a class="button is-light" href="<?= e(url('page=harvests')) ?>">
                    + Log harvest
                </a>
            </div>
        </div>
    </div>
</section>

<section class="section dashboard-section">
    <div class="container">
        <div class="dashboard-section-heading">
            <p class="eyebrow">YOUR FARM IN NUMBERS</p>
            <h2 class="title is-4">Operations snapshot</h2>
        </div>

        <div class="columns is-multiline dashboard-stats">
            <?php foreach ($stats as $label => $value): ?>
                <?php
                $target = match ($label) {
                    'active plantings' => 'plantings',
                    'active users' => 'users',
                    default => $label,
                };
                ?>
                <div class="column is-2">
                    <a class="box stat-card" href="<?= e(url('page=' . $target)) ?>">
                        <div class="stat-label"><?= e(ucwords($label)) ?></div>
                        <div class="stat-number"><?= number_format($value) ?></div>
                        <div class="stat-foot">Open records <span aria-hidden="true">&#8599;</span></div>
                    </a>
                </div>
            <?php endforeach; ?>

            <div class="column is-2">
                <a class="box stat-card total-output" href="<?= e(url('page=harvests')) ?>">
                    <div class="stat-label">Lifetime output</div>
                    <div class="stat-number">
                        <?= number_format($totalHarvest, 0) ?> <small>kg</small>
                    </div>
                    <div class="stat-foot">Open harvests <span aria-hidden="true">&#8599;</span></div>
                </a>
            </div>
        </div>

        <div class="dashboard-section-heading dashboard-section-heading-spaced">
            <p class="eyebrow">WHAT NEEDS ATTENTION</p>
            <h2 class="title is-4">Field activity</h2>
        </div>

        <div class="columns is-variable is-4 dashboard-panels">
            <div class="column is-4">
                <section class="box dashboard-panel">
                    <p class="eyebrow">CROP CYCLE</p>
                    <h3 class="title is-5">Planting status</h3>
                    <?php foreach ($statusSummary as $status): ?>
                        <?php
                        $count = (int) $status['record_count'];
                        $share = $totalPlantings > 0
                            ? min(100, (int) round($count / $totalPlantings * 100))
                            : 0;
                        ?>
                        <div class="status-row">
                            <div class="status-row-label">
                                <span><?= e($status['label']) ?></span>
                                <strong><?= number_format($count) ?></strong>
                            </div>
                            <div class="status-track" aria-hidden="true">
                                <span style="width: <?= $share ?>%"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a class="panel-link" href="<?= e(url('page=plantings')) ?>">
                        Manage plantings <span aria-hidden="true">&#8594;</span>
                    </a>
                </section>
            </div>

            <div class="column is-4">
                <section class="box dashboard-panel harvest-watch">
                    <p class="eyebrow">READY · DUE WITHIN 20 DAYS</p>
                    <h3 class="title is-5">Harvest watch</h3>
                    <?php if ($harvestWatch): ?>
                        <div class="watch-list">
                            <?php foreach ($harvestWatch as $planting): ?>
                                <?php
                                $isOverdue = $planting['expected_harvest_date'] < date('Y-m-d');
                                $cropLabel = $planting['crop_name'] . ' / ' . $planting['variety_name'];
                                ?>
                                <a
                                    class="watch-item"
                                    href="<?= e(url(http_build_query(['page' => 'plantings', 'filter' => ['planting_id' => $planting['planting_id']]]))) ?>"
                                >
                                    <span class="watch-copy">
                                        <strong><?= e($cropLabel) ?></strong>
                                        <small><?= e($planting['farm_name']) ?> &#183; <?= e($planting['status']) ?></small>
                                    </span>
                                    <span class="watch-date <?= $isOverdue ? 'is-overdue' : '' ?>">
                                        <?= $isOverdue ? 'Overdue' : e(date('M j', strtotime($planting['expected_harvest_date']))) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?= $renderDashboardPagination('harvest_watch_page', $harvestWatchPage, $harvestWatchPages) ?>
                    <?php else: ?>
                        <p class="panel-empty">
                            No ready plantings are due within the next 20 days.
                        </p>
                    <?php endif; ?>
                    <a class="panel-link" href="<?= e(url('page=plantings')) ?>">
                        View all plantings <span aria-hidden="true">&#8594;</span>
                    </a>
                </section>
            </div>

            <div class="column is-4">
                <section class="box dashboard-panel status-watch">
                    <p class="eyebrow">DATE BASED STATUS ACTIONS</p>
                    <h3 class="title is-5">Status watch</h3>
                    <?php if ($statusWatch): ?>
                        <div class="watch-list">
                            <?php foreach ($statusWatch as $planting): ?>
                                <div class="watch-item status-watch-item">
                                    <a class="watch-copy" href="<?= e(url(http_build_query(['page' => 'plantings', 'filter' => ['planting_id' => $planting['planting_id']]]))) ?>">
                                        <strong>#<?= e($planting['planting_id']) ?> · <?= e($planting['crop_name'] . ' / ' . $planting['variety_name']) ?></strong>
                                        <small><?= e($planting['farm_name']) ?> · <?= e($planting['status']) ?> · <?= e(date('M j, Y', strtotime($planting['planting_date']))) ?></small>
                                    </a>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                        <input type="hidden" name="action" value="advance_planting">
                                        <input type="hidden" name="planting_id" value="<?= e($planting['planting_id']) ?>">
                                        <button class="button is-small is-primary">
                                            Mark <?= $planting['code'] === 'planting' ? 'growing' : 'ready' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?= $renderDashboardPagination('status_watch_page', $statusWatchPage, $statusWatchPages) ?>
                    <?php else: ?>
                        <p class="panel-empty">No plantings need a date based status update.</p>
                    <?php endif; ?>
                    <a class="panel-link" href="<?= e(url('page=plantings')) ?>">Manage plantings <span aria-hidden="true">&#8594;</span></a>
                </section>
            </div>

        </div>

        <?php if ($isAdmin): ?>
            <section class="box table-card recent-harvest-panel">
                <div class="panel-heading-row">
                    <div>
                        <p class="eyebrow">TODAY ONLY</p>
                        <h2 class="title is-5">Recent audit activity</h2>
                    </div>
                    <a class="button is-small is-light" href="<?= e(url('page=audit')) ?>">All audit logs</a>
                </div>
                <div class="table-container">
                    <table class="table is-fullwidth is-hoverable">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record ID</th></tr></thead>
                        <tbody>
                            <?php foreach ($todayAudit as $entry): ?>
                                <tr>
                                    <td><?= e(date('g:i:s A', strtotime($entry['occurred_at']))) ?></td>
                                    <td><?= e($entry['full_name'] ?? 'Deleted user') ?></td>
                                    <td><?= e($entry['action'] . ' · ' . $entry['entity']) ?></td>
                                    <td>#<?= e($entry['entity_record_id'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$todayAudit): ?><tr><td colspan="4" class="has-text-grey">No audit activity recorded today.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="box table-card recent-harvest-panel">
            <div class="panel-heading-row">
                <div>
                    <p class="eyebrow">LAST SIX MONTHS</p>
                    <h2 class="title is-5">Recent harvests</h2>
                </div>
                <a href="<?= e(url('page=harvests')) ?>" class="button is-small is-light">
                    All harvest records
                </a>
            </div>

            <div class="table-container">
                <table class="table is-fullwidth is-hoverable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Crop / variety</th>
                            <th>Farm</th>
                            <th>Yield</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentHarvests as $harvest): ?>
                            <?php $harvestFilterUrl = url(http_build_query(['page' => 'harvests', 'filter' => ['harvest_id' => $harvest['harvest_id']]])); ?>
                            <tr>
                                <td><a href="<?= e($harvestFilterUrl) ?>"><?= e(date('M j, Y', strtotime($harvest['harvest_date']))) ?></a></td>
                                <td><a href="<?= e($harvestFilterUrl) ?>"><?= e($harvest['crop_display']) ?></a></td>
                                <td><a href="<?= e($harvestFilterUrl) ?>"><?= e($harvest['farm_name']) ?></a></td>
                                <td><a href="<?= e($harvestFilterUrl) ?>"><strong><?= number_format((float) $harvest['quantity_kg'], 2) ?> kg</strong></a></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$recentHarvests): ?>
                            <tr>
                                <td colspan="4" class="has-text-grey">
                                    No harvests recorded yet. Use “Log harvest” to add the first one.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= $renderDashboardPagination('recent_harvest_page', $recentHarvestPage, $recentHarvestPages) ?>
        </section>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
