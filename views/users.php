<?php
require_admin();
$layoutTitle = 'Users';
$pdo = db();
$roles = $pdo->query(
    'SELECT role_id, label FROM user_role ORDER BY sort_order, label'
)->fetchAll();
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$editingUser = null;
if ($editId) {
    $userQuery = $pdo->prepare(
        'SELECT app_user.user_id, app_user.full_name, app_user.email, app_user.role_id,
                app_user.is_active, user_role.label AS role
         FROM app_user JOIN user_role ON user_role.role_id = app_user.role_id
         WHERE app_user.user_id = ?'
    );
    $userQuery->execute([$editId]);
    $editingUser = $userQuery->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: null;
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);
    $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
    $validRole = false;
    if ($roleId !== false && $roleId !== null) {
        $roleCheck = $pdo->prepare('SELECT label FROM user_role WHERE role_id = ?');
        $roleCheck->execute([$roleId]);
        $roleLabel = $roleCheck->fetchColumn();
        $validRole = (bool) $roleLabel;
    }

    $passwordInvalid = $userId === null
        ? strlen($password) < 12
        : ($password !== '' && strlen($password) < 12);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$validRole || $passwordInvalid) {
        flash('danger', 'Enter a name, valid email and role. Passwords must be at least 12 characters.');
        go('page=users' . ($userId ? '&edit=' . $userId : ''));
    }
    if ($userId === (int) user()['user_id'] && ($roleLabel !== 'Administrator' || !$isActive)) {
        flash('danger', 'You cannot remove your own administrator access or deactivate your own account.');
        go('page=users&edit=' . $userId);
    }

    $pdo->beginTransaction();
    try {
        if ($userId) {
            $beforeQuery = $pdo->prepare(
                'SELECT app_user.user_id, app_user.full_name, app_user.email,
                        app_user.role_id, user_role.label AS role, app_user.is_active
                 FROM app_user JOIN user_role ON user_role.role_id = app_user.role_id
                 WHERE app_user.user_id = ?'
            );
            $beforeQuery->execute([$userId]);
            $before = $beforeQuery->fetch();
            if (!$before) {
                flash('danger', 'That user account no longer exists.');
                go('page=users');
            }
            $invalidateSessions = !$isActive || $password !== '';
            $sql = 'UPDATE app_user SET full_name = ?, email = ?, role_id = ?, is_active = ?,
                    signed_out_at = CASE WHEN ? THEN now() ELSE signed_out_at END';
            $values = [$name, $email, $roleId, $isActive ? 1 : 0, $invalidateSessions ? 1 : 0];
            if ($password !== '') {
                $sql .= ', password_hash = ?';
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ', updated_at = now() WHERE user_id = ?';
            $values[] = $userId;
            $pdo->prepare($sql)->execute($values);
            $afterQuery = $pdo->prepare(
                'SELECT app_user.user_id, app_user.full_name, app_user.email,
                        app_user.role_id, user_role.label AS role, app_user.is_active
                 FROM app_user JOIN user_role ON user_role.role_id = app_user.role_id
                 WHERE app_user.user_id = ?'
            );
            $afterQuery->execute([$userId]);
            audit('UPDATE', 'app_user', $userId, [
                'before' => $before,
                'after' => $afterQuery->fetch(),
                'password_reset' => $password !== '',
            ]);
            flash('success', 'User account updated.');
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO app_user (full_name, email, password_hash, role_id)
                 VALUES (?, ?, ?, ?) RETURNING user_id'
            );
            $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId]);
            $newUserId = (int) $insert->fetchColumn();
            audit('CREATE', 'app_user', $newUserId, [
                'after' => [
                    'full_name' => $name,
                    'email' => $email,
                    'role' => $roleLabel,
                    'is_active' => true,
                ],
            ]);
            flash('success', 'User account created.');
        }
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23505') {
            flash('danger', 'An account already uses that email address.');
            go('page=users' . ($userId ? '&edit=' . $userId : ''));
        }
        throw $exception;
    }
    go('page=users');
}

$users = $pdo->query(
    "SELECT app_user.user_id, app_user.full_name, app_user.email,
            user_role.label AS role, app_user.is_active, app_user.created_at,
            app_user.last_login_at, app_user.last_activity_at, app_user.signed_out_at,
            (app_user.is_active AND app_user.signed_out_at IS NULL
             AND app_user.last_activity_at >= now() - INTERVAL '8 hours') AS is_online
     FROM app_user
     JOIN user_role ON user_role.role_id = app_user.role_id
     ORDER BY app_user.user_id"
)->fetchAll();
require __DIR__ . '/layout-top.php';
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">ADMINISTRATOR ONLY</p>
        <h1 class="title is-3">User access</h1>
        <div class="columns is-variable is-5">
            <div class="column is-5">
                <div class="box">
                    <h2 class="title is-5"><?= $editingUser ? 'Edit user' : 'Create account' ?></h2>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="user_id" value="<?= e($editingUser['user_id'] ?? '') ?>">
                        <div class="field"><label class="label" for="full_name">Full name</label><input class="input" id="full_name" name="full_name" value="<?= e($editingUser['full_name'] ?? '') ?>" required></div>
                        <div class="field"><label class="label" for="email">Email</label><input class="input" type="email" id="email" name="email" value="<?= e($editingUser['email'] ?? '') ?>" required></div>
                        <div class="field"><label class="label" for="password"><?= $editingUser ? 'New password (optional)' : 'Temporary password' ?></label><input class="input" type="password" id="password" name="password" minlength="12" <?= $editingUser ? '' : 'required' ?> autocomplete="new-password"><p class="help">Passwords must contain at least 12 characters.</p></div>
                        <div class="field"><label class="label" for="role_id">Role</label><div class="select is-fullwidth"><select id="role_id" name="role_id" required><?php foreach ($roles as $role): ?><option value="<?= e($role['role_id']) ?>" <?= (string) ($editingUser['role_id'] ?? '') === (string) $role['role_id'] || (!$editingUser && $role['label'] === 'Staff') ? 'selected' : '' ?>><?= e($role['label']) ?></option><?php endforeach; ?></select></div></div>
                        <?php if ($editingUser): ?>
                        <div class="field"><label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= filter_var($editingUser['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' ?>> Account enabled</label></div>
                        <?php else: ?>
                            <input type="hidden" name="is_active" value="1">
                        <?php endif; ?>
                        <div class="buttons"><button class="button is-primary"><?= $editingUser ? 'Save changes' : 'Create user' ?></button><?php if ($editingUser): ?><a class="button is-light" href="<?= e(url('page=users')) ?>">Cancel</a><?php endif; ?></div>
                    </form>
                </div>
            </div>
            <div class="column">
                <div class="box table-card">
                    <div class="table-container"><table class="table is-fullwidth is-hoverable">
                        <thead><tr><th>Name / email</th><th>Role</th><th>Account</th><th>Current activity</th><th>Last login</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $account): ?>
                                <tr>
                                    <td><?= e($account['full_name']) ?><br><small><?= e($account['email']) ?></small></td>
                                    <td><?= e($account['role']) ?></td>
                                    <td><?= filter_var($account['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'Enabled' : 'Disabled' ?></td>
                                    <td><?= filter_var($account['is_online'], FILTER_VALIDATE_BOOLEAN) ? 'Active' : 'Inactive' ?><?php if ($account['last_activity_at']): ?><br><small>Last seen <?= e(date('M j, g:i A', strtotime($account['last_activity_at']))) ?></small><?php endif; ?></td>
                                    <td><?= $account['last_login_at'] ? e(date('M j, Y g:i A', strtotime($account['last_login_at']))) : 'Never' ?></td>
                                    <td><a class="button is-small is-light" href="<?= e(url('page=users&edit=' . $account['user_id'])) ?>">Edit</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$users): ?><tr><td colspan="6">No accounts found.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                    <p class="help">A user is marked active while the account is enabled, has not signed out, and had activity within the last eight hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout-bottom.php'; ?>
