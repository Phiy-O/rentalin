<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/image-helper.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$featuredQuery = "
    SELECT p.id, p.name, p.price_per_day, p.store_id,
           s.name AS store_name,
           pi.image
    FROM products p
    INNER JOIN stores s ON s.id = p.store_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE p.status = 'available'
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 8
";
$featuredResult = mysqli_query($conn, $featuredQuery);
$featuredProducts = [];
while ($row = mysqli_fetch_assoc($featuredResult)) {
    $featuredProducts[] = $row;
}
?>

<main class="container">
    <?php show_flash(); ?>
</main>

<section class="hero landing-hero" id="home">
    <div class="hero-glow hero-glow-1"></div>
    <div class="hero-glow hero-glow-2"></div>
    <div class="hero-glow hero-glow-3"></div>
    <div class="container hero-grid">
        <div class="hero-content">
            <h1>Sewa Barang <span class="hero-highlight">Tanpa Ribet</span></h1>
            <p class="hero-desc">
                Rentalin membantu kamu menemukan kamera, alat outdoor, elektronik,
                sampai kebutuhan acara dari toko rental terdekat.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-hero" href="<?= route('catalog'); ?>">
                    Mulai Sewa
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
                <a class="btn btn-hero-outline" href="<?= route('home'); ?>#about">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    Tentang Kami
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-visual-glow"></div>
            <div class="hero-visual-wrap">
                <img src="assets/images/hero-image.png" alt="Barang rental">
            </div>
        </div>
    </div>
</section>

<main>
    <section class="container landing-section category-section" id="categories">
        <div class="section-title-row">
            <h2>Browse by Category</h2>
            <a href="<?= route('catalog'); ?>">Lihat semua</a>
        </div>
        <div class="category-grid">
            <a class="category-item" href="<?= route('catalog', ['category' => 'kamera']); ?>">
                <span class="category-icon">
                    <img src="assets/images/camera.png" alt="camera">
                </span>
                <strong>Kamera</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'elektronik']); ?>">
                <span class="category-icon">
                    <img src="assets/images/tv.png" alt="tv">
                </span>
                <strong>Elektronik</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'outdoor']); ?>">
                <span class="category-icon">
                    <img src="assets/images/kursi-lipat.png" alt="kursi-lipat">
                </span>
                <strong>Outdoor</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'kendaraan']); ?>">
                <span class="category-icon">
                    <img src="assets/images/car.png" alt="car">
                </span>
                <strong>Kendaraan</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'rumah']); ?>">
                <span class="category-icon">
                    <img src="assets/images/house.png" alt="house">
                </span>
                <strong>Rumah</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'event']); ?>">
                <span class="category-icon">
                    <img src="assets/images/toa.png" alt="toa">
                </span>
                <strong>Event</strong>
            </a>
        </div>
    </section>

    <section class="container landing-section" id="products">
        <div class="section-title-row">
            <h2>Feature Products</h2>
            <a class="btn btn-outline btn-small" href="<?= route('catalog'); ?>">Explore</a>
        </div>
        <div class="featured-grid">
            <?php if (empty($featuredProducts)): ?>
                <p class="featured-empty">Belum ada produk tersedia.</p>
            <?php else: ?>
                <?php foreach ($featuredProducts as $index => $product): ?>
                    <a href="<?= route('product.detail', ['id' => $product['id']]); ?>" class="featured-card">
                        <div class="featured-image">
                            <?php render_product_image($product['image'] ?? null, $product['name'] ?? 'Produk', $index < 2); ?>
                        </div>
                        <div class="featured-info">
                            <h3><?= htmlspecialchars($product['name']); ?></h3>
                            <p><?= htmlspecialchars($product['store_name']); ?></p>
                            <strong>Rp<?= number_format((float) $product['price_per_day'], 0, ',', '.'); ?>/hari</strong>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="container discount-banner">
        <div class="cta-banner">
            <a href="<?= route('catalog'); ?>">
                <img src="assets/images/banner1.png" alt="banner1">
            </a>
        </div>
    </section>

    <section class="about-section" id="about">
        <div class="container about-grid">
            <div class="about-content">
                <p class="eyebrow">Kenapa Rentalin?</p>
                <h2>About Us</h2>
                <p>
                    Rentalin dibuat untuk memudahkan penyewa menemukan barang dan membantu
                    pemilik toko mengelola rental secara lebih praktis.
                </p>
                <a class="btn btn-white" href="<?= route('register'); ?>">Join us</a>
            </div>
            <div class="about-gallery">
                <div class="about-photo tall">
                    <div class="about-photo one">
                        <img src="assets/images/teamwork-unsplash.jpg" alt="about-img">
                    </div>
                </div>
                <div class="about-photo right">
                    <div class="about-photo two">
                        <img src="assets/images/microters-seo-agency-unsplash.jpg" alt="about-img">
                    </div>
                    <div class="about-photo three">
                        <img src="assets/images/camera-unsplash.jpg" alt="about-img">
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>