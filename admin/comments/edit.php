<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\CommentRepository;
use MotoBaku\Admin\Validation;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/** @var CommentRepository|null $commentsRepo */
$commentsRepo = app('comments');

if (!$commentsRepo) {
    flash('error', 'Comment repository unavailable.');
    redirect(base_url('comments/index.php'));
}

$comment = $commentsRepo->find($id);

if (!$comment) {
    flash('error', 'Comment not found.');
    redirect(base_url('comments/index.php'));
}

$title = 'Edit Comment';
$errors = [];
$formData = [
    'author_name' => $comment['author_name'] ?? '',
    'message' => $comment['message'] ?? '',
    'status' => ((int)($comment['is_deleted'] ?? 0) === 1) ? 'deleted' : 'active',
];

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('comments/edit.php?id=' . $id));
    }

    $validation = Validation::make($_POST, [
        'author_name' => 'required|string|min:2|max:191',
        'message' => 'required|string|min:2',
        'status' => 'required|string|in:active,deleted',
    ]);

    $data = $validation['data'] ?? [];
    $errors = $validation['errors'] ?? [];

    $data['author_name'] = trim((string)($data['author_name'] ?? ''));
    $data['message'] = trim((string)($data['message'] ?? ''));
    $data['status'] = $data['status'] ?? 'active';

    if (empty($errors)) {
        try {
            $commentsRepo->update($id, [
                'author_name' => $data['author_name'],
                'message' => $data['message'],
                'is_deleted' => $data['status'] === 'deleted',
            ]);

            flash('success', 'Comment updated successfully.');
            redirect(base_url('comments/index.php'));
        } catch (\PDOException $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to update comment. Please try again.';
        }
    }

    $formData = array_merge($formData, $data);
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; align-items:flex-start;">
        <div>
            <h1 class="card__title" style="margin:0;">Edit Comment</h1>
            <p style="margin:0; color:#6b7280;">
                Attached to
                <?php if (!empty($comment['post_title'])): ?>
                    <strong><?= htmlspecialchars($comment['post_title']) ?></strong>
                    (<?= htmlspecialchars($comment['post_slug'] ?? '') ?>)
                <?php else: ?>
                    <span style="color:#9ca3af;">a removed post</span>
                <?php endif; ?>
            </p>
        </div>
        <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Back to list</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error" style="margin-top:1rem;">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('comments/edit.php?id=' . $id)) ?>" style="margin-top:1.5rem;">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="author_name">Author name</label>
            <input class="form__control" id="author_name" name="author_name" type="text" required
                   value="<?= htmlspecialchars($formData['author_name']) ?>">
            <?php if ($error = field_error($errors, 'author_name')): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="status">Visibility</label>
            <select class="form__control" id="status" name="status">
                <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Visible</option>
                <option value="deleted" <?= $formData['status'] === 'deleted' ? 'selected' : '' ?>>Hidden</option>
            </select>
            <?php if ($error = field_error($errors, 'status')): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="message">Message</label>
            <textarea class="form__control" id="message" name="message" rows="6" required><?= htmlspecialchars($formData['message']) ?></textarea>
            <?php if ($error = field_error($errors, 'message')): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Cancel</a>
            <button class="button" type="submit">Save Changes</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
