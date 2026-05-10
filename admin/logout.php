<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use MotoBaku\Admin\CSRF;

$auth = app('auth');

if (!is_post()) {
    redirect(base_url('login.php'));
}

if (!CSRF::validate($_POST['_token'] ?? null)) {
    flash('error', 'Session expired. Please try again.');
    redirect(base_url());
}

if ($auth) {
    $auth->logout();
}

flash('success', 'Signed out successfully.');
redirect(base_url('login.php'));
