<?php

declare(strict_types=1);

return [
    'app' => [
        'url' => rtrim((string)env('APP_URL', '/admin'), '/'),
        'session_name' => (string)env('SESSION_NAME', 'motobaku_admin'),
        'debug' => (bool)env('APP_DEBUG', false),
        'timezone' => (string)env('APP_TIMEZONE', 'Asia/Baku'),
    ],
    'db' => [
        'driver' => (string)env('DB_DRIVER', 'mysql'),
        'host' => (string)env('DB_HOST', '127.0.0.1'),
        'port' => (int)env('DB_PORT', 3306),
        'database' => (string)env('DB_DATABASE', 'motobaku'),
        'username' => (string)env('DB_USERNAME', 'root'),
        'password' => (string)env('DB_PASSWORD', ''),
        'charset' => (string)env('DB_CHARSET', 'utf8mb4'),
        'collation' => (string)env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    ],
];
