<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\MediaService;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

$title = 'Media Library';
$uploadPath = MediaService::uploadDirectory();
$files = MediaService::listMedia();
$writable = is_dir($uploadPath) && is_writable($uploadPath);

if (is_post()) {
    if (!CSRF::validate($_POST['_token'] ?? null)) {
        flash('error', 'Session expired. Please try again.');
        redirect(base_url('media/index.php'));
    }

    try {
        $info = MediaService::storeUploadedFile($_FILES['media'] ?? []);
        flash('success', 'Uploaded ' . $info['name'] . ' successfully.');
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
    }

    redirect(base_url('media/index.php'));
}

include __DIR__ . '/../views/layout/header.php';
?>

<?php include __DIR__ . '/../views/partials/flash.php'; ?>

<section class="card">
    <h1 class="card__title">Media Library</h1>
    <p style="color:#64748b; margin-top:0;">
        Upload images (max 5MB) or videos (max 50MB). Files are stored under <code>/admin/storage/uploads</code>.
    </p>

    <form method="post" enctype="multipart/form-data" class="card" style="background:#f8fafc; border:1px dashed #cbd5e1; padding:1rem; border-radius:10px;">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(CSRF::getToken()) ?>">
        <div class="form__group" style="margin-bottom:1rem;">
            <label class="form__label" for="media-upload">Select file</label>
            <input class="form__control" type="file" id="media-upload" name="media" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg,video/quicktime" required>
            <small style="color:#475569;">Allowed types: JPG, PNG, GIF, WEBP, MP4, WebM, OGG, MOV.</small>
        </div>
        <div class="form__actions" style="justify-content:flex-start;">
            <button class="button" type="submit">Upload</button>
        </div>
    </form>

    <?php if (!$writable): ?>
        <div class="alert alert--error" style="margin-top:1rem;">
            The uploads directory is not writable. Update permissions after deployment (chmod 755).
        </div>
    <?php endif; ?>

    <h2 style="margin-top:2rem;">Stored Files</h2>

    <?php if (empty($files)): ?>
        <p style="color:#64748b;">No files uploaded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Preview</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                    <th>URL</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?= htmlspecialchars($file['name']) ?></td>
                        <td>
                            <?php if ($file['type'] === 'image'): ?>
                                <img src="<?= htmlspecialchars($file['url']) ?>" alt="" style="max-width:90px; max-height:60px; object-fit:cover; border:1px solid #e2e8f0; border-radius:6px;">
                            <?php elseif ($file['type'] === 'video'): ?>
                                <span style="color:#64748b;">Video</span>
                            <?php else: ?>
                                <span style="color:#64748b;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($file['size_label']) ?></td>
                        <td><?= htmlspecialchars($file['modified_label']) ?></td>
                        <td>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="text" class="form__control" value="<?= htmlspecialchars($file['url']) ?>" readonly style="max-width:260px;">
                                <button class="button button--light" type="button" data-copy-url="<?= htmlspecialchars($file['url']) ?>">Copy</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>
