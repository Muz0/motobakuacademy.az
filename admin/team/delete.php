<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\TeamRepository;

$auth = app('auth');
if ($auth) {
    $auth->requireAuth();
}

/** @var TeamRepository|null $teamRepo */
$teamRepo = app('team');

if (!is_post()) {
    redirect(base_url('team/index.php'));
}

if (!CSRF::validate($_POST['_token'] ?? null)) {
    flash('error', 'Session expired. Please try again.');
    redirect(base_url('team/index.php'));
}

$id = (int)($_POST['id'] ?? 0);

if (!$teamRepo || $id <= 0) {
    flash('error', 'Invalid request.');
    redirect(base_url('team/index.php'));
}

$teamRepo->delete($id);

flash('success', 'Team member deleted.');
redirect(base_url('team/index.php'));
