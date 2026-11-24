        </main>

        <footer class="app-footer">
            <span>© <?= date('Y') ?> FrostMart · <?= e(APP_OWNER) ?></span>
            <span>Dibuat dengan PHP Native &amp; MySQL</span>
        </footer>
    </div>
</div>

<dialog class="confirm-dialog" id="confirmDialog" aria-labelledby="confirmTitle">
    <div class="confirm-dialog__icon" data-confirm-icon><?= icon('trash') ?></div>
    <h2 id="confirmTitle" data-confirm-title>Hapus data?</h2>
    <p data-confirm-message>Tindakan ini tidak bisa dibatalkan.</p>
    <div class="confirm-dialog__actions">
        <button class="btn btn--ghost" type="button" data-confirm-cancel>Batal</button>
        <button class="btn btn--danger" type="button" data-confirm-ok>Ya, hapus</button>
    </div>
</dialog>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
