<?php
$layoutTitle = 'Overview';
$pdo = db();
$stats = [];
$tables = [
    'farmers' => 'farmer',
    'farms' => 'farm',
    'crops' => 'crop',
    'plantings' => 'planting',
    'harvests' => 'harvest',
];

foreach ($tables as $label => $table) {
    $stats[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$totalHarvest = (float) $pdo
    ->query('SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest')
    ->fetchColumn();
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
     LIMIT 6'
)->fetchAll();

require __DIR__ . '/layout-top.php';
?>
<section class="hero is-small welcome">
    <div class="hero-body">
        <div class="container">
            <p class="eyebrow">FIELD NOTES | <?= date('F j, Y') ?></p>
            <h1 class="title is-2">A clearer view of your fields.</h1>
            <p class="subtitle is-6">
                Track growers, farms, crop cycles, and harvests in one place.
            </p>
        </div>
    </div>
</section>

<section class="section pt-5">
    <div class="container">
        <div class="columns is-multiline dashboard-stats">
            <?php foreach ($stats as $label => $value): ?>
                <div class="column is-one-fifth">
                    <a class="box stat-card" href="<?= e(url('page=' . $label)) ?>">
                        <div class="stat-label"><?= e(ucfirst($label)) ?></div>
                        <div class="stat-number"><?= number_format($value) ?></div>
                        <div class="stat-foot">View records <span>&#8599;</span></div>
                    </a>
                </div>
            <?php endforeach; ?>

            <div class="column is-one-fifth">
                <a class="box stat-card harvest-stat" href="<?= e(url('page=reports')) ?>">
                    <div class="stat-label">Total harvest</div>
                    <div class="stat-number">
                        <?= number_format($totalHarvest, 0) ?> <small>kg</small>
                    </div>
                    <div class="stat-foot">View reports <span>&#8599;</span></div>
                </a>
            </div>
        </div>

        <div class="columns mt-2">
            <div class="column is-8">
                <div class="box table-card">
                    <div class="level">
                        <div>
                            <p class="eyebrow">LATEST ACTIVITY</p>
                            <h2 class="title is-5">Recent harvests</h2>
                        </div>
                        <a href="<?= e(url('page=harvests')) ?>" class="button is-small is-light">
                            All harvests
                        </a>
                    </div>

                    <div class="table-container">
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Crop</th>
                                    <th>Farm</th>
                                    <th>Yield</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentHarvests as $harvest): ?>
                                    <tr>
                                        <td><?= e($harvest['harvest_date']) ?></td>
                                        <td><?= e($harvest['crop_display']) ?></td>
                                        <td><?= e($harvest['farm_name']) ?></td>
                                        <td><?= number_format((float) $harvest['quantity_kg'], 2) ?> kg</td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$recentHarvests): ?>
                                    <tr>
                                        <td colspan="4" class="has-text-grey">
                                            Harvest records will appear here.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="column">
                <div class="box quick-box">
                    <p class="eyebrow">QUICK ACTIONS</p>
                    <h2 class="title is-5">Keep things moving</h2>
                    <?php
                    $quickLinks = [
                        'farmers' => 'Register a farmer',
                        'plantings' => 'Record a planting',
                        'harvests' => 'Add a harvest',
                        'reports' => 'Open reports',
                    ];
                    ?>
                    <?php foreach ($quickLinks as $target => $label): ?>
                        <a class="quick-link" href="<?= e(url('page=' . $target)) ?>">
                            <span><?= e($label) ?></span>
                            <span>&#8594;</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
