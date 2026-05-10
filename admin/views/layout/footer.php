<?php if (!$isGuest): ?>
                </div>
            </div>
        </main>
        <footer class="footer app-footer">
            <small>&copy; <?= date('Y') ?> MotoBaku Academy. All rights reserved.</small>
        </footer>
    </div>
<?php else: ?>
    </main>
    <footer class="footer">
        <small>&copy; <?= date('Y') ?> MotoBaku Academy. All rights reserved.</small>
    </footer>
<?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc7/dist/js/adminlte.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="<?= htmlspecialchars(base_url('assets/js/admin.js')) ?>"></script>
</body>
</html>
