<?php
require __DIR__ . '/../includes/users-form-data.php';
require __DIR__ . '/../includes/users-actions.php';
require __DIR__ . '/../includes/users-list-data.php';
require __DIR__ . '/layout-top.php';
?><section class="section">
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
                    <div class="table-container"><table class="table is-fullwidth is-striped is-hoverable">
                        <thead><tr><th>Name / email</th><th>Role</th><th>Account</th><th>Current activity</th><th>Last login</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $account): ?>
                                <tr>
                                    <td><?= e($account['full_name']) ?><br><small><?= e($account['email']) ?></small></td>
                                    <td><?= e($account['role']) ?></td>
                                    <td><?= filter_var($account['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'Enabled' : 'Disabled' ?></td>
                                    <td><?= filter_var($account['is_online'], FILTER_VALIDATE_BOOLEAN) ? 'Active' : 'Inactive' ?><?php if ($account['last_activity_at']): ?><br><small>Last seen <?= e(date('M j, g:i A', strtotime($account['last_activity_at']))) ?></small><?php endif; ?></td>
                                    <td><?= $account['last_login_at'] ? e(date('M j, Y g:i A', strtotime($account['last_login_at']))) : 'Never' ?></td>
                                    <td class="actions">
                                        <a class="button is-small is-light" href="<?= e(url('page=users&edit=' . $account['user_id'])) ?>">Edit</a>
                                        <?php if ((int) $account['user_id'] !== (int) user()['user_id']): ?>
                                            <form method="post" data-app-confirm
                                                data-confirm-title="Delete user account?"
                                                data-confirm-message="Permanently delete <?= e($account['full_name']) ?> (<?= e($account['email']) ?>)?"
                                                data-confirm-impact="<?= number_format((int) $account['audit_log_count']) ?> audit log entries will be retained, but their user association will be cleared. This account's active sessions will lose access on their next request."
                                                data-confirm-label="Delete account">
                                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= e($account['user_id']) ?>">
                                                <input type="hidden" name="confirm_delete" value="0">
                                                <button class="button is-small is-danger is-light">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="tag is-light">You</span>
                                        <?php endif; ?>
                                    </td>
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
