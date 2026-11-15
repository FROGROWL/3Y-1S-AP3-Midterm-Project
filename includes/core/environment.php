<?php
declare(strict_types=1);
function envv(string $name, ?string $fallback = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $path = dirname(__DIR__, 2) . '/.env';

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

