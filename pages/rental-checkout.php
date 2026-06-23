<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';

$productId = (int) ($_GET['product_id'] ?? 0);

if ($productId <= 0) {
    redirect_route('catalog');
}

$query = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price_per_day,
        p.stock,
        p.condition_status,
        s.name AS store_name,
        s.address AS store_address,
        s.phone AS store_phone,
        c.name AS category_name,
        pi.image
    FROM products p
    INNER JOIN stores s ON s.id = p.store_id
    INNER JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE p.id = ? AND p.status = 'available'
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    redirect_route('catalog');
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$price = (float) $product['price_per_day'];
?>

<header class="checkout-topbar">
    <div class="container checkout-topbar-inner">
        <a class="checkout-logo" href="<?= route('catalog'); ?>">
            <img src="<?= BASE_URL; ?>/assets/images/rentalin-logo.png" alt="rentalin-logo">
        </a>
        <a class="checkout-help" href="<?= route('contact'); ?>">Butuh Bantuan?</a>
    </div>
</header>

<main class="checkout-page container">
    <h1>Rental</h1>
    <?php show_flash(); ?>

    <form class="checkout-layout" action="<?= route('rental.store'); ?>" method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id']; ?>">

        <div class="checkout-main">
            <section class="checkout-address-card">
                <h2>Alamat Pengambilan</h2>
                <div class="checkout-store-line">
                    <span aria-hidden="true">●</span>
                    <strong><?= htmlspecialchars($product['store_name']); ?></strong>
                </div>
                <p><?= htmlspecialchars($product['store_address']); ?></p>
                <p><?= htmlspecialchars($product['store_phone'] ?? '-'); ?></p>
                <a href="<?= route('contact'); ?>">Lihat di maps</a>
            </section>

            <section class="checkout-product-card">
                <h2><?= htmlspecialchars($product['store_name']); ?></h2>
                <div class="checkout-product-row">
                    <div class="checkout-product-image">
                        <?php render_product_image($product['image'], $product['name'], true); ?>
                    </div>
                    <div class="checkout-product-info">
                        <div class="checkout-product-title-row">
                            <h3><?= htmlspecialchars($product['name']); ?></h3>
                            <strong>Rp<?= number_format($price, 0, ',', '.'); ?>/hari</strong>
                        </div>
                        <p><?= htmlspecialchars($product['category_name']); ?> / <?= htmlspecialchars($product['condition_status'] ?? 'Baik'); ?></p>

                        <div class="checkout-date-grid">
                            <label for="start_date">Tanggal mulai sewa</label>
                            <input type="date" id="start_date" name="start_date" value="<?= $today; ?>" min="<?= $today; ?>" required>

                            <label for="end_date">Tanggal pengembalian</label>
                            <input type="date" id="end_date" name="end_date" value="<?= $tomorrow; ?>" min="<?= $today; ?>" required>
                        </div>

                        <div class="checkout-duration-row">
                            <span>Estimasi durasi</span>
                            <strong>2 hari</strong>
                        </div>

                        <textarea name="notes" placeholder="Catatan untuk toko, contoh: jam pengambilan atau kebutuhan tambahan"></textarea>
                    </div>
                </div>
            </section>
        </div>

        <aside class="checkout-summary">
            <h2>Metode Pembayaran</h2>
            <label class="checkout-payment-method">
                <span>Bayar di Tempat</span>
                <input type="radio" name="payment_method" value="cod" checked>
            </label>

            <div class="checkout-summary-list">
                <h3>Cek ringkasan transaksi</h3>
                <div>
                    <span>Total Sewa Akhir</span>
                    <strong>Rp<?= number_format($price, 0, ',', '.'); ?></strong>
                </div>
                <div>
                    <span>Subtotal Sewa</span>
                    <strong>Rp<?= number_format($price, 0, ',', '.'); ?></strong>
                </div>
                <div>
                    <span>Deposit</span>
                    <strong>Rp0</strong>
                </div>
                <div>
                    <span>Biaya Layanan</span>
                    <strong>Rp0</strong>
                </div>
            </div>

            <div class="checkout-total-row">
                <span>Total Tagihan</span>
                <strong>Rp<?= number_format($price, 0, ',', '.'); ?></strong>
            </div>

            <button class="checkout-submit" type="submit">Ajukan Rental</button>

            <label class="checkout-terms">
                <input type="checkbox" name="agree_terms" required>
                <span>Dengan melanjutkan pembayaran, kamu menyetujui S&K rental.</span>
            </label>
        </aside>
    </form>
</main>

<footer class="checkout-footer">
    <div class="container">Rentalin copyright <?= date('Y'); ?></div>
</footer>
</body>
</html>
