<?php
$layoutTitle = 'Sign in';
require __DIR__ . '/layout-top.php';
?>
<main class="login-wrap">
    <section class="login-card">
        <div class="brand large">
            <span class="brand-mark">A</span>
            <span>Agri<span class="brand-light">Info</span></span>
        </div>
        <p class="eyebrow mt-5">AGRICULTURE INFORMATION SYSTEM</p>
        <h1 class="title is-3 mt-2">Welcome back</h1>
        <p class="has-text-grey mb-5">Sign in to manage your agricultural records.</p>

        <?php if ($notice = $_SESSION['flash'] ?? null): ?>
            <?php unset($_SESSION['flash']); ?>
            <div class="notification is-<?= e($notice[0]) ?> is-light">
                <?= e($notice[1]) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <div class="field">
                <label class="label" for="email">Email</label>
                <input
                    class="input"
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="username"
                    required
                >
            </div>
            <div class="field">
                <label class="label" for="password">Password</label>
                <input
                    class="input"
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>
            <button class="button is-primary is-fullwidth mt-3">Sign in</button>
        </form>

        <p class="is-size-7 has-text-grey mt-5">
            Administrator accounts are created by the installation script.
        </p>
    </section>
</main>
<?php require __DIR__ . '/layout-bottom.php'; ?>
