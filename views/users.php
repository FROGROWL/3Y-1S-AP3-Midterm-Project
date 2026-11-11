<?php
require_admin();
$layoutTitle = 'Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'Staff';
    $validRole = in_array($role, ['Administrator', 'Staff'], true);

    if (
        $name === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 12
        || !$validRole
    ) {
        flash(
            'danger',
            'Enter a name, valid email, role, and password of at least 12 characters.'
        );
        go('page=users');
    }

    $statement = db()->prepare(
        'INSERT INTO app_user (full_name, email, password_hash, role)
         VALUES (?, ?, ?, ?)'
    );
    $statement->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
    ]);

    audit('CREATE', 'app_user', (int) db()->lastInsertId());
    flash('success', 'User account created.');
    go('page=users');
}

$users = db()->query(
    'SELECT user_id, full_name, email, role, is_active, created_at
     FROM app_user
     ORDER BY user_id'
)->fetchAll();

require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <h1 class="title is-3">User access</h1>

        <div class="columns">
            <div class="column is-5">
                <div class="box">
                    <h2 class="title is-5">Create account</h2>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <?php
                        $userFields = [
                            'full_name' => ['Full name', 'text'],
                            'email' => ['Email', 'email'],
                            'password' => ['Temporary password', 'password'],
                        ];
                        ?>
                        <?php foreach ($userFields as $key => [$label, $type]): ?>
                            <div class="field">
                                <label class="label" for="<?= e($key) ?>">
                                    <?= e($label) ?>
                                </label>
                                <input
                                    class="input"
                                    id="<?= e($key) ?>"
                                    name="<?= e($key) ?>"
                                    type="<?= e($type) ?>"
                                    <?= $key === 'password' ? 'minlength="12"' : '' ?>
                                    required
                                >
                            </div>
                        <?php endforeach; ?>

                        <div class="field">
                            <label class="label" for="role">Role</label>
                            <div class="select">
                                <select id="role" name="role">
                                    <option>Staff</option>
                                    <option>Administrator</option>
                                </select>
                            </div>
                        </div>
                        <button class="button is-primary">Create user</button>
                    </form>
                </div>
            </div>

            <div class="column">
                <div class="box table-card">
                    <div class="table-container">
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $account): ?>
                                    <tr>
                                        <td><?= e($account['full_name']) ?></td>
                                        <td><?= e($account['email']) ?></td>
                                        <td><?= e($account['role']) ?></td>
                                        <td><?= $account['is_active'] ? 'Yes' : 'No' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
