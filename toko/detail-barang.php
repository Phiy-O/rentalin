<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    set_flash('error', 'Barang tidak valid.');
    redirect_route('toko.products');
}

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$storeResult = mysqli_stmt_get_result($storeStmt);
$store = mysqli_fetch_assoc($storeResult);
mysqli_stmt_close($storeStmt);

if (!$store) {
    set_flash('error', 'Kamu belum memiliki toko. Silakan buat toko terlebih dahulu.');
    redirect_route('toko.create');
}

$storeId = (int) $store['id'];

$productStmt = mysqli_prepare($conn, "
    SELECT p.id, p.name, p.description, p.price_per_day, p.stock, p.condition_status,
           p.status, p.created_at, p.updated_at,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.store_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($productStmt, 'ii', $productId, $storeId);
mysqli_stmt_execute($productStmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($productStmt));
mysqli_stmt_close($productStmt);

if (!$product) {
    set_flash('error', 'Barang tidak ditemukan atau bukan milik toko kamu.');
    redirect_route('toko.products');
}

$imagesStmt = mysqli_prepare($conn, "SELECT image, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
mysqli_stmt_bind_param($imagesStmt, 'i', $productId);
mysqli_stmt_execute($imagesStmt);
$images = mysqli_fetch_all(mysqli_stmt_get_result($imagesStmt), MYSQLI_ASSOC);
mysqli_stmt_close($imagesStmt);

if (empty($images)) {
    $images[] = ['image' => null, 'is_primary' => 1];
}

$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'active_rentals' => 0,
    'completed_orders' => 0,
];

$statsStmt = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status IN ('approved', 'rented') THEN 1 ELSE 0 END) AS active_rentals,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders
    FROM rentals
    WHERE product_id = ? AND store_id = ?
");
mysqli_stmt_bind_param($statsStmt, 'ii', $productId, $storeId);
mysqli_stmt_execute($statsStmt);
$statsRow = mysqli_fetch_assoc(mysqli_stmt_get_result($statsStmt));
mysqli_stmt_close($statsStmt);

if ($statsRow) {
    $stats = array_map('intval', $statsRow);
}

$statusLabel = $product['status'] === 'available' ? 'Tersedia' : 'Tidak Aktif';
$statusClass = $product['status'] === 'available' ? 'status-available' : 'status-unavailable';
$mainImage = $images[0]['image'] ?? null;
$activeMenu = 'products';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content owner-product-detail-page">
            <?php show_flash(); ?>

            <header class="dashboard-header owner-product-detail-header">
                <div>
                    <span class="product-create-eyebrow">Detail Barang</span>
                    <h1><?= htmlspecialchars($product['name']); ?></h1>
                    <p>Lihat detail barang rental yang tampil di katalog Rentalin.</p>
                </div>
                <div class="owner-product-actions">
                    <a href="<?= route('toko.products'); ?>" class="btn btn-outline btn-small">Kembali</a>
                    <a href="<?= route('toko.products.edit', ['id' => $product['id']]); ?>" class="btn btn-primary btn-small">Edit Barang</a>
                </div>
            </header>

            <div class="owner-product-detail-grid">
                <section class="owner-product-gallery-card">
                    <div class="owner-product-main-image" id="ownerProductMainImage">
                        <?php render_product_image($mainImage, $product['name'], true); ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="owner-product-thumbs">
                            <?php foreach ($images as $index => $image): ?>
                                <button type="button" class="owner-product-thumb <?= $index === 0 ? 'active' : ''; ?>" data-image="<?= htmlspecialchars($image['image'] ?? ''); ?>">
                                    <?php render_product_image($image['image'], $product['name'] . ' ' . ($index + 1)); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="owner-product-info-card">
                    <div class="owner-product-title-row">
                        <div>
                            <p class="owner-product-category"><?= htmlspecialchars($product['category_name'] ?? 'Tanpa kategori'); ?></p>
                            <h2><?= htmlspecialchars($product['name']); ?></h2>
                        </div>
                        <span class="status-badge <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                    </div>

                    <div class="owner-product-price-row">
                        <span>Harga Sewa</span>
                        <strong>Rp<?= number_format((float) $product['price_per_day'], 0, ',', '.'); ?> <small>/hari</small></strong>
                    </div>

                    <div class="owner-product-stats-grid">
                        <div class="owner-product-stat">
                            <span><?= (int) $product['stock']; ?></span>
                            <p>Stok Unit</p>
                        </div>
                        <div class="owner-product-stat">
                            <span><?= $stats['total_orders']; ?></span>
                            <p>Total Pesanan</p>
                        </div>
                        <div class="owner-product-stat">
                            <span><?= $stats['active_rentals']; ?></span>
                            <p>Rental Aktif</p>
                        </div>
                        <div class="owner-product-stat">
                            <span><?= $stats['completed_orders']; ?></span>
                            <p>Selesai</p>
                        </div>
                    </div>

                    <div class="owner-product-detail-list">
                        <p><strong>Kategori</strong><span><?= htmlspecialchars($product['category_name'] ?? '-'); ?></span></p>
                        <p><strong>Kondisi</strong><span><?= htmlspecialchars($product['condition_status'] ?: '-'); ?></span></p>
                        <p><strong>Status</strong><span><?= $statusLabel; ?></span></p>
                        <p><strong>Dibuat</strong><span><?= date('d M Y, H:i', strtotime($product['created_at'])); ?></span></p>
                        <p><strong>Diperbarui</strong><span><?= date('d M Y, H:i', strtotime($product['updated_at'])); ?></span></p>
                    </div>
                </section>
            </div>

            <section class="owner-product-description-card">
                <div class="product-form-card-head">
                    <h2>Deskripsi Barang</h2>
                    <p>Informasi yang dilihat penyewa pada halaman detail produk.</p>
                </div>
                <p><?= nl2br(htmlspecialchars($product['description'] ?: 'Deskripsi barang belum tersedia.')); ?></p>
            </section>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mainImage = document.getElementById('ownerProductMainImage');
    var thumbs = document.querySelectorAll('.owner-product-thumb');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var img = mainImage ? mainImage.querySelector('img') : null;
            var file = this.getAttribute('data-image');
            if (img) {
                img.src = file ? '<?= BASE_URL; ?>/uploads/products/' + encodeURIComponent(file) : '<?= BASE_URL; ?>/assets/images/product-placeholder.svg';
            }
            thumbs.forEach(function (item) { item.classList.remove('active'); });
            this.classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
