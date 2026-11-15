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

            