<?php
declare(strict_types=1);
function user(): ?array
{
    $sessionUser = $_SESSION['user'] ?? null;

    return is_array($sessionUser) ? $sessionUser : null;
}

function require_login(): void
{
    $sessionUser = user();
    $hasRequiredFields = $sessionUser
        && isset(
            $sessionUser['user_id'],
            $sessionUser['full_name'],
            $sessionUser['email'],
            $sessionUser['role']
        );
    $hasValidRole = $hasRequiredFields
        && in_array($sessionUser['role'], ['Administrator', 'Staff'], true);

    if (!$hasValidRole) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        flash('info', 'Your sign-in session expired. Please sign in again.');
        go('page=login');
    }

    $lastActivity = (int) ($_SESSION['last_activity_at'] ?? time());
    if (time() - $lastActivity >= 8 * 60 * 60) {
        db()->prepare(
            'UPDATE app_user SET signed_out_at = now()
             WHERE user_id = ? AND signed_out_at IS NULL'
        )->execute([(int) $sessionUser['user_id']]);
        audit('LOGOUT', 'app_user', (int) $sessionUser['user_id'], [
            'reason' => 'Eight hours of inactivity',
        ]);
        unset($_SESSION['user'], $_SESSION['last_activity_at']);
        session_regenerate_id(true);
        flash('info', 'You were signed out after eight hours of inactivity. Please sign in again.');
        go('page=login');
    }

    $currentAccount = db()->prepare(
        'SELECT app_user.full_name, app_user.email, app_user.is_active,
                app_user.signed_out_at, user_role.code AS role
         FROM app_user JOIN user_role ON user_role.role_id = app_user.role_id
         WHERE app_user.user_id = ?'
    );
    $currentAccount->execute([(int) $sessionUser['user_id']]);
    $account = $currentAccount->fetch();

    $loginStartedAt = (int) ($_SESSION['auth_started_at'] ?? $lastActivity);
    $signedOutAt = $account['signed_out_at'] ?? null;
    $sessionInvalidated = $signedOutAt
        && strtotime((string) $signedOutAt) >= $loginStartedAt;
    if (!$account || !filter_var($account['is_active'], FILTER_VALIDATE_BOOLEAN) || $sessionInvalidated) {
        db()->prepare(
            'UPDATE app_user SET signed_out_at = now()
             WHERE user_id = ? AND signed_out_at IS NULL'
        )->execute([(int) $sessionUser['user_id']]);
        audit('LOGOUT', 'app_user', (int) $sessionUser['user_id'], [
            'reason' => $sessionInvalidated ? 'Session ended by administrator' : 'Account disabled',
        ]);
        unset($_SESSION['user'], $_SESSION['last_activity_at']);
        session_regenerate_id(true);
        flash('danger', 'This account is inactive. Contact an administrator.');
        go('page=login');
    }

    unset($account['signed_out_at']);
    $_SESSION['user'] = array_merge($sessionUser, $account);
    $_SESSION['last_activity_at'] = time();
    db()->prepare(
        'UPDATE app_user SET last_activity_at = now()
         WHERE user_id = ? AND is_active'
    )->execute([(int) $sessionUser['user_id']]);
}

function require_admin(): void
{
    require_login();

    if (user()['role'] !== 'Administrator') {
        http_response_code(403);
        exit('Administrator access required.');
    }
}

