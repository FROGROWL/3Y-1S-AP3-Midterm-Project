<?php
$users = $pdo->query(
    "SELECT app_user.user_id, app_user.full_name, app_user.email,
            user_role.code AS role, app_user.is_active, app_user.created_at,
            app_user.last_login_at, app_user.last_activity_at, app_user.signed_out_at,
            (SELECT COUNT(*) FROM audit_log WHERE audit_log.user_id = app_user.user_id) AS audit_log_count,
            (app_user.is_active AND app_user.signed_out_at IS NULL
             AND app_user.last_activity_at >= now() - INTERVAL '8 hours') AS is_online
     FROM app_user
     JOIN user_role ON user_role.role_id = app_user.role_id
     ORDER BY app_user.user_id"
)->fetchAll();
