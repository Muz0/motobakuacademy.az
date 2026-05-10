<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use MotoBaku\Admin\CSRF;

$auth = app('auth');
$db = app('db');

if (!$auth) {
    flash('error', 'Authentication service is unavailable.');
    redirect(base_url('login.php'));
}

$auth->requireAuth();
$currentUser = $auth->user();

if (!$currentUser) {
    flash('error', 'Unable to load your account. Please sign in again.');
    redirect(base_url('login.php'));
}

if (!$db) {
    flash('error', 'Database connection is unavailable.');
    redirect(base_url());
}

$title = 'Change Password';
$errors = [
    'general' => [],
];

if (is_post()) {
    $token = $_POST['_token'] ?? null;
    if (!CSRF::validate($token)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('password.php'));
    }

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['new_password_confirmation'] ?? '');

    if ($currentPassword === '') {
        $errors['current_password'][] = 'Current password is required.';
    }

    if ($newPassword === '') {
        $errors['new_password'][] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors['new_password'][] = 'New password must be at least 8 characters.';
    }

    if ($confirmPassword === '') {
        $errors['new_password_confirmation'][] = 'Please confirm the new password.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $errors['new_password_confirmation'][] = 'Passwords do not match.';
    }

    if (empty($errors['current_password']) && !password_verify($currentPassword, (string)$currentUser['password_hash'])) {
        $errors['current_password'][] = 'Current password is incorrect.';
    }

    if (empty(array_filter($errors))) {
        try {
            $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute([
                ':hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                ':id' => (int)$currentUser['id'],
            ]);

            // Refresh the cached user/session so new password takes effect immediately.
            $auth->login((int)$currentUser['id']);

            flash('success', 'Password updated successfully.');
            redirect(base_url('password.php'));
        } catch (\Throwable $exception) {
            $errors['general'][] = config('app.debug', false)
                ? $exception->getMessage()
                : 'Unable to update password. Please try again.';
        }
    }
}

include __DIR__ . '/views/layout/header.php';
?>

<?php include __DIR__ . '/views/partials/flash.php'; ?>

<section class="card" style="max-width: 600px;">
    <h1 class="card__title">Change Password</h1>
    <p class="card__subtitle" style="margin-top:0; color:#64748b;">Update your password to keep your account secure.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error">
            <?= htmlspecialchars(implode(' ', $errors['general'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(base_url('password.php')) ?>" class="form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="current_password">Current password</label>
            <input class="form__control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            <?php if ($error = $errors['current_password'][0] ?? null): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="new_password">New password</label>
            <input class="form__control" type="password" id="new_password" name="new_password" autocomplete="new-password" required>
            <?php if ($error = $errors['new_password'][0] ?? null): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__group">
            <label class="form__label" for="new_password_confirmation">Confirm new password</label>
            <input class="form__control" type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" required>
            <?php if ($error = $errors['new_password_confirmation'][0] ?? null): ?>
                <small style="color:#dc2626;"><?= htmlspecialchars($error) ?></small>
            <?php endif; ?>
        </div>

        <div class="form__actions">
            <a class="button button--light" href="<?= htmlspecialchars(base_url()) ?>">Cancel</a>
            <button class="button" type="submit">Update password</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/views/layout/footer.php'; ?>
