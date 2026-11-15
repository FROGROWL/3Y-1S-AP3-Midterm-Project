</div>
<dialog id="app-dialog" class="app-dialog" aria-labelledby="app-dialog-title">
    <div class="app-dialog-header">
        <h2 id="app-dialog-title" class="title is-5" data-dialog-title></h2>
        <button type="button" class="delete" aria-label="Close" data-dialog-close></button>
    </div>
    <div class="app-dialog-content" data-dialog-content></div>
    <div class="app-dialog-actions" data-dialog-actions></div>
</dialog>
<footer class="footer app-footer">
    <div class="content has-text-centered">
        AgriInfo | Agriculture Information System | <?= date('Y') ?>
    </div>
</footer>
<script type="module" src="assets/app.js?v=<?= (int) filemtime(__DIR__ . '/../assets/app.js') ?>"></script>
</body>
</html>
