<?php

use MotoBaku\Admin\Auth;
use MotoBaku\Admin\CSRF;

$title = $title ?? 'MotoBaku Admin';
$isGuest = $isGuest ?? false;

/** @var Auth|null $auth */
$auth = app('auth');
$currentUser = $auth?->user();
$currentPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$navItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => base_url(), 'match' => '/admin/index.php'],
    ['label' => 'All Posts', 'icon' => 'bi-file-earmark-text', 'url' => base_url('posts/index.php'), 'match' => '/admin/posts/index.php'],
    ['label' => 'New Post', 'icon' => 'bi-plus-square', 'url' => base_url('posts/create.php'), 'match' => '/admin/posts/create.php'],
    ['label' => 'Categories', 'icon' => 'bi-tags', 'url' => base_url('categories/index.php'), 'match' => '/admin/categories/'],
    ['label' => 'Comments', 'icon' => 'bi-chat-left-text', 'url' => base_url('comments/index.php'), 'match' => '/admin/comments/'],
    ['label' => 'Media Library', 'icon' => 'bi-images', 'url' => base_url('media/index.php'), 'match' => '/admin/media/'],
    ['label' => 'About/Team', 'icon' => 'bi-people', 'url' => base_url('team/index.php'), 'match' => '/admin/team/'],
    ['label' => 'Change Password', 'icon' => 'bi-shield-lock', 'url' => base_url('password.php'), 'match' => '/admin/password.php'],
];

$isActive = static function (string $match) use ($currentPath): bool {
    if ($match === '/admin/index.php') {
        return str_ends_with($currentPath, '/admin') || str_ends_with($currentPath, '/admin/') || str_ends_with($currentPath, '/admin/index.php');
    }

    return str_contains($currentPath, $match);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> &middot; MotoBaku Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc7/dist/css/adminlte.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= htmlspecialchars(base_url('assets/css/admin.css')) ?>">
</head>
<body class="<?= $isGuest ? 'layout-guest login-page bg-body-tertiary' : 'layout-app layout-fixed sidebar-expand-lg bg-body-tertiary' ?>">
<?php if (!$isGuest): ?>
    <div class="app-wrapper">
        <nav class="topbar app-header navbar navbar-expand bg-body border-bottom">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="<?= htmlspecialchars(base_url()) ?>" class="nav-link">Dashboard</a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="<?= htmlspecialchars(base_url('posts/index.php')) ?>" class="nav-link">Posts</a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="<?= htmlspecialchars(base_url('media/index.php')) ?>" class="nav-link">Media</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if ($currentUser): ?>
                        <li class="nav-item d-flex align-items-center me-3">
                            <span class="navbar-text fw-semibold"><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
                        </li>
                        <li class="nav-item">
                            <form method="post" action="<?= htmlspecialchars(base_url('logout.php')) ?>" class="m-0">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                    <i class="bi bi-box-arrow-right me-1"></i> Sign out
                                </button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <aside class="sidebar app-sidebar bg-dark shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="<?= htmlspecialchars(base_url()) ?>" class="brand-link">
                    <span class="brand-text fw-semibold">MotoBaku Admin</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <?php foreach ($navItems as $item): ?>
                            <li class="nav-item">
                                <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-link <?= $isActive($item['match']) ? 'active' : '' ?>">
                                    <i class="nav-icon bi <?= htmlspecialchars($item['icon']) ?>"></i>
                                    <p><?= htmlspecialchars($item['label']) ?></p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="content app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= htmlspecialchars($title) ?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
<?php else: ?>
    <main class="content content--guest">
<?php endif; ?>
