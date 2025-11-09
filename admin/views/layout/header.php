<?php

use MotoBaku\Admin\Auth;

$title = $title ?? 'MotoBaku Admin';
$isGuest = $isGuest ?? false;

/** @var Auth|null $auth */
$auth = app('auth');
$currentUser = $auth?->user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> · MotoBaku Admin</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(base_url('assets/css/admin.css')) ?>">
</head>
<body class="<?= $isGuest ? 'layout-guest' : 'layout-app' ?>">
<?php if (!$isGuest): ?>
    <header class="topbar">
        <div class="topbar__brand">
            <a href="<?= htmlspecialchars(base_url()) ?>" class="brand-link">MotoBaku Admin</a>
        </div>
        <nav class="topbar__nav">
            <a href="<?= htmlspecialchars(base_url()) ?>">Dashboard</a>
            <a href="<?= htmlspecialchars(base_url('posts/index.php')) ?>">Posts</a>
            <a href="<?= htmlspecialchars(base_url('categories/index.php')) ?>">Categories</a>
            <a href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Comments</a>
            <a href="<?= htmlspecialchars(base_url('media/index.php')) ?>">Media</a>
        </nav>
        <div class="topbar__user">
            <?php if ($currentUser): ?>
                <span class="topbar__user-name"><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
                <a class="button button--light" href="<?= htmlspecialchars(base_url('logout.php')) ?>">Sign out</a>
            <?php endif; ?>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar__nav">
                <a href="<?= htmlspecialchars(base_url()) ?>">Overview</a>
                <a href="<?= htmlspecialchars(base_url('posts/index.php')) ?>">All Posts</a>
                <a href="<?= htmlspecialchars(base_url('posts/create.php')) ?>">New Post</a>
                <a href="<?= htmlspecialchars(base_url('categories/index.php')) ?>">Categories</a>
                <a href="<?= htmlspecialchars(base_url('comments/index.php')) ?>">Comments</a>
                <a href="<?= htmlspecialchars(base_url('media/index.php')) ?>">Media Library</a>
                <a href="<?= htmlspecialchars(base_url('password.php')) ?>">Change Password</a>
            </nav>
        </aside>
        <main class="content">
<?php else: ?>
    <main class="content content--guest">
<?php endif; ?>
