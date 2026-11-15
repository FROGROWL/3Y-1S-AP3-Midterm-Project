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
                                            Mark <?= $planting['code'] === 'Planting' ? 'Growing' : 'Ready to harvest' ?>
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

        