<?php

use MotoBaku\Admin\CSRF;

$mediaApiUrl = base_url('media/api.php');
$csrfToken = CSRF::getToken();
?>
<div class="media-picker" data-media-picker-modal data-media-api="<?= htmlspecialchars($mediaApiUrl) ?>" data-csrf="<?= htmlspecialchars($csrfToken) ?>" hidden>
    <div class="media-picker__overlay" data-media-close></div>
    <div class="media-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaPickerTitle">
        <header class="media-picker__header">
            <h2 class="media-picker__title" id="mediaPickerTitle">Media Library</h2>
            <button type="button" class="media-picker__close" data-media-close aria-label="Close media picker">×</button>
        </header>
        <nav class="media-picker__tabs" aria-label="Media picker tabs">
            <button type="button" class="media-picker__tab is-active" data-media-tab="upload">Upload</button>
            <button type="button" class="media-picker__tab" data-media-tab="library">Library</button>
        </nav>
        <section class="media-picker__panel is-active" data-media-panel="upload" aria-label="Upload media">
            <form data-media-upload-form enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label class="media-picker__form-label" for="mediaPickerFile">Select a file to upload</label>
                <input class="media-picker__file-input" type="file" id="mediaPickerFile" name="media" accept="image/*,video/*" required>
                <small class="media-picker__helper">Allowed types: JPG, PNG, GIF, WEBP, SVG, MP4, WebM, OGG, MOV.</small>
                <div class="media-picker__actions">
                    <button class="button" type="submit">Upload</button>
                </div>
                <p class="media-picker__feedback" data-media-upload-feedback></p>
            </form>
        </section>
        <section class="media-picker__panel" data-media-panel="library" aria-label="Choose from library">
            <div class="media-picker__library" data-media-library>
                <p style="color:#64748b;">No files yet. Upload a new image or video.</p>
            </div>
        </section>
    </div>
</div>
