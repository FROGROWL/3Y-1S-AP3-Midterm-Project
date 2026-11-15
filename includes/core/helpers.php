<?php
declare(strict_types=1);
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

