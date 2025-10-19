<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CategoryRepository;
use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\Validation;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/** @var CategoryRepository|null $categoriesRepo */
$categoriesRepo = app('categories');

if (!$categoriesRepo) {
    flash('error', 'Category repository unavailable.');
    redirect(base_url('categories/index.php'));
}

$category = $categoriesRepo->find($id);

if (!$category) {
    flash('error', 'Category not found.');
    redirect(base_url('categories/index.php'));
}

$title = 'Edit Category';
$errors = [];
$formData = [
    'name' => $category['name'],
    'slug' => $category['slug'],
];

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('categories/edit.php?id=' . $id));
    }

    $validation = Validation::make($_POST, [
        'name' => 'required|string|min:2|max:100',
        'slug' => 'required|string|slug|max:100',
    ]);
    $data = $validation['data'] ?? [];
    $errors = $validation['errors'] ?? [];

    $data['name'] = trim((string)($data['name'] ?? ''));
    $data['slug'] = trim((string)($data['slug'] ?? ''));

    $slug = $data['slug'] ?: slugify((string)$data['name']);
    if ($slug === '') {
        $errors['slug'][] = 'Unable to generate slug. Please provide one manually.';
    } elseif ($categoriesRepo->existsBySlug($slug, $id)) {
        $errors['slug'][] = 'Slug already exists.';
    }
    $data['slug'] = $slug;

    if (empty($errors)) {
        try {
            $categoriesRepo->update($id, [
                'name' => $data['name'],
                'slug' => $data['slug'],
            ]);

            flash('success', 'Category updated successfully.');
            redirect(base_url('categories/index.php'));
        } catch (\PDOException $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to update category. Please try again.';
        }
    }

    $formData = array_merge($formData, $data);
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <h1 class="card__title">Edit Category</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('categories/edit.php?id=' . $id)) ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="name">Name</label>
            <input class="form__control" id="name" name="name" type="text" required
                   value="<?= htmlspecialchars($formData['name']) ?>" data-slug-source>
            <?php if ($error = field_error($errors, 'name')): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="slug">Slug</label>
            <input class="form__control" id="slug" name="slug" type="text"
                   value="<?= htmlspecialchars($formData['slug']) ?>"
                   data-slug-target data-slug-source="#name">
            <?php if ($error = field_error($errors, 'slug')): ?>
                <small style="color:#dc2626; display:block;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url('categories/index.php')) ?>">Cancel</a>
            <button class="button" type="submit">Save Changes</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
