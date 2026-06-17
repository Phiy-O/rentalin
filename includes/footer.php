<footer class="footer" id="footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <h2><?= defined('APP_NAME') ? APP_NAME : 'Rentalin'; ?></h2>
            <p>Platform rental barang untuk penyewa dan pemilik toko rental.</p>
            <div class="footer-socials">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div>
            <h3>Product</h3>
            <a href="<?= route('catalog'); ?>">Katalog</a>
            <a href="<?= route('register'); ?>">Buka Toko</a>
            <a href="<?= route('login'); ?>">Login</a>
        </div>
        <div>
            <h3>Company</h3>
            <a href="<?= route('home'); ?>#about">About</a>
            <a href="<?= route('home'); ?>#categories">Category</a>
            <a href="<?= route('home'); ?>#products">Products</a>
        </div>
        <div>
            <h3>Terms & Condition</h3>
            <a href="<?= route('terms.privacy'); ?>">Privacy Policy</a>
            <a href="<?= route('terms.rental'); ?>">Syarat Rental</a>
            <a href="<?= route('help'); ?>">Bantuan</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>copyright <?= date('Y'); ?> | <?= defined('APP_NAME') ? APP_NAME : 'Rentalin'; ?></p>
    </div>
</footer>
</body>
</html>
