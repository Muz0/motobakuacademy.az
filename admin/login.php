<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use MotoBaku\Admin\CSRF;

$auth = app('auth');

if ($auth?->check()) {
    redirect(base_url());
}

if (is_post()) {
    $token = $_POST['_token'] ?? null;

    if (!CSRF::validate($token)) {
        flash('error', 'Your session has expired. Please try again.');
        redirect(base_url('login.php'));
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    remember_input(['username' => $username]);

    if ($username === '' || $password === '') {
        flash('error', 'Username and password are required.');
        redirect(base_url('login.php'));
    }

    if ($auth && $auth->attempt($username, $password)) {
        clear_old_input();
        flash('success', 'Welcome back!');
        redirect(base_url());
    }

    flash('error', 'Invalid credentials. Please try again.');
    redirect(base_url('login.php'));
}

clear_old_input();

$title = 'Sign in';
$isGuest = true;

include __DIR__ . '/views/layout/header.php';
?>

<section class="card" style="max-width: 420px; width: 100%;">
    <h1 class="card__title">MotoBaku Admin</h1>
    <p class="card__subtitle" style="margin-top:0; color:#64748b;">Sign in to manage blog content.</p>

    <?php include __DIR__ . '/views/partials/flash.php'; ?>

    <form class="form" method="post" action="<?= htmlspecialchars(base_url('login.php')) ?>">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">

        <div class="form__group">
            <label class="form__label" for="username">Username</label>
            <input class="form__control" id="username" name="username" type="text" value="<?= htmlspecialchars((string)old('username', '')) ?>" autocomplete="username" required>
        </div>

        <div class="form__group">
            <label class="form__label" for="password">Password</label>
            <input class="form__control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>

        <div class="form__actions">
            <button class="button" type="submit">Sign in</button>
        </div>
    </form>
</section>

<?php include __DIR__ . '/views/layout/footer.php'; ?>
