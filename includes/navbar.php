<?php
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'User';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isCatalogPage = $currentPage === 'catalog.php';
$isProfilePage = $currentPage === 'profile.php';
$isProductDetailPage = $currentPage === 'product-detail.php';
$useCatalogNavbar = $isCatalogPage || $isProfilePage || $isProductDetailPage;
?>
<nav class="navbar <?= $useCatalogNavbar ? 'catalog-navbar' : ''; ?>">
    <div class="navbar-inner container">
        <?php if ($useCatalogNavbar): ?>
            <div class="navbar-logo">
                <a href="<?= route('catalog'); ?>"><?= APP_NAME; ?></a>
            </div>
        <?php else: ?>
            <div class="navbar-logo">
                <a href="<?= route('home'); ?>"><?= APP_NAME; ?></a>
            </div>
        <?php endif; ?>

        <?php if ($useCatalogNavbar): ?>
            <form class="catalog-nav-search" action="<?= route('catalog'); ?>" method="GET">
                <span class="search-icon" aria-hidden="true"></span>
                <input type="text" name="search" placeholder="Cari barang rental...">
            </form>

            <div class="catalog-nav-actions">
                <a class="catalog-nav-icon cart-icon" href="<?= route('cart'); ?>" aria-label="Keranjang rental"></a>
                <a class="catalog-nav-icon bell-icon" href="<?= route('notifications'); ?>" aria-label="Notifikasi"></a>
                <span class="catalog-nav-divider"></span>
                <a class="catalog-nav-icon store-icon" href="<?= route('toko.dashboard'); ?>" aria-label="Toko"></a>
                <a class="catalog-nav-user" href="<?= route('profile'); ?>"><span class="user-icon"></span><?= htmlspecialchars($username); ?></a>
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
