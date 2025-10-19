<?php

declare(strict_types=1);

$basePath = __DIR__;

require_once $basePath . '/helpers.php';

load_env($basePath . '/.env');

$config = require $basePath . '/config.php';
set_config($config);

date_default_timezone_set((string)config('app.timezone', 'Asia/Baku'));

if (session_status() === PHP_SESSION_NONE) {
    session_name((string)config('app.session_name', 'motobaku_admin'));
    session_start();
}

foreach (glob($basePath . '/lib/*.php') as $libFile) {
    require_once $libFile;
}

use MotoBaku\Admin\Auth;
use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\Database;
use MotoBaku\Admin\PostRepository;

$connection = Database::make(config('db'));

set_service('db', $connection);
set_service('auth', new Auth($connection));
set_service('posts', new PostRepository($connection));
set_service('categories', new CategoryRepository($connection));
