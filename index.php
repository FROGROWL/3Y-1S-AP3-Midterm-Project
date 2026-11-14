<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';

try {
    if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

        $statement = db()->prepare(
            'SELECT app_user.user_id, app_user.full_name, app_user.email,
                    app_user.password_hash, user_role.label AS role, app_user.is_active
             FROM app_user
             JOIN user_role ON user_role.role_id = app_user.role_id
             WHERE lower(app_user.email) = lower(?)'
        );
        $statement->execute([trim($_POST['email'] ?? '')]);
        $account = $statement->fetch();

        if (
            $account
            && $account['is_active']
            && password_verify((string) ($_POST['password'] ?? ''), $account['password_hash'])
        ) {
            session_regenerate_id(true);
            unset($account['password_hash']);
            $_SESSION['user'] = $account;

            flash('success', 'Welcome back, ' . $account['full_name'] . '.');
            go('page=dashboard');
        }

        flash('danger', 'Email or password is incorrect.');
        go('page=login');
    }

    if ($page === 'logout') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Use the sign-out form.');
        }

        check_csrf();
        session_destroy();
        go('page=login');
    }

    if ($page === 'login') {
        require __DIR__ . '/views/login.php';
        exit;
    }

    require_login();

    if ($page === 'dashboard') {
        require __DIR__ . '/views/dashboard.php';
    } elseif ($page === 'reports') {
        require __DIR__ . '/views/reports.php';
    } elseif ($page === 'users') {
        require __DIR__ . '/views/users.php';
    } elseif (array_key_exists($page, require __DIR__ . '/modules.php')) {
        require __DIR__ . '/module.php';
    } else {
        http_response_code(404);
        echo 'Page not found';
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    $message = envv('APP_ENV', 'production') === 'development'
        ? $exception->getMessage()
        : 'The request could not be completed. Check the application log.';

    require __DIR__ . '/views/error.php';
}
