<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="about-page-hero">
    <div class="about-page-hero-content">
        <h1>About Us</h1>
        <p>Kenali Rentalin lebih dekat sebagai platform yang membantu proses sewa barang jadi lebih mudah, praktis, dan terpercaya.</p>
    </div>
</section>

<main class="about-page">
    <section class="container about-intro">
        <div class="about-intro-content">
            <p class="about-label">TENTANG RENTALIN</p>
            <h1>Cara baru untuk menyewa barang.</h1>
            <p class="about-paragraph">
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
            <p class="about-label">MISI KAMI</p>
            <h2>Membuat rental terasa sederhana dan terpercaya.</h2>
            <p class="about-paragraph">
                Kami ingin proses sewa barang terasa sederhana: pengguna bisa mencari
                barang, melihat informasi toko, mengajukan rental, dan memantau status
                penyewaan dalam satu website.
            </p>
        </div>
    </section>

    <section class="container about-cta">
        <p class="about-label">MULAI BERSAMA KAMI</p>
        <h2>Rentalin menghubungkan penyewa dengan pemilik barang secara mudah.</h2>
        <a class="btn btn-primary" href="<?= route('register'); ?>">Mulai Sekarang</a>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
