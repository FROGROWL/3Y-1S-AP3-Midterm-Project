
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