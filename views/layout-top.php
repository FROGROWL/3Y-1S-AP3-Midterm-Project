<?php
$title = $layoutTitle ?? 'AgriInfo';
$currentUser = user();
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | <?= e(envv('APP_NAME', 'AgriInfo')) ?></title>
    <meta name="theme-color" content="#f7f8f2">
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bulma@1.0.4/css/bulma.min.css"
    >
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<?php if ($currentUser): ?>
    <nav class="navbar app-nav">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item brand" href="<?= e(url()) ?>">
                    <span class="brand-mark">A</span>
                    <span>Agri<span class="brand-light">Info</span></span>
                </a>
                <a
                    role="button"
                    class="navbar-burger"
                    aria-label="menu"
                    aria-expanded="false"
                    data-target="mainNav"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </a>
            </div>

            <div id="mainNav" class="navbar-menu">
                <div class="navbar-start">
                    <a class="navbar-item" href="<?= e(url()) ?>">Overview</a>
                    <?php
                    $navigation = [
                        'farmers' => 'Farmers',
                        'farms' => 'Farms',
                        'crops' => 'Crops',
                        'plantings' => 'Plantings',
                        'harvests' => 'Harvests',
                        'reports' => 'Reports',
                    ];
                    ?>
                    <?php foreach ($navigation as $pageKey => $label): ?>
                        <a class="navbar-item" href="<?= e(url('page=' . $pageKey)) ?>">
                            <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if (($currentUser['role'] ?? null) === 'Administrator'): ?>
                        <a class="navbar-item" href="<?= e(url('page=users')) ?>">Users</a>
                    <?php endif; ?>
                </div>

                <div class="navbar-end">
                    <div class="navbar-item user-chip">
                        <span><?= e($currentUser['full_name'] ?? '') ?></span>
                        <span class="tag is-light"><?= e($currentUser['role'] ?? 'Staff') ?></span>
                    </div>
                    <div class="navbar-item">
                        <form method="post" action="<?= e(url('page=logout')) ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <button class="button is-small is-light">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="container notice">
        <div class="notification is-<?= e($notice[0]) ?> is-light">
            <?= e($notice[1]) ?>
        </div>
    </div>
<?php endif; ?>
