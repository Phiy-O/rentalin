<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <?php show_flash(); ?>
</main>

<section class="hero landing-hero" id="home">
    <div class="container hero-grid">
        <div class="hero-content">
            <p class="eyebrow">Rental barang jadi lebih mudah</p>
            <h1>Sewa Barang Tanpa Ribet</h1>
            <p>
                Rentalin membantu kamu menemukan kamera, alat outdoor, elektronik,
                sampai kebutuhan acara dari toko rental terdekat.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= route('catalog'); ?>">Mulai Sewa</a>
                <a class="btn btn-white" href="<?= route('home'); ?>#about">Tentang Kami</a>
            </div>
        </div>
        <div class="hero-visual" aria-label="Ilustrasi barang rental">
            <div class="hero-visual-card">
                <span>Kamera</span>
                <strong>Rp150k</strong>
            </div>
            <div class="hero-visual-card small">
                <span>Tenda</span>
                <strong>Ready</strong>
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
                <span class="category-icon">KM</span>
                <strong>Kamera</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'elektronik']); ?>">
                <span class="category-icon">EL</span>
                <strong>Elektronik</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'outdoor']); ?>">
                <span class="category-icon">OD</span>
                <strong>Outdoor</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'kendaraan']); ?>">
                <span class="category-icon">KD</span>
                <strong>Kendaraan</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'rumah']); ?>">
                <span class="category-icon">RM</span>
                <strong>Rumah</strong>
            </a>
            <a class="category-item" href="<?= route('catalog', ['category' => 'event']); ?>">
                <span class="category-icon">EV</span>
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
            <?php
            $featuredProducts = [
                ['name' => 'Sony Mirrorless', 'store' => 'Lensa Rental', 'price' => 'Rp150k/hari'],
                ['name' => 'Tenda Camping', 'store' => 'Outdoor Kita', 'price' => 'Rp75k/hari'],
                ['name' => 'Proyektor HD', 'store' => 'Event Tools', 'price' => 'Rp120k/hari'],
                ['name' => 'Tripod Pro', 'store' => 'Kamera Hub', 'price' => 'Rp35k/hari'],
                ['name' => 'Speaker Aktif', 'store' => 'Sound Rent', 'price' => 'Rp90k/hari'],
                ['name' => 'Drone Mini', 'store' => 'Aero Rent', 'price' => 'Rp200k/hari'],
                ['name' => 'Sepeda Lipat', 'store' => 'Cycle Rent', 'price' => 'Rp50k/hari'],
                ['name' => 'Lighting Set', 'store' => 'Studio Pack', 'price' => 'Rp110k/hari'],
            ];
            ?>

            <?php foreach ($featuredProducts as $product): ?>
                <article class="featured-card">
                    <button class="favorite-button" type="button" aria-label="Tambah favorit">☆</button>
                    <div class="featured-image"></div>
                    <div class="featured-info">
                        <h3><?= htmlspecialchars($product['name']); ?></h3>
                        <p><?= htmlspecialchars($product['store']); ?></p>
                        <strong><?= htmlspecialchars($product['price']); ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container discount-banner">
        <div>
            <p class="eyebrow">Promo rental pertama</p>
            <h2>DISCOUNT</h2>
            <p>Daftar sekarang dan temukan promo dari toko rental pilihan di Rentalin.</p>
            <a class="btn btn-primary" href="<?= route('register'); ?>">Ambil Promo</a>
        </div>
        <div class="discount-visual"></div>
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
                <div class="about-photo tall"></div>
                <div>
                    <div class="about-photo"></div>
                    <div class="about-stats">
                        <strong>100+</strong>
                        <strong>500+</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
