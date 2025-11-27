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

/**
 * Determine whether the current HTTP request is using HTTPS.
 */
function is_request_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTP_CF_VISITOR'] ?? null;
    if (is_string($forwardedProto)) {
        if (str_contains($forwardedProto, 'https')) {
            return true;
        }
        if (strtolower($forwardedProto) === 'https') {
            return true;
        }
    }

    return false;
}

/**
 * Retrieve the list of allowed origins for API responses.
 */
function allowed_origins(): array
{
    $origins = config('api.allowed_origins', []);

    if (!is_array($origins) || empty($origins)) {
        $fallback = (string)config('app.url', '');
        if ($fallback !== '') {
            $parts = parse_url($fallback);
            if ($parts && isset($parts['scheme'], $parts['host'])) {
                $origin = $parts['scheme'] . '://' . $parts['host'];
                if (isset($parts['port'])) {
                    $origin .= ':' . $parts['port'];
                }
                $origins = [$origin];
            }
        }
    }

    return array_values(array_unique(array_filter(array_map(
        static fn ($value) => trim((string)$value),
        $origins
    ))));
}

/**
 * Apply JSON + CORS headers according to configuration.
 */
function send_json_headers(array $methods = ['GET']): void
{
    static $sent = false;

    if ($sent) {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');

    // In debug mode, allow any origin to simplify local development.
    if (config('app.debug', false)) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: ' . implode(', ', array_unique(array_merge(['OPTIONS'], $methods))));
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        $sent = true;
        return;
    }

    $originHeader = $_SERVER['HTTP_ORIGIN'] ?? null;
    $allowed = allowed_origins();

    if ($originHeader && in_array($originHeader, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $originHeader);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', array_unique(array_merge(['OPTIONS'], $methods))));
    header('Access-Control-Allow-Headers: Content-Type, Accept');

    $sent = true;
}

/**
 * Send the configured preflight response if the request is OPTIONS.
 */
function handle_preflight(array $methods = ['GET']): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        send_json_headers($methods);
        http_response_code(204);
        exit;
    }
}
