<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\CSRF;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');

$title = 'Categories';
$categories = [];
$loadError = null;

try {
    if ($categoriesRepo instanceof CategoryRepository) {
        $categories = $categoriesRepo->all();
    }
} catch (\PDOException $exception) {
    $loadError = $exception;
    if (config('app.debug', false)) {
        flash('error', 'Database error: ' . $exception->getMessage());
    } else {
        flash('error', 'Unable to load categories. Please check database configuration.');
    }
}

$csrfToken = CSRF::getToken();

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
        <h1 class="card__title" style="margin:0;">Categories</h1>
        <a class="button" href="<?= htmlspecialchars(base_url('categories/create.php')) ?>">Create Category</a>
    </div>

    <?php if ($loadError): ?>
        <p style="color:#dc2626; margin-top:1.5rem;">Unable to load categories.</p>
    <?php elseif (empty($categories)): ?>
        <p style="color:#64748b; margin-top:1.5rem;">No categories yet.</p>
    <?php else: ?>
        <div class="table-responsive" style="margin-top:1.5rem;">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Created</th>
                    <th style="width:160px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td><?= htmlspecialchars($category['slug']) ?></td>
                        <td><?= htmlspecialchars($category['created_at'] ?? '') ?></td>
                        <td>
                            <div style="display:flex; gap:0.5rem;">
                                <a class="button button--light" href="<?= htmlspecialchars(base_url('categories/edit.php?id=' . $category['id'])) ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(base_url('categories/delete.php')) ?>" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                    <button class="button" style="background:#dc2626;" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
