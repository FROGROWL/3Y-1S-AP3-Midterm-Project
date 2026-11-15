<?php
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
        $roleCheck = $pdo->prepare('SELECT code FROM user_role WHERE role_id = ?');
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
                        app_user.role_id, user_role.code AS role, app_user.is_active,
                        app_user.created_at, app_user.updated_at, app_user.last_login_at
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
                        app_user.role_id, user_role.code AS role, app_user.is_active,
                        app_user.created_at, app_user.updated_at, app_user.last_login_at
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
            $createdQuery = $pdo->prepare(
                'SELECT app_user.user_id, app_user.full_name, app_user.email,
                        user_role.code AS role, app_user.is_active, app_user.created_at,
                        app_user.updated_at, app_user.last_login_at
                 FROM app_user JOIN user_role ON user_role.role_id = app_user.role_id
                 WHERE app_user.user_id = ?'
            );
            $createdQuery->execute([$newUserId]);
            audit('CREATE', 'app_user', $newUserId, [
                'after' => $createdQuery->fetch(),
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

