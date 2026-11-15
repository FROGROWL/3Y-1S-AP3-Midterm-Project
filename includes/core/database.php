<?php
declare(strict_types=1);
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

