<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\TeamRepository;
use MotoBaku\Admin\Validation;

$auth = app('auth');
if ($auth) {
    $auth->requireAuth();
}

/** @var TeamRepository|null $teamRepo */
$teamRepo = app('team');

if (!$teamRepo) {
    echo "Team repository unavailable.";
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$member = $id ? $teamRepo->find($id) : null;

if (!$member) {
    flash('error', 'Team member not found.');
    redirect(base_url('team/index.php'));
}

$title = 'Edit team member';
$errors = [];

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('team/edit.php?id=' . $id));
    }

    $input = $_POST;
    $rules = [
        'name' => 'required|string|min:2|max:191',
        'role' => 'required|string|min:2|max:191',
        'description' => 'nullable|string|max:500',
        'photo_url' => 'required|string|min:5|max:255',
        'position' => 'nullable|integer|min:0|max:1000',
    ];

    $validation = Validation::make($input, $rules);
    $errors = $validation['errors'] ?? [];

    if (!empty($errors)) {
        flash('error', 'Please correct the errors below.');
    } else {
        $teamRepo->update($id, [
            'name' => trim((string)$input['name']),
            'role' => trim((string)$input['role']),
            'description' => trim((string)($input['description'] ?? '')),
            'photo_url' => trim((string)$input['photo_url']),
            'position' => isset($input['position']) && $input['position'] !== '' ? (int)$input['position'] : 0,
        ]);

        flash('success', 'Team member updated.');
        redirect(base_url('team/index.php'));
    }
}

include __DIR__ . '/../views/layout/header.php';
?>

<div class="page-head">
    <h1>Edit team member</h1>
</div>

<?php if ($message = flash('success')): ?>
    <div class="alert alert--success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert--danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card__head">
        <h2 class="card__title">Details</h2>
    </div>
    <div class="card__body">
        <form method="post" action="<?= htmlspecialchars(base_url('team/edit.php?id=' . $id)) ?>" class="form form--wide">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
            <div class="form-grid">
                <div class="form__group">
                    <label class="form__label" for="team-name">Name</label>
                    <input id="team-name" class="form__control" type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $member['name']) ?>" required>
                    <?php if (isset($errors['name'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['name'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-role">Role</label>
                    <input id="team-role" class="form__control" type="text" name="role" value="<?= htmlspecialchars($_POST['role'] ?? $member['role']) ?>" required>
                    <?php if (isset($errors['role'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['role'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group" style="grid-column: 1 / -1;">
                    <label class="form__label" for="team-description">Description (optional)</label>
                    <textarea id="team-description" class="form__control" name="description" rows="3" data-editor="rich-text-optional"><?= htmlspecialchars($_POST['description'] ?? ($member['description'] ?? '')) ?></textarea>
                    <?php if (isset($errors['description'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['description'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-photo">Photo URL</label>
                    <div class="input-with-actions">
                        <input id="team-photo" class="form__control" type="text" name="photo_url" value="<?= htmlspecialchars($_POST['photo_url'] ?? $member['photo_url']) ?>" required>
                        <button type="button" class="button button--light" data-open-media-picker="#team-photo">Upload / choose</button>
                    </div>
                    <small class="form__hint">Use the Media Library picker to upload or choose an existing image.</small>
                    <?php if (isset($errors['photo_url'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['photo_url'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-position">Position</label>
                    <input id="team-position" class="form__control" type="number" name="position" min="0" max="1000" value="<?= htmlspecialchars($_POST['position'] ?? $member['position']) ?>">
                    <small class="form__hint">Lower numbers appear first. Leave empty to auto-place.</small>
                    <?php if (isset($errors['position'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['position'][0]) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form__actions">
                <button type="submit" class="button button--primary">Save changes</button>
                <a class="button" href="<?= htmlspecialchars(base_url('team/index.php')) ?>">Back</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
<?php include __DIR__ . '/../views/partials/media-picker.php'; ?>
