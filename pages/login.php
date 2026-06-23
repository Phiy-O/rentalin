<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/remember-me.php';

if (isset($_SESSION['user_id']) || try_remember_login($conn)) {
    redirect_route('catalog');
}

require_once __DIR__ . '/../includes/header.php';
?>

<header class="auth-topbar">
    <div class="container auth-topbar-inner">
        <a class="auth-logo" href="<?= route('home'); ?>">
            <img src="/assets/images/rentalin-logo.png" alt="rentalin-logo">
        </a>
        <a class="auth-help" href="<?= route('contact'); ?>">Butuh Bantuan?</a>
    </div>
</header>

<main class="auth-login-page">
    <div class="container auth-login-grid">
        <section class="auth-brand-panel">
            <div class="auth-brand-mark">
                <img src="/assets/images/rentalin-logo-white.png" alt="rentalin-logo">
            </div>
            <p>Sewa barang lebih mudah, hemat, dan terpercaya dalam satu platform.</p>
        </section>

        <section class="auth-login-card">
            <div class="auth-login-heading">
                <p>Selamat datang kembali</p>
                <h1>Log In</h1>
            </div>

            <?php show_flash(); ?>

            <form class="auth-login-form" action="<?= route('auth.login'); ?>" method="POST">
                <?php csrf_field(); ?>
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
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
