<?php
$layoutTitle = 'Reports';
$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$statement = db()->prepare(
    'SELECT c.crop_name,
            COUNT(h.harvest_id) AS harvest_count,
            COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
            COALESCE(AVG(h.quantity_kg), 0) AS avg_kg,
            COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg), 0) AS gross_value
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     WHERE h.harvest_date BETWEEN ? AND ?
     GROUP BY c.crop_id, c.crop_name
     ORDER BY total_kg DESC'
);
$statement->execute([$from, $to]);
$rows = $statement->fetchAll();

require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ANALYSIS</p>
        <h1 class="title is-3">Agricultural reports</h1>

        <div class="box">
            <form class="field is-grouped is-align-items-flex-end">
                <input type="hidden" name="page" value="reports">
                <div class="control">
                    <label class="label" for="from">From</label>
                    <input class="input" id="from" type="date" name="from" value="<?= e($from) ?>">
                </div>
                <div class="control">
                    <label class="label" for="to">To</label>
                    <input class="input" id="to" type="date" name="to" value="<?= e($to) ?>">
                </div>
                <div class="control">
                    <button class="button is-primary">Apply period</button>
                </div>
                <div class="control">
                    <button type="button" class="button is-light" onclick="window.print()">
                        Print report
                    </button>
                </div>
            </form>

            <div class="table-container">
                <table class="table is-fullwidth is-striped">
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Harvests</th>
                            <th>Total yield (kg)</th>
                            <th>Average (kg)</th>
                            <th>Gross value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['crop_name']) ?></td>
                                <td><?= e($row['harvest_count']) ?></td>
                                <td><?= number_format((float) $row['total_kg'], 2) ?></td>
                                <td><?= number_format((float) $row['avg_kg'], 2) ?></td>
                                <td><?= number_format((float) $row['gross_value'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5" class="has-text-grey">
                                    No harvests in this date range.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="help">
                Gross value is computed from recorded quantity and price per kilogram.
            </p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
