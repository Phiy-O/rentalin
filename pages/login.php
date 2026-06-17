<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/flash.php';

if (isset($_SESSION['user_id'])) {
    redirect_route('catalog');
}

require_once __DIR__ . '/../includes/header.php';
?>

<header class="auth-topbar">
    <div class="container auth-topbar-inner">
        <a class="auth-logo" href="<?= route('home'); ?>"><?= APP_NAME; ?></a>
        <a class="auth-help" href="<?= route('contact'); ?>">Butuh Bantuan?</a>
    </div>
</header>

<main class="auth-login-page">
    <div class="container auth-login-grid">
        <section class="auth-brand-panel">
            <div class="auth-brand-mark">
                <span class="auth-key-icon"></span>
                <strong><?= APP_NAME; ?></strong>
            </div>
            <p>Sewa barang, buka toko rental, dan kelola transaksi dalam satu platform.</p>
        </section>

        <section class="auth-login-card">
            <div class="auth-login-heading">
                <p>Selamat datang kembali</p>
                <h1>Log In</h1>
            </div>

            <?php show_flash(); ?>

            <form class="auth-login-form" action="<?= route('auth.login'); ?>" method="POST">
                <div class="form-group auth-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="nama@email.com" required>
                </div>

                <div class="form-group auth-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>

                <div class="auth-form-row">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>
                    <a href="<?= route('help'); ?>">Lupa Password?</a>
                </div>

                <button class="auth-submit" type="submit">Masuk</button>
            </form>

            <div class="auth-divider">
                <span></span>
                <p>ATAU</p>
                <span></span>
            </div>

            <a class="auth-secondary-button" href="<?= route('register'); ?>">Daftar sebagai pengguna baru</a>

            <p class="form-note">
                Belum punya akun?
                <a href="<?= route('register'); ?>">Daftar sekarang</a>
            </p>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
