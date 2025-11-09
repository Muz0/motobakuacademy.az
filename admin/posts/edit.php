<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\PostRepository;
use MotoBaku\Admin\Validation;


$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');
/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');

if (!$postsRepo) {
    flash('error', 'Post repository unavailable.');
    redirect(base_url('posts/index.php'));
}

$post = $postsRepo->find($id);

if (!$post) {
    flash('error', 'Post not found.');
    redirect(base_url('posts/index.php'));
}

$categories = $categoriesRepo ? $categoriesRepo->all() : [];
$title = 'Edit Post';
$errors = [];

$formData = [
    'title' => $post['title'],
    'slug' => $post['slug'],
    'excerpt' => $post['excerpt'] ?? '',
    'content' => $post['content'],
    'status' => $post['status'],
    'published_at' => $post['published_at'] ? (new \DateTime($post['published_at']))->format('Y-m-d\TH:i') : '',
    'cover_image' => $post['cover_image'] ?? '',
    'graphic_content' => $post['graphic_content'] ?? '',
    'categories' => array_map(static fn($category) => (int)$category['id'], $post['categories'] ?? []),
    'accepts_comments' => (int)($post['accepts_comments'] ?? 1) === 1,
];

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('posts/edit.php?id=' . $id));
    }

    $input = $_POST;
    $input['categories'] = $input['categories'] ?? [];

    $validation = Validation::make($input, [
        'title' => 'required|string|min:3|max:255',
        'slug' => 'required|string|slug|max:255',
        'excerpt' => 'nullable|string|max:500',
        'content' => 'required|string|min:10',
        'status' => 'required|string|in:draft,published',
        'published_at' => 'nullable|string',
        'cover_image' => 'nullable|string|max:255',
        'graphic_content' => 'nullable|string|max:255',
        'categories' => 'nullable|array',
        'accepts_comments' => 'nullable|string|in:on',
    ]);
    $data = $validation['data'] ?? [];
    $errors = $validation['errors'] ?? [];

    $data['title'] = trim((string)($data['title'] ?? ''));
    $data['slug'] = trim((string)($data['slug'] ?? ''));
    $data['excerpt'] = isset($data['excerpt']) ? trim((string)$data['excerpt']) : null;
    $data['content'] = (string)($data['content'] ?? '');
    $data['status'] = (string)($data['status'] ?? 'draft');
    $data['cover_image'] = isset($data['cover_image']) && $data['cover_image'] !== ''
        ? trim((string)$data['cover_image'])
        : null;
    $data['graphic_content'] = isset($data['graphic_content']) && $data['graphic_content'] !== ''
        ? trim((string)$data['graphic_content'])
        : null;

    $data['categories'] = array_values(array_unique(
        array_map(static fn($value) => (int)$value, (array)($data['categories'] ?? []))
    ));
    $data['accepts_comments'] = isset($input['accepts_comments']) && $input['accepts_comments'] === 'on';

    $slug = $data['slug'] ?: slugify((string)$data['title']);
    if ($slug === '') {
        $errors['slug'][] = 'Unable to generate slug. Please provide one manually.';
    } elseif ($postsRepo->existsBySlug($slug, $id)) {
        $errors['slug'][] = 'Slug already exists. Choose a different value.';
    }
    $data['slug'] = $slug;

    $publishedInput = $_POST['published_at'] ?? '';
    $data['published_at'] = null;

    if ($publishedInput !== '') {
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i', $publishedInput)
            ?: \DateTime::createFromFormat('Y-m-d H:i:s', $publishedInput);

        if ($dateTime === false) {
            $errors['published_at'][] = 'Invalid date/time format.';
        } else {
            $data['published_at'] = $dateTime->format('Y-m-d H:i:s');
        }
    }

    if (empty($errors)) {
        try {
            $postsRepo->update($id, [
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
                'content' => $data['content'],
                'status' => $data['status'],
                'published_at' => $data['published_at'],
                'cover_image' => $data['cover_image'],
                'graphic_content' => $data['graphic_content'],
                'categories' => $data['categories'],
                'accepts_comments' => $data['accepts_comments'],
            ]);

            flash('success', 'Post updated successfully.');
            redirect(base_url('posts/index.php'));
        } catch (\PDOException $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to update post. Please try again.';
        }
    }

    $formData = array_merge($formData, $data);
    $formData['published_at'] = $publishedInput;
    $formData['graphic_content'] = $data['graphic_content'] ?? '';
    $formData['accepts_comments'] = (bool)($data['accepts_comments'] ?? false);
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <h1 class="card__title">Edit Post</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('posts/edit.php?id=' . $id)) ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="title">Title</label>
            <input class="form__control" id="title" name="title" type="text" required
                   value="<?= htmlspecialchars($formData['title']) ?>" data-slug-source>
            <?php if ($error = field_error($errors, 'title')): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="slug">Slug</label>
            <input class="form__control" id="slug" name="slug" type="text"
                   value="<?= htmlspecialchars($formData['slug']) ?>"
                   data-slug-target data-slug-source="#title">
            <?php if ($error = field_error($errors, 'slug')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="excerpt">Excerpt</label>
            <textarea class="form__control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars((string)$formData['excerpt']) ?></textarea>
            <?php if ($error = field_error($errors, 'excerpt')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="content">Content</label>
            <textarea class="form__control" id="content" name="content" data-editor="rich-text" rows="12"><?= htmlspecialchars($formData['content']) ?></textarea>
            <small class="form__error" data-editor-error style="color:#dc2626; display:none;"></small>
            <?php if ($error = field_error($errors, 'content')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="status">Status</label>
            <select class="form__control" id="status" name="status" required>
                <option value="draft" <?= $formData['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $formData['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
            <?php if ($error = field_error($errors, 'status')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="published_at">Publish Date (optional)</label>
            <input class="form__control" id="published_at" name="published_at" type="datetime-local"
                   value="<?= htmlspecialchars($formData['published_at']) ?>">
            <?php if ($error = field_error($errors, 'published_at')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="accepts_comments" style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" id="accepts_comments" name="accepts_comments" value="on"
                       <?= !empty($formData['accepts_comments']) ? 'checked' : '' ?>>
                Accept new comments
            </label>
            <small style="color:#64748b;">Uncheck to prevent additional comments on this post.</small>
            <?php if ($error = field_error($errors, 'accepts_comments')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="graphic_content">Graphic Content URL</label>
            <input class="form__control" id="graphic_content" name="graphic_content" type="text"
                   value="<?= htmlspecialchars((string)$formData['graphic_content']) ?>">
            <button type="button" class="button button--light" data-open-media-picker="#graphic_content" style="margin-top:0.5rem;">Upload</button>
            <small style="color:#64748b;">Use this for the main graphic or video file.</small>
            <?php if ($error = field_error($errors, 'graphic_content')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
            <?php
            $graphicUrl = trim((string)$formData['graphic_content']);
            $graphicType = 'none';
            if ($graphicUrl !== '') {
                if (preg_match('/\.(mp4|webm|ogg|mov)(?:$|\?)/i', $graphicUrl)) {
                    $graphicType = 'video';
                } elseif (preg_match('/\.(jpe?g|png|gif|webp|svg)(?:$|\?)/i', $graphicUrl)) {
                    $graphicType = 'image';
                } else {
                    $graphicType = 'file';
                }
            }
            ?>
            <div class="media-preview <?= $graphicType === 'none' ? 'is-empty' : '' ?>" data-media-preview-for="graphic_content">
                <?php if ($graphicType === 'none'): ?>
                    <span class="media-preview__empty">No file selected.</span>
                <?php elseif ($graphicType === 'image'): ?>
                    <div class="media-preview__thumb"><img src="<?= htmlspecialchars($graphicUrl) ?>" alt=""></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($graphicUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php elseif ($graphicType === 'video'): ?>
                    <div class="media-preview__thumb"><video src="<?= htmlspecialchars($graphicUrl) ?>" controls></video></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($graphicUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php else: ?>
                    <div class="media-preview__thumb"><span class="media-preview__file-icon">File</span></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($graphicUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form__group">
            <label class="form__label" for="cover_image">Cover Image URL</label>
            <input class="form__control" id="cover_image" name="cover_image" type="text"
                   value="<?= htmlspecialchars((string)$formData['cover_image']) ?>">
            <button type="button" class="button button--light" data-open-media-picker="#cover_image" style="margin-top:0.5rem;">Upload</button>
            <?php if ($error = field_error($errors, 'cover_image')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
            <?php
            $coverUrl = trim((string)$formData['cover_image']);
            $coverType = 'none';
            if ($coverUrl !== '') {
                if (preg_match('/\.(mp4|webm|ogg|mov)(?:$|\?)/i', $coverUrl)) {
                    $coverType = 'video';
                } elseif (preg_match('/\.(jpe?g|png|gif|webp|svg)(?:$|\?)/i', $coverUrl)) {
                    $coverType = 'image';
                } else {
                    $coverType = 'file';
                }
            }
            ?>
            <div class="media-preview <?= $coverType === 'none' ? 'is-empty' : '' ?>" data-media-preview-for="cover_image">
                <?php if ($coverType === 'none'): ?>
                    <span class="media-preview__empty">No file selected.</span>
                <?php elseif ($coverType === 'image'): ?>
                    <div class="media-preview__thumb"><img src="<?= htmlspecialchars($coverUrl) ?>" alt=""></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($coverUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php elseif ($coverType === 'video'): ?>
                    <div class="media-preview__thumb"><video src="<?= htmlspecialchars($coverUrl) ?>" controls></video></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($coverUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php else: ?>
                    <div class="media-preview__thumb"><span class="media-preview__file-icon">File</span></div>
                    <div class="media-preview__meta"><a href="<?= htmlspecialchars($coverUrl) ?>" target="_blank" rel="noopener">Open in new tab</a></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form__group">
            <label class="form__label" for="categories">Categories</label>
            <select class="form__control" id="categories" name="categories[]" multiple size="4">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= in_array((int)$category['id'], $formData['categories'], true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($error = field_error($errors, 'categories')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url('posts/index.php')) ?>">Cancel</a>
            <button class="button" type="submit">Save Changes</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../views/partials/media-picker.php'; ?>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
