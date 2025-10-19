<?php if (!$isGuest): ?>
        </main>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>
    <footer class="footer">
        <small>&copy; <?= date('Y') ?> MotoBaku Academy. All rights reserved.</small>
    </footer>
    <script src="<?= htmlspecialchars(base_url('assets/js/admin.js')) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
</body>
</html>
