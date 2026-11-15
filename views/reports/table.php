<section class="box table-card">
                <div class="panel-heading-row"><div><p class="eyebrow">FILTERED RESULT TABLE</p><h2 class="title is-5"><?= e($dimensionLabel) ?></h2></div><span class="tag is-light"><?= count($rows) ?> groups</span></div>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped is-hoverable">
                        <thead><tr><th>Name</th><th>Harvests</th><th>Harvested kg</th><th>Avg kg/harvest</th><th>Gross value</th><th>Forecast kg</th><th>Forecast gross</th><th>Quality score</th><th>Cancelled</th></tr></thead>
                        <tbody>
                            <?php foreach ($tableRows as $row): ?>
                                <tr><th><?= e($row['label']) ?></th><td><?= number_format($row['harvest_count']) ?></td><td><?= number_format($row['total_kg'], 2) ?></td><td><?= number_format($row['average_kg'], 2) ?></td><td><?= number_format($row['gross_value'], 2) ?></td><td><?= number_format($row['forecast_kg'], 2) ?></td><td><?= number_format($row['forecast_gross'], 2) ?></td><td><?= number_format($row['quality_score'], 2) ?>/3</td><td><?= number_format($row['cancelled_count']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr><td colspan="9" class="has-text-centered">No report rows found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tableTotal > 10): ?>
                    <nav class="pagination is-right mt-4" aria-label="Filtered report pages">
                        <a class="pagination-previous" href="<?= e($previousTableUrl) ?>" <?= $tablePage <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>
                        <a class="pagination-next" href="<?= e($nextTableUrl) ?>" <?= $tablePage >= $tableTotalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
                        <span class="pagination-list">Page <?= $tablePage ?> of <?= $tableTotalPages ?> · <?= number_format($tableTotal) ?> groups</span>
                    </nav>
                <?php endif; ?>
                <p class="help">Projected kg uses the selected group’s recorded kg per planted hectare in this date range. Projected gross applies its average recorded selling price/kg. Cancelled cycles are grouped by their last updated date. Gross values use the selling prices entered on harvest records.</p>
            </section>
