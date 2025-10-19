<?php if ($message = flash('success')): ?>
    <div class="alert alert--success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($message = flash('error')): ?>
    <div class="alert alert--error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
