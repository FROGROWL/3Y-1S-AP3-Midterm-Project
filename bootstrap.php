<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function envv(string $name, ?string $fallback = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $path = __DIR__ . '/.env';

        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim(trim($value), " \\\"'");
            }
        }

        $loaded = true;
    }

    $environmentValue = getenv($name);

    if ($environmentValue !== false) {
        return $environmentValue;
    }

    return $_ENV[$name] ?? $fallback;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];

    foreach ($required as $key) {
        if (envv($key) === null || envv($key) === '') {
            throw new RuntimeException(
                'Database configuration is incomplete. Copy .env.example to .env and enter the Supabase connection values.'
            );
        }
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        envv('DB_HOST'),
        envv('DB_PORT', '5432'),
        envv('DB_NAME'),
        envv('DB_SSLMODE', 'require')
    );

    $pdo = new PDO($dsn, envv('DB_USERNAME'), envv('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function url(string $query = ''): string
{
    return 'index.php' . ($query !== '' ? '?' . $query : '');
}

function go(string $query): never
{
    header('Location: ' . url($query));
    exit;
}

function flash(string $kind, string $message): void
{
    $_SESSION['flash'] = [$kind, $message];
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    $submittedToken = (string) ($_POST['csrf'] ?? '');

    if (!hash_equals(csrf(), $submittedToken)) {
        http_response_code(419);
        exit('Session expired. Please go back and try again.');
    }
}

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
                app_user.signed_out_at, user_role.label AS role
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

function audit(string $action, string $entity, ?int $id = null, ?array $details = null): void
{
    $statement = db()->prepare(
        'INSERT INTO audit_log (user_id, action_id, entity_type_id, entity_record_id, details)
         SELECT ?, action.action_id, entity.entity_type_id, ?, ?::jsonb
         FROM audit_action action, audit_entity entity
         WHERE action.code = ? AND entity.code = ?'
    );
    $jsonDetails = $details === null
        ? null
        : json_encode($details, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    $statement->execute([
        user()['user_id'] ?? null,
        $id,
        $jsonDetails,
        strtoupper($action),
        strtolower($entity),
    ]);
}
