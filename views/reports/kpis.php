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

            