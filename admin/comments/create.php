<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CommentRepository;
use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\PostRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

/** @var CommentRepository|null $commentsRepo */
$commentsRepo = app('comments');
/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');
/** @var PDO|null $db */
$db = app('db');

if (!$commentsRepo || !$db) {
    flash('error', 'Comment service unavailable.');
    redirect(base_url('comments/index.php'));
}

$postsStatement = $db->query(
    'SELECT id, slug, title_az
     FROM posts
     ORDER BY COALESCE(updated_at, created_at) DESC'
);
$posts = $postsStatement ? $postsStatement->fetchAll() : [];

if (empty($posts)) {
    flash('error', 'Please create a post before adding comments.');
    redirect(base_url('posts/create.php'));
}

$postMap = [];
foreach ($posts as $post) {
    $postMap[(int)$post['id']] = $post;
}

$selectedPostId = isset($_POST['post_id'])
    ? (int)$_POST['post_id']
    : (int)($_GET['post_id'] ?? $posts[0]['id']);

if (!isset($postMap[$selectedPostId])) {
    $selectedPostId = (int)$posts[0]['id'];
}

$parentComments = [];
$loadParentComments = static function (CommentRepository $repo, int $postId): array {
    try {
        $listing = $repo->listByPost($postId, 1, 200, true);
        return $listing['data'] ?? [];
    } catch (Throwable $exception) {
        return [];
    }
};

$parentComments = $loadParentComments($commentsRepo, $selectedPostId);

$currentUser = $auth?->user();
$title = 'Add Comment';
$errors = [];
$formData = [
    'post_id' => $selectedPostId,
    'parent_comment_id' => null,
    'author_name' => $currentUser['username'] ?? '',
    'message' => '',
    'visibility' => 'visible',
    'link_user' => $currentUser ? 'admin' : 'guest',
];

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('comments/create.php'));
    }

    $formData['post_id'] = (int)($_POST['post_id'] ?? 0);
    $formData['parent_comment_id'] = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
        ? (int)$_POST['parent_comment_id']
        : null;
    $formData['author_name'] = trim((string)($_POST['author_name'] ?? ''));
    $formData['message'] = trim((string)($_POST['message'] ?? ''));
    $formData['visibility'] = $_POST['visibility'] === 'hidden' ? 'hidden' : 'visible';
    $formData['link_user'] = in_array($_POST['link_user'] ?? 'guest', ['admin', 'guest'], true)
        ? ($_POST['link_user'] ?? 'guest')
        : 'guest';

    if (!$currentUser) {
        $formData['link_user'] = 'guest';
    }

    if (!isset($postMap[$formData['post_id']])) {
        $errors['post_id'][] = 'Please choose a valid post.';
    }

    if ($formData['link_user'] === 'guest') {
        if ($formData['author_name'] === '' || mb_strlen($formData['author_name']) < 2) {
            $errors['author_name'][] = 'Author name must be at least 2 characters.';
        } elseif (mb_strlen($formData['author_name']) > 191) {
            $errors['author_name'][] = 'Author name is too long.';
        }
    } elseif ($currentUser) {
        $formData['author_name'] = $currentUser['username'] ?? 'Admin';
    }

    if ($formData['message'] === '' || mb_strlen($formData['message']) < 2) {
        $errors['message'][] = 'Message must be at least 2 characters.';
    }

    $parentComment = null;
    if ($formData['parent_comment_id']) {
        try {
            $parentComment = $commentsRepo->find($formData['parent_comment_id']);
        } catch (Throwable $exception) {
            $parentComment = null;
        }

        if (!$parentComment || (int)$parentComment['post_id'] !== $formData['post_id']) {
            $errors['parent_comment_id'][] = 'Parent comment does not belong to the selected post.';
        } elseif ((int)$parentComment['is_deleted'] === 1) {
            $errors['parent_comment_id'][] = 'Parent comment is hidden.';
        }
    }

    if (empty($errors)) {
        try {
            $commentsRepo->create([
                'post_id' => $formData['post_id'],
                'user_id' => $formData['link_user'] === 'admin' && isset($currentUser['id'])
                    ? (int)$currentUser['id']
                    : null,
                'parent_comment_id' => $formData['parent_comment_id'],
                'author_name' => $formData['author_name'],
                'message' => $formData['message'],
                'is_deleted' => $formData['visibility'] === 'hidden' ? 1 : 0,
            ]);

            flash('success', 'Comment added successfully.');
            redirect(base_url('comments/index.php'));
        } catch (Throwable $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to add comment. Please try again.';
        }
    } else {
        $parentComments = $loadParentComments($commentsRepo, $formData['post_id']);
    }
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <h1 class="card__title" style="margin:0;">Add Comment</h1>
        <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Back to comments</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error" style="margin-top:1rem;">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('comments/create.php')) ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="post_id">Post</label>
            <select class="form__control" id="post_id" name="post_id" required>
                <?php foreach ($posts as $post): ?>
                    <option value="<?= (int)$post['id'] ?>"
                            data-slug="<?= htmlspecialchars($post['slug']) ?>"
                        <?= $formData['post_id'] === (int)$post['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($post['title_az'] ?? $post['slug']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($error = $errors['post_id'][0] ?? null): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="parent_comment_id">Reply To (optional)</label>
            <select class="form__control" id="parent_comment_id" name="parent_comment_id">
                <option value="">-- None --</option>
                <?php foreach ($parentComments as $parent): ?>
                    <?php
                    $snippet = strip_tags((string)$parent['message']);
                    $snippet = mb_strimwidth($snippet, 0, 60, '…');
                    $isHidden = (int)($parent['is_deleted'] ?? 0) === 1;
                    ?>
                    <option value="<?= (int)$parent['id'] ?>"
                            data-author="<?= htmlspecialchars($parent['author_name']) ?>"
                            data-message="<?= htmlspecialchars($snippet) ?>"
                            data-hidden="<?= $isHidden ? '1' : '0' ?>"
                        <?= $formData['parent_comment_id'] === (int)$parent['id'] ? 'selected' : '' ?>>
                        <?= $isHidden ? '[Hidden] ' : '' ?>#<?= (int)$parent['id'] ?> — <?= htmlspecialchars($parent['author_name']) ?> — <?= htmlspecialchars($snippet) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color:#64748b;">Pick the exact comment you want to reply to. This list refreshes automatically whenever you change the post above.</small>
            <?php if ($error = $errors['parent_comment_id'][0] ?? null): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div id="parent-preview" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem; padding:0.75rem; font-size:0.9rem; color:#0f172a; display:none;">
            <strong id="parent-preview-author"></strong>
            <p id="parent-preview-text" style="margin:0.25rem 0 0;"></p>
        </div>

        <div class="form__group">
            <label class="form__label" for="author_name">Author name</label>
            <input class="form__control" id="author_name" name="author_name" type="text" required
                   data-admin-name="<?= htmlspecialchars($currentUser['username'] ?? '') ?>"
                   value="<?= htmlspecialchars($formData['author_name']) ?>">
            <?php if ($error = $errors['author_name'][0] ?? null): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label">Comment as</label>
            <div style="display:flex; gap:1rem;">
                <label style="display:flex; align-items:center; gap:0.35rem; cursor:pointer;">
                    <input type="radio" name="link_user" value="admin"
                        <?= $formData['link_user'] === 'admin' ? 'checked' : '' ?>
                        <?= $currentUser ? '' : 'disabled' ?>>
                    <span>Admin (<?= htmlspecialchars($currentUser['username'] ?? 'N/A') ?>)</span>
                </label>
                <label style="display:flex; align-items:center; gap:0.35rem; cursor:pointer;">
                    <input type="radio" name="link_user" value="guest" <?= $formData['link_user'] === 'guest' ? 'checked' : '' ?>>
                    <span>Guest (custom name)</span>
                </label>
            </div>
            <?php if (!$currentUser): ?>
                <small style="color:#64748b;">Not linked to an admin account because you are not logged in.</small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="message">Message</label>
            <textarea class="form__control" id="message" name="message" rows="6" data-editor="rich-text"><?= htmlspecialchars($formData['message']) ?></textarea>
            <?php if ($error = $errors['message'][0] ?? null): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="visibility">Visibility</label>
            <select class="form__control" id="visibility" name="visibility">
                <option value="visible" <?= $formData['visibility'] === 'visible' ? 'selected' : '' ?>>Visible immediately</option>
                <option value="hidden" <?= $formData['visibility'] === 'hidden' ? 'selected' : '' ?>>Hidden / requires review</option>
            </select>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Cancel</a>
            <button class="button" type="submit">Submit Comment</button>
        </div>
    </form>
</section>

<script>
(function () {
    const postSelect = document.getElementById('post_id');
    const parentSelect = document.getElementById('parent_comment_id');
    const parentPreview = document.getElementById('parent-preview');
    const parentPreviewAuthor = document.getElementById('parent-preview-author');
    const parentPreviewText = document.getElementById('parent-preview-text');
    const authorInput = document.getElementById('author_name');
    const linkRadios = document.querySelectorAll('input[name="link_user"]');
    const commentsApi = '<?= htmlspecialchars(base_url('comments/options.php')) ?>';

    if (!postSelect || !parentSelect) {
        return;
    }

    let commentCache = new Map();

    function seedCacheFromDom() {
        commentCache = new Map();
        const options = parentSelect.querySelectorAll('option');
        options.forEach(option => {
            if (!option.value) {
                return;
            }
            commentCache.set(option.value, {
                id: option.value,
                author_name: option.dataset.author || option.textContent || '',
                message_plain: option.dataset.message || '',
                is_deleted: option.dataset.hidden === '1',
            });
        });
    }

    function updateParentPreview() {
        if (!parentPreview || !parentPreviewAuthor || !parentPreviewText) {
            return;
        }
        const selectedId = parentSelect.value;
        if (!selectedId || !commentCache.has(selectedId)) {
            parentPreview.style.display = 'none';
            parentPreviewAuthor.textContent = '';
            parentPreviewText.textContent = '';
            return;
        }
        const comment = commentCache.get(selectedId);
        const statusLabel = comment.is_deleted ? '[Hidden] ' : '';
        parentPreviewAuthor.textContent = `${statusLabel}Replying to ${comment.author_name} (#${comment.id})`;
        parentPreviewText.textContent = comment.message_plain;
        parentPreview.style.display = 'block';
    }

    function setParentOptions(comments) {
        const selectedValue = parentSelect.value;
        commentCache = new Map();
        parentSelect.innerHTML = '';
        const noneOption = document.createElement('option');
        noneOption.value = '';
        noneOption.textContent = '-- None --';
        parentSelect.appendChild(noneOption);

        comments.forEach(comment => {
            commentCache.set(String(comment.id), comment);
            const option = document.createElement('option');
            option.value = comment.id;
            option.dataset.author = comment.author_name;
            option.dataset.message = comment.message_plain;
            option.dataset.hidden = comment.is_deleted ? '1' : '0';
            const statusPrefix = comment.is_deleted ? '[Hidden] ' : '';
            option.textContent = `${statusPrefix}#${comment.id} — ${comment.author_name} — ${comment.message_plain}`;
            if (String(comment.id) === selectedValue) {
                option.selected = true;
            }
            parentSelect.appendChild(option);
        });

        updateParentPreview();
    }

    async function refreshParentComments() {
        const selected = postSelect.selectedOptions[0];
        if (!selected) {
            return;
        }
        const postId = selected.value;
        if (!postId) {
            return;
        }
        parentSelect.value = '';
        commentCache.clear();
        updateParentPreview();
        try {
            const response = await fetch(`${commentsApi}?post_id=${encodeURIComponent(postId)}`);
            if (!response.ok) {
                throw new Error('Failed to load comments');
            }
            const data = await response.json();
            setParentOptions(data.data || []);
        } catch (error) {
            console.warn('Unable to refresh parent comments', error);
        }
    }

    function syncAuthorField() {
        if (!authorInput || !linkRadios.length) {
            return;
        }
        const selected = Array.from(linkRadios).find(radio => radio.checked)?.value;
        if (selected === 'admin' && authorInput.dataset.adminName) {
            authorInput.value = authorInput.dataset.adminName;
            authorInput.setAttribute('readonly', 'readonly');
            authorInput.style.backgroundColor = '#f3f4f6';
        } else {
            authorInput.removeAttribute('readonly');
            authorInput.style.backgroundColor = '';
        }
    }

    postSelect.addEventListener('change', refreshParentComments);
    parentSelect.addEventListener('change', updateParentPreview);
    linkRadios.forEach(radio => radio.addEventListener('change', syncAuthorField));

    seedCacheFromDom();
    syncAuthorField();
    updateParentPreview();
})();
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
