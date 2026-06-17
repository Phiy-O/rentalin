<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="about-page-hero"></section>

<main class="about-page">
    <section class="container about-intro">
        <div class="about-intro-content">
            <h1>HEADING</h1>
            <p>
                Rentalin adalah platform rental barang yang membantu penyewa menemukan
                kebutuhan mereka dengan mudah, sekaligus membantu pemilik toko mengelola
                barang rental secara lebih rapi dan praktis.
            </p>
        </div>
        <div class="about-intro-image" aria-label="Ilustrasi tentang Rentalin"></div>
    </section>

    <section class="container about-feature">
        <div class="about-feature-image" aria-label="Ilustrasi layanan Rentalin"></div>
        <div class="about-feature-content">
            <p class="about-label">LOREM IPSUM</p>
            <h2>HEADING</h2>
            <p>
                Kami ingin proses sewa barang terasa sederhana: pengguna bisa mencari
                barang, melihat informasi toko, mengajukan rental, dan memantau status
                penyewaan dalam satu website.
            </p>
        </div>
    </section>

    <section class="container about-cta">
        <p class="about-label">LOREM IPSUM</p>
        <h2>Rentalin menghubungkan penyewa dengan pemilik barang secara mudah.</h2>
        <a class="btn btn-primary" href="<?= route('register'); ?>">Mulai Sekarang</a>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
