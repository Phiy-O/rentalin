<?php
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'User';
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$currentPage = basename($currentScript);
$publicPages = ['index.php', 'about.php', 'services.php', 'contact.php'];
$isPublicPage = in_array($currentPage, $publicPages);
$useCatalogNavbar = !$isPublicPage;

$userStore = null;
if ($isLoggedIn && $useCatalogNavbar) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM stores WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userStore = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
?>
<nav class="navbar <?= $useCatalogNavbar ? 'catalog-navbar' : 'landing-navbar'; ?>">
    <div class="navbar-inner container">
        <?php if ($useCatalogNavbar): ?>
            <div class="navbar-logo">
                <a href="<?= route('catalog'); ?>">
                    <img src="<?= BASE_URL; ?>/assets/images/rentalin-logo.png" alt="rentalin-logo">
                </a>
            </div>
        <?php else: ?>
            <div class="navbar-logo">
                <a href="<?= route('home'); ?>">
                    <img src="<?= BASE_URL; ?>/assets/images/rentalin-logo.png" alt="rentalin-logo">
                </a>
            </div>
        <?php endif; ?>

        <?php if ($useCatalogNavbar): ?>
            <form class="catalog-nav-search" action="<?= route('catalog'); ?>" method="GET">
                <span class="search-icon" aria-hidden="true"></span>
                <input type="text" name="search" placeholder="Cari barang rental...">
            </form>

            <div class="catalog-nav-actions">
                <a class="catalog-nav-icon cart-icon" href="<?= route('cart'); ?>" aria-label="Keranjang rental"><?php render_icon('shopping-cart'); ?></a>
                <a class="catalog-nav-icon bell-icon" href="<?= route('notifications'); ?>" aria-label="Notifikasi"><?php render_icon('bell'); ?></a>
                <span class="catalog-nav-divider"></span>
                <div class="store-icon-wrapper">
                    <a class="catalog-nav-icon store-icon" href="<?= $userStore ? route('toko.dashboard') : route('toko.create'); ?>" aria-label="Toko"><?php render_icon('store'); ?></a>
                    <div class="store-popup">
                        <?php if ($userStore): ?>
                            <p class="store-popup-name"><?= htmlspecialchars($userStore['name']); ?></p>
                            <hr>
                            <a class="btn btn-primary btn-small btn-full" href="<?= route('toko.dashboard'); ?>">Lihat Toko</a>
                        <?php else: ?>
                            <p class="store-popup-desc">Belum punya toko?</p>
                            <hr>
                            <a class="btn btn-primary btn-small btn-full" href="<?= route('toko.create'); ?>">Buat Toko</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a class="catalog-nav-user" href="<?= route('profile'); ?>"><?php render_icon('circle-user-round'); ?><?= htmlspecialchars($username); ?></a>
            </div>
        <?php else: ?>
            <div class="navbar-menu">
                <a href="<?= route('home'); ?>#home">Home</a>
                <a href="<?= route('about'); ?>">About</a>
                <a href="<?= route('services'); ?>">Services</a>
                <a href="<?= route('contact'); ?>">Contact</a>
            </div>

            <div class="navbar-login">
                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-outline btn-small" href="<?= route('logout'); ?>">Logout</a>
                <?php else: ?>
                    <a class="btn btn-primary btn-small" href="<?= route('register'); ?>">Join us</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</nav>