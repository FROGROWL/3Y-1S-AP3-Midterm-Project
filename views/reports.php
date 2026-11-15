<?php
require_admin();
$layoutTitle = 'Business intelligence reports';
$pdo = db();
$todayDate = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$today = $todayDate->format('Y-m-d');
$defaultTo = $todayDate->modify('+90 days')->format('Y-m-d');
$from = (string) ($_GET['from'] ?? substr($today, 0, 4) . '-01-01');
$to = (string) ($_GET['to'] ?? $defaultTo);
$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$dateError = !$validDate($from) || !$validDate($to) || $from > $to;
$scope = in_array($_GET['scope'] ?? 'farms', ['farms', 'crops'], true)
    ? (string) ($_GET['scope'] ?? 'farms')
    : 'farms';
$groupBy = ($scope === 'crops' && ($_GET['group_by'] ?? '') === 'variety')
    ? 'variety'
    : 'crop';
$metrics = [
    'gross_value' => 'Recorded gross value',
    'forecast_gross' => 'Projected gross value',
    'total_kg' => 'Harvested quantity (kg)',
    'forecast_kg' => 'Projected quantity (kg)',
    'average_kg' => 'Average kg per harvest',
    'harvest_count' => 'Harvest count',
    'quality_score' => 'Average quality score (A=3)',
    'cancelled_count' => 'Cancelled plantings',
];
$metric = isset($metrics[$_GET['metric'] ?? '']) ? (string) $_GET['metric'] : 'gross_value';
$requestedOrder = (string) ($_GET['order_by'] ?? $metric);
$orderBy = $requestedOrder === 'label'
    ? 'label'
    : (isset($metrics[$requestedOrder]) ? $requestedOrder : $metric);
$direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$farmId = filter_var($_GET['farm_id'] ?? '', FILTER_VALIDATE_INT);
$cropId = filter_var($_GET['crop_id'] ?? '', FILTER_VALIDATE_INT);
$varietyId = filter_var($_GET['variety_id'] ?? '', FILTER_VALIDATE_INT);
$gradeId = filter_var($_GET['quality_grade_id'] ?? '', FILTER_VALIDATE_INT);
$farmId = $farmId && $farmId > 0 ? $farmId : null;
$cropId = $cropId && $cropId > 0 ? $cropId : null;
$varietyId = $varietyId && $varietyId > 0 ? $varietyId : null;
$gradeId = $gradeId && $gradeId > 0 ? $gradeId : null;
$farms = $pdo->query('SELECT farm_id, farm_name FROM farm ORDER BY farm_name')->fetchAll();
$crops = $pdo->query('SELECT crop_id, crop_name FROM crop ORDER BY crop_name')->fetchAll();
$varieties = $pdo->query(
    'SELECT variety.variety_id, variety.variety_name, crop.crop_name
     FROM crop_variety variety JOIN crop ON crop.crop_id = variety.crop_id
     ORDER BY crop.crop_name, variety.variety_name'
)->fetchAll();
$grades = $pdo->query(
    'SELECT quality_grade_id, label FROM quality_grade ORDER BY sort_order, label'
)->fetchAll();

$dimensions = [
    'farms' => [
        'id' => 'f.farm_id',
        'label' => 'f.farm_name',
        'group' => 'f.farm_id, f.farm_name',
    ],
    'crops' => [
        'id' => $groupBy === 'variety' ? 'v.variety_id' : 'c.crop_id',
        'label' => $groupBy === 'variety'
            ? "c.crop_name || ' / ' || v.variety_name"
            : 'c.crop_name',
        'group' => $groupBy === 'variety'
            ? 'v.variety_id, c.crop_name, v.variety_name'
            : 'c.crop_id, c.crop_name',
    ],
];
$dimension = $dimensions[$scope];
$joins = 'JOIN farm f ON f.farm_id = p.farm_id
          JOIN crop_variety v ON v.variety_id = p.variety_id
          JOIN crop c ON c.crop_id = v.crop_id';
$filterSql = '';
$filterParams = [];
foreach ([
    ['f.farm_id', $farmId],
    ['c.crop_id', $cropId],
    ['v.variety_id', $varietyId],
] as [$column, $value]) {
    if ($value !== null) {
        $filterSql .= ' AND ' . $column . ' = ?';
        $filterParams[] = $value;
    }
}
$actualFilterSql = $filterSql;
$actualFilterParams = $filterParams;
if ($gradeId !== null) {
    $actualFilterSql .= ' AND h.quality_grade_id = ?';
    $actualFilterParams[] = $gradeId;
}

$results = [];
$totalHarvests = 0;
$totalKg = 0.0;
$grossValue = 0.0;
$projectedKg = 0.0;
$projectedGross = 0.0;
$cancelledPlantings = 0;

if (!$dateError) {
    $actual = $pdo->prepare(
        "SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                COUNT(h.harvest_id) AS harvest_count,
                COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
                COALESCE(AVG(h.quantity_kg), 0) AS average_kg,
                COALESCE(SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)), 0) AS gross_value,
                COALESCE(AVG(CASE grade.code
                    WHEN 'grade_a' THEN 3.0 WHEN 'grade_b' THEN 2.0
                    WHEN 'grade_c' THEN 1.0 ELSE NULL END), 0) AS quality_score
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         {$joins}
         LEFT JOIN quality_grade grade ON grade.quality_grade_id = h.quality_grade_id
         WHERE h.harvest_date BETWEEN ? AND ? {$actualFilterSql}
         GROUP BY {$dimension['group']}"
    );
    $actual->execute(array_merge([$from, $to], $actualFilterParams));
    foreach ($actual->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] = array_merge([
            'group_id' => $row['group_id'],
            'label' => $row['label'],
            'harvest_count' => 0,
            'total_kg' => 0.0,
            'average_kg' => 0.0,
            'gross_value' => 0.0,
            'quality_score' => 0.0,
            'cancelled_count' => 0,
            'forecast_kg' => 0.0,
            'forecast_gross' => 0.0,
        ], $row);
    }

    $cancelled = $pdo->prepare(
        "SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                COUNT(*) AS cancelled_count
         FROM planting p {$joins}
         JOIN planting_status status ON status.status_id = p.status_id
         WHERE status.code = 'cancelled'
           AND p.updated_at::date BETWEEN ? AND ? {$filterSql}
         GROUP BY {$dimension['group']}"
    );
    $cancelled->execute(array_merge([$from, $to], $filterParams));
    foreach ($cancelled->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] ??= [
            'group_id' => $row['group_id'], 'label' => $row['label'],
            'harvest_count' => 0, 'total_kg' => 0.0, 'average_kg' => 0.0,
            'gross_value' => 0.0, 'quality_score' => 0.0,
            'cancelled_count' => 0, 'forecast_kg' => 0.0, 'forecast_gross' => 0.0,
        ];
        $results[$key]['cancelled_count'] = (int) $row['cancelled_count'];
    }

    $forecast = $pdo->prepare(
        "WITH planting_history AS (
            SELECT {$dimension['id']} AS group_id,
                   p.planting_id,
                   MAX(p.area_planted) AS area_planted,
                   SUM(h.quantity_kg) AS harvested_kg,
                   SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)) AS gross_value
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            {$joins}
            LEFT JOIN quality_grade grade ON grade.quality_grade_id = h.quality_grade_id
            WHERE h.harvest_date BETWEEN ? AND ? {$actualFilterSql}
            GROUP BY {$dimension['id']}, p.planting_id
         ), history AS (
            SELECT group_id,
                   SUM(harvested_kg) / NULLIF(SUM(area_planted), 0) AS kg_per_hectare,
                   SUM(gross_value) / NULLIF(SUM(harvested_kg), 0) AS average_price
            FROM planting_history GROUP BY group_id
         ), expected AS (
            SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                   SUM(p.area_planted) AS forecast_area
            FROM planting p {$joins}
            JOIN planting_status status ON status.status_id = p.status_id
            WHERE status.code IN ('planting', 'growing', 'ready')
              AND p.expected_harvest_date > CURRENT_DATE
              AND p.expected_harvest_date BETWEEN ? AND ? {$filterSql}
            GROUP BY {$dimension['group']}
         )
         SELECT expected.group_id, expected.label,
                expected.forecast_area * COALESCE(history.kg_per_hectare, 0) AS forecast_kg,
                expected.forecast_area * COALESCE(history.kg_per_hectare, 0)
                    * COALESCE(history.average_price, 0) AS forecast_gross
         FROM expected LEFT JOIN history USING (group_id)"
    );
    $forecast->execute(array_merge(
        [$from, $to], $actualFilterParams,
        [$from, $to], $filterParams
    ));
    foreach ($forecast->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] ??= [
            'group_id' => $row['group_id'], 'label' => $row['label'],
            'harvest_count' => 0, 'total_kg' => 0.0, 'average_kg' => 0.0,
            'gross_value' => 0.0, 'quality_score' => 0.0,
            'cancelled_count' => 0, 'forecast_kg' => 0.0, 'forecast_gross' => 0.0,
        ];
        $results[$key]['forecast_kg'] = (float) $row['forecast_kg'];
        $results[$key]['forecast_gross'] = (float) $row['forecast_gross'];
    }

    foreach ($results as &$result) {
        foreach (['harvest_count', 'cancelled_count'] as $key) {
            $result[$key] = (int) ($result[$key] ?? 0);
        }
        foreach (['total_kg', 'average_kg', 'gross_value', 'quality_score', 'forecast_kg', 'forecast_gross'] as $key) {
            $result[$key] = (float) ($result[$key] ?? 0);
        }
        $totalHarvests += $result['harvest_count'];
        $totalKg += $result['total_kg'];
        $grossValue += $result['gross_value'];
        $projectedKg += $result['forecast_kg'];
        $projectedGross += $result['forecast_gross'];
        $cancelledPlantings += $result['cancelled_count'];
    }
    unset($result);
}

$rows = array_values($results);
usort($rows, static function (array $left, array $right) use ($orderBy, $direction): int {
    $comparison = $orderBy === 'label'
        ? strcasecmp((string) $left['label'], (string) $right['label'])
        : (($left[$orderBy] ?? 0) <=> ($right[$orderBy] ?? 0));
    return $direction === 'asc' ? $comparison : -$comparison;
});
$metricLabel = $metrics[$metric];
$maxValue = max(array_map(static fn (array $row): float => max(0, (float) ($row[$metric] ?? 0)), $rows) ?: [0]);
$dimensionLabel = $scope === 'farms' ? 'Farm performance' : ($groupBy === 'variety' ? 'Crop variety performance' : 'Crop performance');
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR · BUSINESS INTELLIGENCE</p>
        <h1 class="title is-3">Performance reports</h1>
        <p class="subtitle is-6">Compare recorded results, expected production, quality, and cancelled cycles across a selected date range.</p>

        <form method="get" class="box report-filters">
            <input type="hidden" name="page" value="reports">
            <div class="columns is-multiline">
                <div class="column is-3"><label class="label" for="from">Date from</label><input class="input" type="date" id="from" name="from" value="<?= e($from) ?>" required></div>
                <div class="column is-3"><label class="label" for="to">Date to</label><input class="input" type="date" id="to" name="to" value="<?= e($to) ?>" required></div>
                <div class="column is-3"><label class="label" for="scope">Performance focus</label><div class="select is-fullwidth"><select id="scope" name="scope"><option value="farms" <?= $scope === 'farms' ? 'selected' : '' ?>>Farms</option><option value="crops" <?= $scope === 'crops' ? 'selected' : '' ?>>Crops</option></select></div></div>
                <div class="column is-3"><label class="label" for="group_by">Crop detail</label><div class="select is-fullwidth"><select id="group_by" name="group_by"><option value="crop" <?= $groupBy === 'crop' ? 'selected' : '' ?>>Group by crop</option><option value="variety" <?= $groupBy === 'variety' ? 'selected' : '' ?>>Break down by variety</option></select></div></div>
                <div class="column is-3"><label class="label" for="metric">Chart metric</label><div class="select is-fullwidth"><select id="metric" name="metric"><?php foreach ($metrics as $key => $label): ?><option value="<?= e($key) ?>" <?= $metric === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="farm_id">Farm filter</label><div class="select is-fullwidth"><select id="farm_id" name="farm_id"><option value="">All farms</option><?php foreach ($farms as $farm): ?><option value="<?= e($farm['farm_id']) ?>" <?= (string) $farmId === (string) $farm['farm_id'] ? 'selected' : '' ?>><?= e($farm['farm_name']) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="crop_id">Crop filter</label><div class="select is-fullwidth"><select id="crop_id" name="crop_id"><option value="">All crops</option><?php foreach ($crops as $crop): ?><option value="<?= e($crop['crop_id']) ?>" <?= (string) $cropId === (string) $crop['crop_id'] ? 'selected' : '' ?>><?= e($crop['crop_name']) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="variety_id">Variety filter</label><div class="select is-fullwidth"><select id="variety_id" name="variety_id"><option value="">All varieties</option><?php foreach ($varieties as $variety): ?><option value="<?= e($variety['variety_id']) ?>" <?= (string) $varietyId === (string) $variety['variety_id'] ? 'selected' : '' ?>><?= e($variety['crop_name'] . ' / ' . $variety['variety_name']) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="quality_grade_id">Harvest quality</label><div class="select is-fullwidth"><select id="quality_grade_id" name="quality_grade_id"><option value="">All grades</option><?php foreach ($grades as $grade): ?><option value="<?= e($grade['quality_grade_id']) ?>" <?= (string) $gradeId === (string) $grade['quality_grade_id'] ? 'selected' : '' ?>><?= e($grade['label']) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="order_by">Table order by</label><div class="select is-fullwidth"><select id="order_by" name="order_by"><option value="label" <?= $orderBy === 'label' ? 'selected' : '' ?>>Name</option><?php foreach ($metrics as $key => $label): ?><option value="<?= e($key) ?>" <?= $orderBy === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div></div>
                <div class="column is-3"><label class="label" for="direction">Table order</label><div class="select is-fullwidth"><select id="direction" name="direction"><option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>Descending</option><option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Ascending</option></select></div></div>
                <div class="column is-3 is-flex is-align-items-flex-end"><button class="button is-primary is-fullwidth">Run analysis</button></div>
            </div>
        </form>

        <?php if ($dateError): ?>
            <div class="notification is-danger is-light">Enter a valid date range where the start date is before or equal to the end date.</div>
        <?php else: ?>
            <div class="columns is-multiline dashboard-stats report-kpis">
                <?php foreach ([
                    'Harvests recorded' => number_format($totalHarvests),
                    'Yield harvested' => number_format($totalKg, 2) . ' kg',
                    'Gross value' => number_format($grossValue, 2),
                    'Expected yield' => number_format($projectedKg, 2) . ' kg',
                    'Expected gross' => number_format($projectedGross, 2),
                    'Cancelled cycles' => number_format($cancelledPlantings),
                ] as $label => $value): ?>
                    <div class="column is-2"><div class="box stat-card"><div class="stat-label"><?= e($label) ?></div><div class="stat-number report-kpi-value"><?= e($value) ?></div></div></div>
                <?php endforeach; ?>
            </div>

            <section class="box report-chart-panel">
                <div class="panel-heading-row"><div><p class="eyebrow"><?= e($dimensionLabel) ?></p><h2 class="title is-5"><?= e($metricLabel) ?></h2></div><span class="tag is-warning is-light"><?= e($from) ?> to <?= e($to) ?></span></div>
                <?php if ($rows): ?>
                    <div class="performance-chart" role="img" aria-label="Bar chart of <?= e($metricLabel) ?> by <?= e(strtolower($dimensionLabel)) ?>">
                        <?php foreach (array_slice($rows, 0, 12) as $row): ?>
                            <?php $value = max(0, (float) ($row[$metric] ?? 0)); $width = $maxValue > 0 ? ($value / $maxValue * 100) : 0; ?>
                            <div class="performance-chart-row"><div class="performance-chart-label" title="<?= e($row['label']) ?>"><?= e($row['label']) ?></div><div class="performance-chart-track"><span style="width: <?= number_format($width, 2, '.', '') ?>%"></span></div><strong><?= number_format($value, 2) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="panel-empty">No results match these filters and dates.</p>
                <?php endif; ?>
            </section>

            <section class="box table-card">
                <div class="panel-heading-row"><div><p class="eyebrow">FILTERED RESULT TABLE</p><h2 class="title is-5"><?= e($dimensionLabel) ?></h2></div><span class="tag is-light"><?= count($rows) ?> groups</span></div>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped is-hoverable">
                        <thead><tr><th>Name</th><th>Harvests</th><th>Harvested kg</th><th>Avg kg/harvest</th><th>Gross value</th><th>Forecast kg</th><th>Forecast gross</th><th>Quality score</th><th>Cancelled</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr><th><?= e($row['label']) ?></th><td><?= number_format($row['harvest_count']) ?></td><td><?= number_format($row['total_kg'], 2) ?></td><td><?= number_format($row['average_kg'], 2) ?></td><td><?= number_format($row['gross_value'], 2) ?></td><td><?= number_format($row['forecast_kg'], 2) ?></td><td><?= number_format($row['forecast_gross'], 2) ?></td><td><?= number_format($row['quality_score'], 2) ?>/3</td><td><?= number_format($row['cancelled_count']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr><td colspan="9" class="has-text-centered">No report rows found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="help">Projected kg uses the selected group’s recorded kg per planted hectare in this date range. Projected gross applies its average recorded selling price/kg. Cancelled cycles are grouped by their last updated date. Gross values use the selling prices entered on harvest records.</p>
            </section>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
