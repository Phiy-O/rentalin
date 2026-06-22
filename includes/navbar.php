<?php
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'User';
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$currentPage = basename($currentScript);
$publicPages = ['index.php', 'about.php', 'services.php', 'contact.php'];
$isPublicPage = in_array($currentPage, $publicPages);
$useCatalogNavbar = !$isPublicPage;

$userStore = null;
$cartCount = 0;
$cartPopupItems = [];
$navbarProfileImageUrl = '';
if ($isLoggedIn && $useCatalogNavbar) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/image-helper.php';

    $userStmt = mysqli_prepare($conn, "SELECT username, profile_image FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($userStmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userRow = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);

    if ($userRow) {
        $username = $userRow['username'] ?: $username;

        if (!empty($userRow['profile_image'])) {
            $safeProfileImage = basename($userRow['profile_image']);
            $profileImagePath = UPLOAD_PROFILES_PATH . $safeProfileImage;
            if (file_exists($profileImagePath)) {
                $navbarProfileImageUrl = UPLOAD_PROFILES_URL . rawurlencode($safeProfileImage);
            }
        }
    }

    $stmt = mysqli_prepare($conn, "SELECT id, name FROM stores WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userStore = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM carts WHERE user_id = ?");
    mysqli_stmt_bind_param($countStmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($countStmt);
    $cartCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
    mysqli_stmt_close($countStmt);

    $popupStmt = mysqli_prepare($conn, "
        SELECT c.id AS cart_id, c.quantity, p.name, p.price_per_day, pi.image
        FROM carts c
        INNER JOIN products p ON p.id = c.product_id
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    mysqli_stmt_bind_param($popupStmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($popupStmt);
    $cartPopupItems = mysqli_fetch_all(mysqli_stmt_get_result($popupStmt), MYSQLI_ASSOC);
    mysqli_stmt_close($popupStmt);
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
                <div class="cart-icon-wrapper">
                    <a class="catalog-nav-icon cart-icon" href="<?= route('cart'); ?>" aria-label="Keranjang rental">
                        <?php render_icon('shopping-cart'); ?>
                        <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount > 99 ? '99+' : $cartCount; ?></span><?php endif; ?>
                    </a>
                    <div class="cart-popup">
                        <?php if (empty($cartPopupItems)): ?>
                            <p class="cart-popup-empty">Keranjang masih kosong</p>
                        <?php else: ?>
                            <?php foreach ($cartPopupItems as $ci): ?>
                            <div class="cart-popup-item">
                                <div class="cart-popup-img"><?php render_product_image($ci['image'], $ci['name']); ?></div>
                                <div class="cart-popup-info">
                                    <p class="cart-popup-name"><?= htmlspecialchars($ci['name']); ?></p>
                                    <p class="cart-popup-qty"><?= (int) $ci['quantity']; ?>x Rp<?= number_format((float) $ci['price_per_day'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <hr>
                            <a class="btn btn-primary btn-small btn-full" href="<?= route('cart'); ?>">Lihat Keranjang</a>
                        <?php endif; ?>
                    </div>
                </div>
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
                <a class="catalog-nav-user" href="<?= route('profile'); ?>" aria-label="Profil <?= htmlspecialchars($username); ?>">
                    <span class="catalog-nav-avatar">
                        <?php if ($navbarProfileImageUrl): ?>
                            <img src="<?= htmlspecialchars($navbarProfileImageUrl); ?>" alt="Foto profil <?= htmlspecialchars($username); ?>">
                        <?php else: ?>
                            <?php render_icon('circle-user-round'); ?>
                        <?php endif; ?>
                    </span>
                    <span class="catalog-nav-username"><?= htmlspecialchars($username); ?></span>
                </a>
                <a class="catalog-nav-icon" href="<?= route('rental.returns'); ?>" aria-label="Pengembalian Saya" title="Pengembalian Saya">
                    <?php render_icon('rotate-ccw'); ?>
                </a>
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
