<?php
require __DIR__ . '/layout-top.php';
?>
<section class="section pt-5">
    <div class="container">
        <div class="level">
            <div>
                <p class="eyebrow">DATA MANAGEMENT</p>
                <h1 class="title is-3"><?= e($module['title']) ?></h1>
                <p class="subtitle is-6">Create, filter, update, and remove records.</p>
            </div>
        </div>

        <div class="columns is-variable is-5 module-layout">
            <?php require __DIR__ . '/modules/form.php'; ?>
            <?php require __DIR__ . '/modules/list.php'; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
