<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\PostRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$title = 'Posts';
$page = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['search'] ?? ''));
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

$filters = [];

if (in_array($status, ['draft', 'published'], true)) {
    $filters['status'] = $status;
}
if ($search !== '') {
    $filters['search'] = $search;
}
if (!empty($categoryId)) {
    $filters['category_id'] = $categoryId;
}

/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');
/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');

$pagination = [
    'data' => [],
    'total' => 0,
    'page' => $page,
    'per_page' => 10,
    'last_page' => 1,
];
$loadError = null;
$categories = [];

try {
    if ($postsRepo instanceof PostRepository) {
        $pagination = $postsRepo->paginate($page, 10, $filters);
    }
    if ($categoriesRepo instanceof CategoryRepository) {
        $categories = $categoriesRepo->all();
    }
} catch (\PDOException $exception) {
    $loadError = $exception;
    if (config('app.debug', false)) {
        flash('error', 'Database error: ' . $exception->getMessage());
    } else {
        flash('error', 'Unable to load posts. Please check database configuration.');
    }
}

$csrfToken = CSRF::getToken();

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
        <h1 class="card__title" style="margin:0;">Posts</h1>
        <a class="button" href="<?= htmlspecialchars(base_url('posts/create.php')) ?>">Create Post</a>
    </div>

    <form method="get" class="form" style="margin-top:1.5rem; max-width:none;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
            <div class="form__group">
                <label class="form__label" for="search">Search</label>
                <input class="form__control" type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or slug">
            </div>
            <div class="form__group">
                <label class="form__label" for="status">Status</label>
                <select class="form__control" id="status" name="status">
                    <option value="">Any status</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <div class="form__group">
                <label class="form__label" for="category">Category</label>
                <select class="form__control" id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group" style="align-self:flex-end;">
                <button class="button" type="submit" style="width:100%;">Apply</button>
            </div>
        </div>
    </form>

    <?php if ($loadError): ?>
        <p style="color:#dc2626; margin-top:1.5rem;">Unable to load posts. Check database schema.</p>
    <?php elseif (empty($pagination['data'])): ?>
        <p style="color:#64748b; margin-top:1.5rem;">No posts found.</p>
    <?php else: ?>
        <div class="table-responsive" style="margin-top:1.5rem;">
            <table>
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Categories</th>
                    <th>Comments</th>
                    <th>Updated</th>
                    <th style="width:160px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['data'] as $post): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                            <br>
                            <small style="color:#8896a5;"><?= htmlspecialchars($post['slug']) ?></small>
                        </td>
                        <td style="text-transform:capitalize;"><?= htmlspecialchars($post['status']) ?></td>
                        <td>
                            <?php if (!empty($post['categories'])): ?>
                                <?php foreach ($post['categories'] as $category): ?>
                                    <span style="display:inline-block; background:#e2eff7; color:#0369a1; padding:0.2rem 0.5rem; border-radius:999px; font-size:0.75rem; margin:0.1rem;">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:#9ca3af;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $accepts = (int)($post['accepts_comments'] ?? 1) === 1; ?>
                            <span style="display:inline-flex; align-items:center; gap:0.35rem; font-weight:600; color:<?= $accepts ? '#059669' : '#b45309' ?>;">
                                <span style="width:0.5rem; height:0.5rem; border-radius:999px; background:<?= $accepts ? '#34d399' : '#fb923c' ?>;"></span>
                                <?= $accepts ? 'Open' : 'Closed' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($post['updated_at'] ?? $post['created_at']) ?></td>
                        <td>
                            <div style="display:flex; gap:0.5rem;">
                                <a class="button button--light" href="<?= htmlspecialchars(base_url('posts/edit.php?id=' . $post['id'])) ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(base_url('posts/delete.php')) ?>" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                                    <button class="button" style="background:#dc2626;" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['last_page'] ?? 1) > 1): ?>
            <div style="display:flex; gap:0.5rem; margin-top:1.5rem; justify-content:flex-end;">
                <?php for ($i = 1; $i <= (int)$pagination['last_page']; $i++): ?>
                    <?php
                    $query = $_GET;
                    $query['page'] = $i;
                    $url = base_url('posts/index.php') . '?' . http_build_query($query);
                    ?>
                    <a class="button <?= $i === (int)$pagination['page'] ? '' : 'button--light' ?>" href="<?= htmlspecialchars($url) ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
