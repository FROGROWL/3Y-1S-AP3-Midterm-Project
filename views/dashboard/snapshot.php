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
                $displayLabel = $label === 'active plantings' ? 'Plantings' : ucwords($label);
                ?>
                <div class="column is-2">
                    <a class="box stat-card" href="<?= e(url('page=' . $target)) ?>">
                        <div class="stat-label"><?= e($displayLabel) ?></div>
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

        
