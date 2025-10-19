<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$auth = app('auth');

if ($auth) {
    $auth->logout();
}

flash('success', 'Signed out successfully.');
redirect(base_url('login.php'));
