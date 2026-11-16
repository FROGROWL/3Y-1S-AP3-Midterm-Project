<?php
$layoutTitle = 'Sign in';
require __DIR__ . '/layout-top.php';
?>
<main class="login-wrap">
    <div class="login-shell">
        <section class="login-intro" aria-labelledby="login-intro-title">
            <p class="eyebrow">AGRICULTURE INFORMATION SYSTEM</p>
            <h1 id="login-intro-title" class="title is-1">Your farm operations, in one place.</h1>
            <p class="login-intro-copy">
                AgriInfo helps your team keep farmer, farm, planting, and harvest records organized
                and easier to follow.
            </p>
            <div class="login-highlights" aria-label="What you can manage in AgriInfo">
                <article class="login-highlight">
                    <span class="login-highlight-number">01</span>
                    <div>
                        <h2>Farm records</h2>
                        <p>Keep farmer details, farm locations, and available area together.</p>
                    </div>
                </article>
                <article class="login-highlight">
                    <span class="login-highlight-number">02</span>
                    <div>
                        <h2>Planting schedules</h2>
                        <p>Track crop varieties, growing periods, planting status, and expected harvest dates.</p>
                    </div>
                </article>
                <article class="login-highlight">
                    <span class="login-highlight-number">03</span>
                    <div>
                        <h2>Harvest insights</h2>
                        <p>Record harvest quantities and review farm and crop performance.</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="login-card" aria-labelledby="login-form-title">
            <div class="login-actions">
                <button
                    class="button is-small theme-toggle"
                    type="button"
                    aria-label="Switch to dark theme"
                    aria-pressed="false"
                >
                    <span data-theme-icon class="theme-icon" aria-hidden="true">&#9790;</span>
                    <span data-theme-label>Dark theme</span>
                </button>
            </div>
            <div class="brand large">
                <img
                    class="brand-mark"
                    src="img/agri-info-icon.png?v=<?= (int) filemtime(__DIR__ . '/../img/agri-info-icon.png') ?>"
                    alt=""
                >
                <span>Agri<span class="brand-light">Info</span></span>
            </div>
            <p class="eyebrow mt-5">SECURE WORKSPACE</p>
            <h2 id="login-form-title" class="title is-3 mt-2">Sign in to AgriInfo</h2>
            <p class="has-text-grey mb-5">Enter your account details to open your role-based workspace.</p>

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
            <p class="login-access-note">Need an account or help signing in? Contact your system administrator.</p>

        </section>
    </div>
</main>
<?php require __DIR__ . '/layout-bottom.php'; ?>
