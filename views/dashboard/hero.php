<section class="hero is-small welcome">
    <div class="hero-body">
        <div class="container dashboard-hero">
            <div>
                <p class="eyebrow">AGRIINFO / <?= $isAdmin ? 'ADMINISTRATOR' : 'STAFF' ?> / DAILY OPERATIONS</p>
                <h1 class="title is-2"><?= $isAdmin ? 'Operations and administration, at a glance.' : 'Field operations, at a glance.' ?></h1>
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
