<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\CommentRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

if (!is_post()) {
    redirect(base_url('comments/index.php'));
}

if (!CSRF::validate($_POST['_token'] ?? null)) {
    flash('error', 'Session expired. Please try again.');
    redirect(base_url('comments/index.php'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$state = isset($_POST['state']) ? (int)$_POST['state'] : 0;
$setDeleted = $state === 1;

/** @var CommentRepository|null $commentsRepo */
$commentsRepo = app('comments');

if (!$commentsRepo || $id <= 0) {
    flash('error', 'Invalid comment request.');
    redirect(base_url('comments/index.php'));
}

try {
    if ($commentsRepo->setDeleted($id, $setDeleted)) {
        flash('success', $setDeleted ? 'Comment hidden.' : 'Comment restored.');
    } else {
        flash('error', 'Comment not found.');
    }
} catch (\PDOException $exception) {
    flash('error', config('app.debug', false)
        ? $exception->getMessage()
        : 'Unable to update comment. Please try again.');
}

redirect(base_url('comments/index.php'));
