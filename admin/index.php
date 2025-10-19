<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use MotoBaku\Admin\PostRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$title = 'Dashboard';
/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');

$stats = [
    'total' => 0,
    'published' => 0,
    'drafts' => 0,
];
$recentPosts = [];

if ($postsRepo instanceof PostRepository) {
    try {
        $stats['total'] = $postsRepo->countAll();
        $stats['published'] = $postsRepo->countPublished();
        $stats['drafts'] = $postsRepo->countDrafts();
        $recentPosts = $postsRepo->latest(5);
    } catch (\PDOException $exception) {
        if (config('app.debug', false)) {
            flash('error', 'Database error: ' . $exception->getMessage());
        } else {
            flash('error', 'Unable to load statistics. Please check database connectivity.');
        }
    }
}

include __DIR__ . '/views/layout/header.php';
?>

<?php include __DIR__ . '/views/partials/flash.php'; ?>

<section class="stats">
    <div class="stat">
        <span class="stat__label">Total Posts</span>
        <span class="stat__value"><?= htmlspecialchars((string)$stats['total']) ?></span>
    </div>
    <div class="stat">
        <span class="stat__label">Published</span>
        <span class="stat__value"><?= htmlspecialchars((string)$stats['published']) ?></span>
    </div>
    <div class="stat">
        <span class="stat__label">Drafts</span>
        <span class="stat__value"><?= htmlspecialchars((string)$stats['drafts']) ?></span>
    </div>
</section>

<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card__title" style="margin:0;">Recent Posts</h2>
        <a class="button" href="<?= htmlspecialchars(base_url('posts/create.php')) ?>">New Post</a>
    </div>

    <?php if (empty($recentPosts)): ?>
        <p style="color:#64748b; margin-top:1rem;">No posts found. Start by creating a new post.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Updated</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($post['title']) ?></strong><br>
                            <small style="color:#8896a5;"><?= htmlspecialchars($post['slug']) ?></small>
                        </td>
                        <td>
                            <span style="text-transform:capitalize; font-weight:600;"><?= htmlspecialchars($post['status']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($post['published_at'] ?? '--') ?></td>
                        <td><?= htmlspecialchars($post['updated_at'] ?? $post['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/views/layout/footer.php'; ?>
