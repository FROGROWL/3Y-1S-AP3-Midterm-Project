<?php
declare(strict_types=1);

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
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) {
        go('page=login');
    }
}

function require_admin(): void
{
    require_login();

    if (user()['role'] !== 'Administrator') {
        http_response_code(403);
        exit('Administrator access required.');
    }
}

function audit(string $action, string $entity, ?int $id = null): void
{
    $statement = db()->prepare(
        'INSERT INTO audit_log (user_id, action, entity, entity_id) VALUES (?, ?, ?, ?)'
    );
    $statement->execute([user()['user_id'] ?? null, $action, $entity, $id]);
}
