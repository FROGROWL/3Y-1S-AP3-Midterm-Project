<?php
$layoutTitle = 'Overview';
$pdo = db();
$stats = [];
$tables = [
    'farmers' => 'farmer',
    'farms' => 'farm',
    'crops' => 'crop',
    'harvests' => 'harvest',
];

foreach ($tables as $label => $table) {
    $stats[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$stats['active plantings'] = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     WHERE s.code IN ('planted', 'growing', 'ready')"
)->fetchColumn();
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

$harvestWatch = $pdo->query(
    "SELECT p.planting_id, p.expected_harvest_date, s.label AS status,
            c.crop_name, v.variety_name, f.farm_name
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE p.expected_harvest_date <= CURRENT_DATE + INTERVAL '30 days'
       AND s.code IN ('planted', 'growing', 'ready')
     ORDER BY p.expected_harvest_date, p.planting_id
     LIMIT 6"
)->fetchAll();

$recentHarvests = $pdo->query(
    'SELECT h.harvest_date, h.quantity_kg,
            c.crop_name || \' / \' || v.variety_name AS crop_display,
            f.farm_name
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     ORDER BY h.harvest_date DESC, h.harvest_id DESC
     LIMIT 8'
)->fetchAll();

require __DIR__ . '/layout-top.php';
?>
<section class="hero is-small welcome">
    <div class="hero-body">
        <div class="container dashboard-hero">
            <div>
                <p class="eyebrow">AGRIINFO / DAILY OPERATIONS</p>
                <h1 class="title is-2">Field operations, at a glance.</h1>
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
                <?php $target = $label === 'active plantings' ? 'plantings' : $label; ?>
                <div class="column is-2">
                    <a class="box stat-card" href="<?= e(url('page=' . $target)) ?>">
                        <div class="stat-label"><?= e(ucwords($label)) ?></div>
                        <div class="stat-number"><?= number_format($value) ?></div>
                        <div class="stat-foot">Open records <span aria-hidden="true">&#8599;</span></div>
                    </a>
                </div>
            <?php endforeach; ?>

            <div class="column is-2">
                <a class="box stat-card total-output" href="<?= e(url('page=reports')) ?>">
                    <div class="stat-label">Lifetime output</div>
                    <div class="stat-number">
                        <?= number_format($totalHarvest, 0) ?> <small>kg</small>
                    </div>
                    <div class="stat-foot">Open reports <span aria-hidden="true">&#8599;</span></div>
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
                    <p class="eyebrow">OVERDUE + NEXT 30 DAYS</p>
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
                                    href="<?= e(url('page=plantings&edit=' . $planting['planting_id'])) ?>"
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
                    <?php else: ?>
                        <p class="panel-empty">
                            No active plantings are expected to harvest in the next 30 days.
                        </p>
                    <?php endif; ?>
                    <a class="panel-link" href="<?= e(url('page=plantings')) ?>">
                        View all plantings <span aria-hidden="true">&#8594;</span>
                    </a>
                </section>
            </div>

            <div class="column is-4">
                <section class="box dashboard-panel quick-box">
                    <p class="eyebrow">SHORTCUTS</p>
                    <h3 class="title is-5">Common tasks</h3>
                    <?php
                    $quickLinks = [
                        ['farmers', 'Register a farmer'],
                        ['farms', 'Add a farm'],
                        ['plantings', 'Record a planting'],
                        ['harvests', 'Log a harvest'],
                    ];
                    ?>
                    <div class="quick-actions-grid">
                        <?php foreach ($quickLinks as [$target, $label]): ?>
                            <a class="quick-action" href="<?= e(url('page=' . $target)) ?>">
                                <span><?= e($label) ?></span>
                                <span aria-hidden="true">+</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a class="panel-link" href="<?= e(url('page=reports')) ?>">
                        Open reports <span aria-hidden="true">&#8594;</span>
                    </a>
                </section>
            </div>
        </div>

        <section class="box table-card recent-harvest-panel">
            <div class="panel-heading-row">
                <div>
                    <p class="eyebrow">LATEST ACTIVITY</p>
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
                            <tr>
                                <td><?= e(date('M j, Y', strtotime($harvest['harvest_date']))) ?></td>
                                <td><?= e($harvest['crop_display']) ?></td>
                                <td><?= e($harvest['farm_name']) ?></td>
                                <td><strong><?= number_format((float) $harvest['quantity_kg'], 2) ?> kg</strong></td>
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
        </section>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
