<?php

declare(strict_types=1);

$basePath = __DIR__;

require_once $basePath . '/helpers.php';

load_env($basePath . '/.env');

$config = require $basePath . '/config.php';
set_config($config);

$forceHttps = (bool)config('app.force_https', false);

if ($forceHttps && !is_request_secure()) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '') {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: https://' . $host . $requestUri, true, 301);
        exit;
    }
}

if ($forceHttps && is_request_secure()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

date_default_timezone_set((string)config('app.timezone', 'Asia/Baku'));

if (session_status() === PHP_SESSION_NONE) {
    $sessionName = (string)config('app.session_name', 'motobaku_admin');
    if ($sessionName !== '') {
        session_name($sessionName);
    }

    $appUrl = (string)config('app.url', '');
    $parsedUrl = parse_url($appUrl);
    $defaultCookieParams = session_get_cookie_params();
    $cookieDomain = $parsedUrl['host'] ?? ($defaultCookieParams['domain'] ?? '');
    $cookieOptions = [
        'lifetime' => 0,
        'path' => $defaultCookieParams['path'] ?? '/',
        'domain' => $cookieDomain ?: '',
        'secure' => is_request_secure() || (isset($parsedUrl['scheme']) && strtolower((string)$parsedUrl['scheme']) === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieOptions);
    } else {
        session_set_cookie_params(
            $cookieOptions['lifetime'],
            $cookieOptions['path'] . '; samesite=' . $cookieOptions['samesite'],
            $cookieOptions['domain'],
            $cookieOptions['secure'],
            true
        );
    }

    session_start();
}

foreach (glob($basePath . '/lib/*.php') as $libFile) {
    require_once $libFile;
}

use MotoBaku\Admin\Auth;
use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\Database;
use MotoBaku\Admin\CommentRepository;
use MotoBaku\Admin\PostRepository;
use MotoBaku\Admin\TeamRepository;

$connection = Database::make(config('db'));

set_service('db', $connection);
set_service('auth', new Auth($connection));
set_service('posts', new PostRepository($connection));
set_service('categories', new CategoryRepository($connection));
set_service('comments', new CommentRepository($connection));
set_service('team', new TeamRepository($connection));
