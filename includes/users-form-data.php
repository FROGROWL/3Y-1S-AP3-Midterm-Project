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

