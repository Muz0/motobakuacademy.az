<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\CommentRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$title = 'Comments';
$page = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['search'] ?? ''));
$postFilter = trim((string)($_GET['post'] ?? ''));

$filters = [];
if (in_array($status, ['active', 'deleted'], true)) {
    $filters['status'] = $status;
}
if ($search !== '') {
    $filters['search'] = $search;
}
if ($postFilter !== '') {
    $filters['post'] = $postFilter;
}

/** @var CommentRepository|null $commentsRepo */
$commentsRepo = app('comments');

$pagination = [
    'data' => [],
    'total' => 0,
    'page' => $page,
    'per_page' => 15,
    'last_page' => 1,
];
$loadError = null;

try {
    if ($commentsRepo instanceof CommentRepository) {
        $pagination = $commentsRepo->paginate($page, 15, $filters);
    }
} catch (\PDOException $exception) {
    $loadError = $exception;
    if (config('app.debug', false)) {
        flash('error', 'Database error: ' . $exception->getMessage());
    } else {
        flash('error', 'Unable to load comments. Please try again later.');
    }
}

$csrfToken = CSRF::getToken();

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="card__title" style="margin:0;">Comments</h1>
            <p style="margin:0; color:#6b7280;">Review new feedback, edit content, or hide spam.</p>
        </div>
        <a class="button" href="<?= htmlspecialchars(base_url('comments/create.php')) ?>">Add Comment</a>
    </div>

    <form method="get" class="form" style="margin-top:1.5rem; max-width:none;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
            <div class="form__group">
                <label class="form__label" for="search">Author or text</label>
                <input class="form__control" type="text" id="search" name="search"
                       value="<?= htmlspecialchars($search) ?>" placeholder="Search comments">
            </div>
            <div class="form__group">
                <label class="form__label" for="post">Post</label>
                <input class="form__control" type="text" id="post" name="post"
                       value="<?= htmlspecialchars($postFilter) ?>" placeholder="Filter by post title or slug">
            </div>
            <div class="form__group">
                <label class="form__label" for="status">Status</label>
                <select class="form__control" id="status" name="status">
                    <option value="">Any status</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Visible</option>
                    <option value="deleted" <?= $status === 'deleted' ? 'selected' : '' ?>>Hidden</option>
                </select>
            </div>
            <div class="form__group" style="align-self:flex-end;">
                <div style="display:flex; gap:0.5rem;">
                    <button class="button" type="submit" style="flex:1;">Apply</button>
                    <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <?php if ($loadError): ?>
        <p style="color:#dc2626; margin-top:1.5rem;">Unable to load comments. Check database schema.</p>
    <?php elseif (empty($pagination['data'])): ?>
        <p style="color:#64748b; margin-top:1.5rem;">No comments found.</p>
    <?php else: ?>
        <div class="table-responsive" style="margin-top:1.5rem;">
            <table>
                <thead>
                <tr>
                    <th>Comment</th>
                    <th>Post</th>
                    <th>Parent</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th style="width:160px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['data'] as $comment): ?>
                    <?php
                    $isDeleted = (int)($comment['is_deleted'] ?? 0) === 1;
                    $messagePreview = (string)($comment['message'] ?? '');
                    if (function_exists('mb_strimwidth')) {
                        $messagePreview = mb_strimwidth($messagePreview, 0, 120, mb_strlen($messagePreview, 'UTF-8') > 120 ? '…' : '', 'UTF-8');
                    } elseif (strlen($messagePreview) > 120) {
                        $messagePreview = substr($messagePreview, 0, 117) . '...';
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($comment['author_name'] ?? 'Guest') ?></strong>
                            <?php if (!empty($comment['user_id'])): ?>
                                <span style="color:#64748b;">(user #<?= (int)$comment['user_id'] ?>)</span>
                            <?php else: ?>
                                <span style="color:#9ca3af;">(guest)</span>
                            <?php endif; ?>
                            <br>
                            <small style="color:#8896a5; display:block;"><?= htmlspecialchars($comment['created_at'] ?? '') ?></small>
                            <div style="margin-top:0.5rem; color:#111827; font-size:0.9rem; line-height:1.4;">
                                <?= nl2br(htmlspecialchars($messagePreview)) ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($comment['post_title'])): ?>
                                <strong><?= htmlspecialchars($comment['post_title']) ?></strong><br>
                                <small style="color:#8896a5;">Slug: <?= htmlspecialchars($comment['post_slug'] ?? '') ?></small>
                            <?php else: ?>
                                <span style="color:#9ca3af;">Post removed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($comment['parent_comment_id'])): ?>
                                <span style="font-weight:600; color:#0f172a;">#<?= (int)$comment['parent_comment_id'] ?></span>
                            <?php else: ?>
                                <span style="color:#9ca3af;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($comment['created_at'] ?? '') ?></td>
                        <td>
                            <span style="display:inline-flex; align-items:center; gap:0.35rem; font-weight:600; color:<?= $isDeleted ? '#b91c1c' : '#059669' ?>;">
                                <span style="width:0.5rem; height:0.5rem; border-radius:999px; background:<?= $isDeleted ? '#f87171' : '#34d399' ?>;"></span>
                                <?= $isDeleted ? 'Hidden' : 'Visible' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/edit.php?id=' . (int)$comment['id'])) ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(base_url('comments/toggle.php')) ?>"
                                      onsubmit="return confirm('<?= $isDeleted ? 'Restore this comment?' : 'Hide this comment?' ?>');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                                    <input type="hidden" name="state" value="<?= $isDeleted ? 0 : 1 ?>">
                                    <button class="button" type="submit" style="background:<?= $isDeleted ? '#0ea5e9' : '#dc2626' ?>;">
                                        <?= $isDeleted ? 'Restore' : 'Hide' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['last_page'] ?? 1) > 1): ?>
            <div style="display:flex; gap:0.5rem; margin-top:1.5rem; justify-content:flex-end; flex-wrap:wrap;">
                <?php for ($i = 1; $i <= (int)$pagination['last_page']; $i++): ?>
                    <?php
                    $query = $_GET;
                    $query['page'] = $i;
                    $url = base_url('comments/index.php') . '?' . http_build_query($query);
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
