<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$services = [
    [
        'title' => 'Rental Barang Harian',
        'description' => 'Sewa barang kebutuhan harian seperti kamera, proyektor, alat outdoor, dan perlengkapan acara.',
    ],
    [
        'title' => 'Buka Toko Rental',
        'description' => 'Pemilik barang dapat membuat toko rental dan mulai menawarkan barang kepada calon penyewa.',
    ],
    [
        'title' => 'Katalog Produk',
        'description' => 'Cari barang berdasarkan kategori, harga, dan kebutuhan sewa dengan tampilan katalog yang mudah dipahami.',
    ],
    [
        'title' => 'Manajemen Pesanan',
        'description' => 'Toko dapat melihat pengajuan sewa, menyetujui pesanan, dan memantau status rental barang.',
    ],
    [
        'title' => 'Pengembalian Barang',
        'description' => 'Penyewa dapat mengajukan pengembalian dan toko dapat memeriksa kondisi barang setelah masa rental selesai.',
    ],
    [
        'title' => 'Chat Penyewa & Toko',
        'description' => 'Penyewa dan pemilik toko dapat berkomunikasi untuk menanyakan detail barang sebelum transaksi rental.',
    ],
];
?>

<section class="services-hero"></section>

<main class="container services-page">
    <section class="services-heading">
        <p>OUR SERVICES</p>
        <h1>Layanan rental barang yang membantu penyewa dan pemilik toko.</h1>
    </section>

    <section class="services-grid">
        <?php foreach ($services as $service): ?>
            <article class="service-card">
                <div class="service-icon"></div>
                <h2><?= htmlspecialchars($service['title']); ?></h2>
                <p><?= htmlspecialchars($service['description']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
