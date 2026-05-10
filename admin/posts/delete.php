<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\PostRepository;


$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
    $auth->requireRole('admin');
}

if (!is_post()) {
    redirect(base_url('posts/index.php'));
}

if (!CSRF::validate($_POST['_token'] ?? null)) {
    flash('error', 'Session expired. Please try again.');
    redirect(base_url('posts/index.php'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');

if (!$postsRepo) {
    flash('error', 'Unable to delete post.');
    redirect(base_url('posts/index.php'));
}

try {
    if ($id > 0) {
        $deleted = $postsRepo->delete($id);
        if ($deleted) {
            flash('success', 'Post deleted successfully.');
        } else {
            flash('error', 'Post not found or already deleted.');
        }
    } else {
        flash('error', 'Invalid post id.');
    }
} catch (\PDOException $exception) {
    flash('error', config('app.debug', false)
        ? $exception->getMessage()
        : 'Unable to delete post. Please try again.');
}

redirect(base_url('posts/index.php'));
