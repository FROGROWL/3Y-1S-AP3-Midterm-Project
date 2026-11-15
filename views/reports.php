<?php
require __DIR__ . '/../includes/reports-data.php';
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR · BUSINESS INTELLIGENCE</p>
        <h1 class="title is-3">Performance reports</h1>
        <p class="subtitle is-6">Compare recorded results, expected production, quality, and cancelled cycles across a selected date range.</p>

        <?php require __DIR__ . '/reports/filters.php'; ?>
<?php if ($dateError): ?>
<?php require __DIR__ . '/reports/date-error.php'; ?>
<?php else: ?>
<?php require __DIR__ . '/reports/kpis.php'; ?>
<?php require __DIR__ . '/reports/chart.php'; ?>
<?php require __DIR__ . '/reports/table.php'; ?>
<?php endif; ?>

    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
