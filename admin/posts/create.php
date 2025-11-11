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

/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');
/** @var PostRepository|null $postsRepo */
$postsRepo = app('posts');

$categories = $categoriesRepo ? $categoriesRepo->all() : [];
$title = 'Create Post';
$errors = [];

$languages = [
    'az' => 'Azerbaijani',
    'ru' => 'Russian',
    'en' => 'English',
];

$formData = [
    'slug' => '',
    'status' => 'draft',
    'published_at' => '',
    'categories' => [],
    'accepts_comments' => true,
];

foreach ($languages as $code => $label) {
    $formData["title_{$code}"] = '';
    $formData["summary_{$code}"] = '';
    $formData["content_{$code}"] = '';
    $formData["cover_image_{$code}"] = '';
    $formData["graphic_content_{$code}"] = '';
}

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('posts/create.php'));
    }

    $input = $_POST;
    $input['categories'] = $input['categories'] ?? [];

    $rules = [
        'slug' => 'nullable|string|slug|max:255',
        'status' => 'required|string|in:draft,published',
        'published_at' => 'nullable|string',
        'categories' => 'nullable|array',
        'accepts_comments' => 'nullable|string|in:on',
    ];

    foreach ($languages as $code => $label) {
        $rules["title_{$code}"] = $code === 'az'
            ? 'required|string|min:3|max:255'
            : 'nullable|string|max:255';
        $rules["summary_{$code}"] = 'nullable|string|max:500';
        $rules["content_{$code}"] = $code === 'az'
            ? 'required|string|min:10'
            : 'nullable|string|min:10';
        $rules["cover_image_{$code}"] = 'nullable|string|max:255';
        $rules["graphic_content_{$code}"] = 'nullable|string|max:255';
    }

    $validation = Validation::make($input, $rules);
    $data = $validation['data'] ?? [];
    $errors = $validation['errors'] ?? [];

    $data['slug'] = trim((string)($data['slug'] ?? ''));
    $data['status'] = (string)($data['status'] ?? 'draft');

    foreach ($languages as $code => $label) {
        $titleKey = "title_{$code}";
        $summaryKey = "summary_{$code}";
        $contentKey = "content_{$code}";
        $coverKey = "cover_image_{$code}";
        $graphicKey = "graphic_content_{$code}";

        $data[$titleKey] = trim((string)($data[$titleKey] ?? ''));

        $summaryValue = $data[$summaryKey] ?? null;
        if (is_string($summaryValue)) {
            $summaryValue = trim($summaryValue);
        }
        $data[$summaryKey] = $summaryValue === '' ? null : $summaryValue;

        $data[$contentKey] = (string)($data[$contentKey] ?? '');

        $coverValue = $data[$coverKey] ?? null;
        if (is_string($coverValue)) {
            $coverValue = trim($coverValue);
        }
        $data[$coverKey] = $coverValue === '' ? null : $coverValue;

        $graphicValue = $data[$graphicKey] ?? null;
        if (is_string($graphicValue)) {
            $graphicValue = trim($graphicValue);
        }
        $data[$graphicKey] = $graphicValue === '' ? null : $graphicValue;
    }

    foreach (['title', 'summary', 'content', 'cover_image', 'graphic_content'] as $field) {
        $sourceKey = "{$field}_az";
        foreach ($languages as $code => $label) {
            if ($code === 'az') {
                continue;
            }
            $targetKey = "{$field}_{$code}";
            $value = $data[$targetKey];
            $isEmpty = $field === 'content'
                ? ($value === '' || $value === null)
                : ($value === null || $value === '');
            if ($isEmpty) {
                $data[$targetKey] = $data[$sourceKey];
            }
        }
    }

    $data['categories'] = array_values(array_unique(
        array_map(static fn($value) => (int)$value, (array)($data['categories'] ?? []))
    ));
    $data['accepts_comments'] = isset($input['accepts_comments']) && $input['accepts_comments'] === 'on';

    $slug = $data['slug'] ?: slugify((string)$data['title_az']);
    if ($slug === '') {
        $errors['slug'][] = 'Unable to generate slug. Please provide one manually.';
    } elseif ($postsRepo && $postsRepo->existsBySlug($slug)) {
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

    if (empty($errors) && $postsRepo instanceof PostRepository) {
        try {
            $payload = [
                'slug' => $data['slug'],
                'status' => $data['status'],
                'published_at' => $data['published_at'],
                'categories' => $data['categories'],
                'accepts_comments' => $data['accepts_comments'],
            ];

            foreach ($languages as $code => $label) {
                $payload["title_{$code}"] = $data["title_{$code}"];
                $payload["summary_{$code}"] = $data["summary_{$code}"];
                $payload["content_{$code}"] = $data["content_{$code}"];
                $payload["cover_image_{$code}"] = $data["cover_image_{$code}"];
                $payload["graphic_content_{$code}"] = $data["graphic_content_{$code}"];
            }

            $postsRepo->create($payload);

            flash('success', 'Post created successfully.');
            redirect(base_url('posts/index.php'));
        } catch (\PDOException $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to create post. Please try again.';
        }
    }

    $formData = array_merge($formData, [
        'slug' => $data['slug'] ?? '',
        'status' => $data['status'] ?? 'draft',
        'published_at' => $publishedInput,
        'categories' => $data['categories'] ?? [],
        'accepts_comments' => (bool)($data['accepts_comments'] ?? false),
    ]);

    foreach ($languages as $code => $label) {
        $formData["title_{$code}"] = $data["title_{$code}"] ?? '';
        $formData["summary_{$code}"] = $data["summary_{$code}"] ?? '';
        $formData["content_{$code}"] = $data["content_{$code}"] ?? '';
        $formData["cover_image_{$code}"] = $data["cover_image_{$code}"] ?? '';
        $formData["graphic_content_{$code}"] = $data["graphic_content_{$code}"] ?? '';
    }
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <h1 class="card__title">Create New Post</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('posts/create.php')) ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <?php foreach ($languages as $code => $label): ?>
            <div style="border:1px solid #e5e7eb; border-radius:0.5rem; padding:1rem; margin-bottom:1.5rem;">
                <h2 style="margin-top:0; font-size:1.1rem; display:flex; justify-content:space-between; align-items:center;">
                    <span><?= htmlspecialchars($label) ?> content</span>
                    <span style="font-size:0.85rem; color:#6b7280;"><?= strtoupper($code) ?></span>
                </h2>

                <div class="form__group">
                    <label class="form__label" for="title_<?= $code ?>">Title (<?= strtoupper($code) ?>)</label>
                    <input class="form__control" id="title_<?= $code ?>" name="title_<?= $code ?>" type="text"
                           <?= $code === 'az' ? 'required data-slug-source' : '' ?>
                           value="<?= htmlspecialchars($formData["title_{$code}"]) ?>">
                    <?php if ($error = field_error($errors, "title_{$code}")): ?>
                        <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form__group">
                    <label class="form__label" for="summary_<?= $code ?>">Summary (<?= strtoupper($code) ?>)</label>
                    <textarea class="form__control" id="summary_<?= $code ?>" name="summary_<?= $code ?>" rows="3"><?= htmlspecialchars((string)$formData["summary_{$code}"]) ?></textarea>
                    <small style="color:#64748b;">Shown on listing pages. Leave blank to reuse the Azerbaijani summary.</small>
                    <?php if ($error = field_error($errors, "summary_{$code}")): ?>
                        <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form__group">
                    <label class="form__label" for="content_<?= $code ?>">Content (<?= strtoupper($code) ?>)</label>
                    <textarea class="form__control" id="content_<?= $code ?>" name="content_<?= $code ?>"
                              data-editor="rich-text" rows="10"><?= htmlspecialchars($formData["content_{$code}"]) ?></textarea>
                    <?php if ($error = field_error($errors, "content_{$code}")): ?>
                        <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form__group">
                    <label class="form__label" for="cover_image_<?= $code ?>">Cover Image URL (<?= strtoupper($code) ?>)</label>
                    <input class="form__control" id="cover_image_<?= $code ?>" name="cover_image_<?= $code ?>" type="text"
                           value="<?= htmlspecialchars((string)$formData["cover_image_{$code}"]) ?>">
                    <button type="button" class="button button--light" data-open-media-picker="#cover_image_<?= $code ?>" style="margin-top:0.5rem;">Upload</button>
                    <small style="color:#64748b;">Leave blank to reuse the Azerbaijani cover.</small>
                    <?php if ($error = field_error($errors, "cover_image_{$code}")): ?>
                        <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form__group">
                    <label class="form__label" for="graphic_content_<?= $code ?>">Graphic/Video URL (<?= strtoupper($code) ?>)</label>
                    <input class="form__control" id="graphic_content_<?= $code ?>" name="graphic_content_<?= $code ?>" type="text"
                           value="<?= htmlspecialchars((string)$formData["graphic_content_{$code}"]) ?>">
                    <button type="button" class="button button--light" data-open-media-picker="#graphic_content_<?= $code ?>" style="margin-top:0.5rem;">Upload</button>
                    <small style="color:#64748b;">Leave blank to reuse the Azerbaijani asset.</small>
                    <?php if ($error = field_error($errors, "graphic_content_{$code}")): ?>
                        <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form__group">
            <label class="form__label" for="slug">Slug</label>
            <input class="form__control" id="slug" name="slug" type="text"
                   value="<?= htmlspecialchars($formData['slug']) ?>"
                   data-slug-target data-slug-source="#title_az">
            <small style="color:#64748b;">Used in the URL. Leave empty to auto-generate.</small>
            <?php if ($error = field_error($errors, 'slug')): ?>
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
            <small style="color:#64748b;">Uncheck to disable future comments for this post.</small>
            <?php if ($error = field_error($errors, 'accepts_comments')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
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
            <small style="color:#64748b;">Hold Ctrl (Cmd on Mac) to select multiple.</small>
            <?php if ($error = field_error($errors, 'categories')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url('posts/index.php')) ?>">Cancel</a>
            <button class="button" type="submit">Publish</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../views/partials/media-picker.php'; ?>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
