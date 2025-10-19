<?php

declare(strict_types=1);

$GLOBALS['APP_CONFIG'] = [];
$GLOBALS['APP_CONTAINER'] = [];

/**
 * Load key=value pairs from a .env file into environment variables.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $line, 2), 2, null);
        $name = trim((string)$name);
        $value = $value !== null ? trim($value) : '';
        $value = trim($value, "\"'");

        if ($name === '') {
            continue;
        }

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
        if (!array_key_exists($name, $_SERVER)) {
            $_SERVER[$name] = $value;
        }

        putenv(sprintf('%s=%s', $name, $value));
    }
}

/**
 * Retrieve a configuration value from the environment.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    if (is_string($value)) {
        $normalized = strtolower($value);
        if (in_array($normalized, ['true', '(true)', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['false', '(false)', 'no', 'off'], true)) {
            return false;
        }
        if ($normalized === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
    }

    return $value;
}

function set_config(array $config): void
{
    $GLOBALS['APP_CONFIG'] = $config;
}

function config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['APP_CONFIG'] ?? [];

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function set_service(string $key, mixed $value): void
{
    $GLOBALS['APP_CONTAINER'][$key] = $value;
}

function app(string $key, mixed $default = null): mixed
{
    return $GLOBALS['APP_CONTAINER'][$key] ?? $default;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = preg_replace('/-+/', '-', $value);

    return trim((string)$value, '-');
}

function sanitize_filename(string $filename): string
{
    $filename = trim($filename);
    $filename = str_replace('\\', '/', $filename);
    $filename = basename($filename);

    $extension = '';
    if (str_contains($filename, '.')) {
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension);
        $name = (string)pathinfo($filename, PATHINFO_FILENAME);
    } else {
        $name = $filename;
    }

    $name = preg_replace('/[^a-z0-9_\-]+/i', '-', $name);
    $name = trim($name, '-_');

    if ($name === '') {
        $name = 'file';
    }

    $sanitized = strtolower($name);

    return $extension !== ''
        ? $sanitized . '.' . $extension
        : $sanitized;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string)config('app.url', ''), '/');
    $path = ltrim($path, '/');

    if ($base === '') {
        return '/' . $path;
    }

    return $path === '' ? $base : $base . '/' . $path;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $key, ?string $message = null): mixed
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session is not active.');
    }

    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    if ($message === null) {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    $_SESSION['_flash'][$key] = $message;

    return $message;
}

function remember_input(array $values): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Session is not active.');
    }

    $_SESSION['_old'] = $values;
}

function old(string $key, mixed $default = null): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function field_error(array $errors, string $field): ?string
{
    return $errors[$field][0] ?? null;
}
