<?php
require __DIR__ . '/../includes/dashboard-data.php';
require __DIR__ . '/layout-top.php';
require __DIR__ . '/dashboard/hero.php';
?>
<section class="section dashboard-section">
    <div class="container">
        <?php require __DIR__ . '/dashboard/snapshot.php'; ?>
        <?php require __DIR__ . '/dashboard/field-activity.php'; ?>
        <?php require __DIR__ . '/dashboard/recent-audit.php'; ?>
        <?php require __DIR__ . '/dashboard/recent-harvests.php'; ?>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>