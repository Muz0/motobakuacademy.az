<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\CSRF;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

if (!is_post()) {
    redirect(base_url('categories/index.php'));
}

if (!CSRF::validate($_POST['_token'] ?? null)) {
    flash('error', 'Session expired. Please try again.');
    redirect(base_url('categories/index.php'));
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');

if (!$categoriesRepo) {
    flash('error', 'Unable to delete category.');
    redirect(base_url('categories/index.php'));
}

try {
    if ($id > 0) {
        $deleted = $categoriesRepo->delete($id);
        if ($deleted) {
            flash('success', 'Category deleted successfully.');
        } else {
            flash('error', 'Category not found or already deleted.');
        }
    } else {
        flash('error', 'Invalid category id.');
    }
} catch (\PDOException $exception) {
    flash('error', config('app.debug', false)
        ? $exception->getMessage()
        : 'Unable to delete category. Please try again.');
}

redirect(base_url('categories/index.php'));
