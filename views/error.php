<?php
$layoutTitle = 'Application error';
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <div class="notification is-danger app-notice" role="alert" data-app-notice data-notice-type="danger">
            <h1 class="title is-4">Could not load this page</h1>
            <p><?= e($message ?? 'Unexpected application error.') ?></p>
            <button class="delete" type="button" aria-label="Dismiss notification" data-notice-close></button>
        </div>
        <a class="button is-primary" href="<?= e(url()) ?>">Back to overview</a>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
