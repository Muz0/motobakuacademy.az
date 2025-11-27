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

$title = 'Team members';
$errors = [];
$settingsErrors = [];
$aboutErrors = [];
$teamDescription = $teamRepo->getTeamDescription();
$aboutContent = $teamRepo->getAboutContent();

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('team/index.php'));
    }

    $action = $_POST['action'] ?? 'create';

    if ($action === 'settings') {
        $descInput = $_POST['team_description'] ?? '';
        $validation = Validation::make(
            ['team_description' => $descInput],
            ['team_description' => 'nullable|string|max:2000']
        );
        $settingsErrors = $validation['errors'] ?? [];
        if (!empty($settingsErrors)) {
            flash('error', 'Please correct the errors below.');
        } else {
            $teamRepo->saveTeamDescription(trim((string)$descInput));
            flash('success', 'Team description updated.');
            redirect(base_url('team/index.php'));
        }
    } elseif ($action === 'about') {
        $input = [
            'title_az' => $_POST['about_title_az'] ?? '',
            'title_ru' => $_POST['about_title_ru'] ?? '',
            'title_en' => $_POST['about_title_en'] ?? '',
            'content_az' => $_POST['about_content_az'] ?? '',
            'content_ru' => $_POST['about_content_ru'] ?? '',
            'content_en' => $_POST['about_content_en'] ?? '',
        ];

        $rules = [
            'title_az' => 'nullable|string|max:255',
            'title_ru' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_az' => 'nullable|string',
            'content_ru' => 'nullable|string',
            'content_en' => 'nullable|string',
        ];

        $validation = Validation::make($input, $rules);
        $aboutErrors = $validation['errors'] ?? [];

        if (!empty($aboutErrors)) {
            flash('error', 'Please correct the errors below.');
        } else {
            $teamRepo->saveAboutContent([
                'title_az' => trim((string)$input['title_az']),
                'title_ru' => trim((string)$input['title_ru']),
                'title_en' => trim((string)$input['title_en']),
                'content_az' => trim((string)$input['content_az']),
                'content_ru' => trim((string)$input['content_ru']),
                'content_en' => trim((string)$input['content_en']),
            ]);
            flash('success', 'About page content updated.');
            redirect(base_url('team/index.php'));
        }
    } else {
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
            $position = isset($input['position']) && $input['position'] !== ''
                ? (int)$input['position']
                : $teamRepo->nextPosition();

            $teamRepo->create([
                'name' => trim((string)$input['name']),
                'role' => trim((string)$input['role']),
                'description' => trim((string)($input['description'] ?? '')),
                'photo_url' => trim((string)$input['photo_url']),
                'position' => $position,
            ]);

            flash('success', 'Team member added.');
            redirect(base_url('team/index.php'));
        }
    }
}

$team = $teamRepo->all();

include __DIR__ . '/../views/layout/header.php';
?>

<div class="page-head">
    <h1>Team</h1>
    <p>Manage instructors and staff shown on the public site.</p>
</div>

<?php if ($message = flash('success')): ?>
    <div class="alert alert--success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert--danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<section class="card">
    <div class="card__head">
        <h2 class="card__title">Add team member</h2>
    </div>
    <div class="card__body">
        <form method="post" action="<?= htmlspecialchars(base_url('team/index.php')) ?>" class="form form--wide">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
            <div class="form-grid">
                <div class="form__group">
                    <label class="form__label" for="team-name">Name</label>
                    <input id="team-name" class="form__control" type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    <?php if (isset($errors['name'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['name'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-role">Role</label>
                    <input id="team-role" class="form__control" type="text" name="role" value="<?= htmlspecialchars($_POST['role'] ?? '') ?>" required>
                    <?php if (isset($errors['role'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['role'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group" style="grid-column: 1 / -1;">
                    <label class="form__label" for="team-description">Description (optional)</label>
                    <textarea id="team-description" class="form__control" name="description" rows="3" data-editor="rich-text-optional"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <?php if (isset($errors['description'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['description'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-photo">Photo URL</label>
                    <div class="input-with-actions">
                        <input id="team-photo" class="form__control" type="text" name="photo_url" value="<?= htmlspecialchars($_POST['photo_url'] ?? '') ?>" required placeholder="https://...">
                        <button type="button" class="button button--light" data-open-media-picker="#team-photo">Upload / choose</button>
                    </div>
                    <small class="form__hint">Use the Media Library picker to upload or choose an existing image.</small>
                    <?php if (isset($errors['photo_url'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['photo_url'][0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form__group">
                    <label class="form__label" for="team-position">Position (optional)</label>
                    <input id="team-position" class="form__control" type="number" name="position" min="0" max="1000" value="<?= htmlspecialchars($_POST['position'] ?? '') ?>">
                    <small class="form__hint">Lower numbers appear first. Leave empty to auto-place.</small>
                    <?php if (isset($errors['position'][0])): ?>
                        <small class="form__error"><?= htmlspecialchars($errors['position'][0]) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form__actions">
                <button type="submit" class="button button--primary">Add member</button>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <div class="card__head">
        <h2 class="card__title">Current team</h2>
    </div>
    <div class="card__body">
        <?php if (empty($team)): ?>
            <p>No team members yet.</p>
        <?php else: ?>
            <div class="table table--responsive">
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Position</th>
                        <th>Photo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($team as $member): ?>
                        <tr>
                            <td><?= (int)$member['id'] ?></td>
                            <td><?= htmlspecialchars($member['name']) ?></td>
                            <td><?= htmlspecialchars($member['role']) ?></td>
                            <td><?= (int)$member['position'] ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($member['photo_url']) ?>" target="_blank" rel="noopener">View</a>
                            </td>
                            <td class="table__actions">
                                <a class="button button--small" href="<?= htmlspecialchars(base_url('team/edit.php?id=' . (int)$member['id'])) ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(base_url('team/delete.php')) ?>" style="display:inline-block" onsubmit="return confirm('Delete this member?');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
                                    <button class="button button--small button--danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
<?php include __DIR__ . '/../views/partials/media-picker.php'; ?>
