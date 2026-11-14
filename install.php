<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run installation from the command line.\n");
}

try {
    $pdo = db();
    $legacySchema = (bool) $pdo->query(
        "SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = current_schema()
              AND table_name = 'crop'
              AND column_name = 'variety'
        )"
    )->fetchColumn();

    if ($legacySchema) {
        $migration = file_get_contents(
            __DIR__ . '/sql/migrations/001_integer_lookup_normalization.sql'
        );

        if ($migration === false) {
            throw new RuntimeException('Missing integer lookup migration.');
        }

        $pdo->exec($migration);
        echo "Applied 001_integer_lookup_normalization.sql\n";
    }

    $sqlFiles = ['schema.sql', 'lookup_data.sql', 'sample_data.sql'];

    foreach ($sqlFiles as $file) {
        $path = __DIR__ . '/sql/' . $file;
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException("Missing SQL file: {$file}");
        }

        $pdo->exec($sql);
        echo "Applied {$file}\n";
    }

    $email = envv('INSTALL_ADMIN_EMAIL');
    $password = envv('INSTALL_ADMIN_PASSWORD');
    $name = envv('INSTALL_ADMIN_NAME', 'System Administrator');

    if ($email && $password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new RuntimeException(
                'Set a valid INSTALL_ADMIN_EMAIL and an INSTALL_ADMIN_PASSWORD of at least 12 characters.'
            );
        }

        $statement = $pdo->prepare(
            'INSERT INTO app_user (full_name, email, password_hash, role_id)
             SELECT ?, ?, ?, role_id FROM user_role WHERE code = ?
             ON CONFLICT (email) DO NOTHING'
        );
        $statement->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            'administrator',
        ]);

        echo "Administrator account ensured for {$email}\n";
    } else {
        echo "No admin created: set INSTALL_ADMIN_EMAIL and INSTALL_ADMIN_PASSWORD in .env, "
            . "then rerun to create the first account.\n";
    }

    echo "Database installation completed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Installation failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
