<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    redirect_route('catalog');
}

$productQuery = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price_per_day,
        p.stock,
        p.condition_status,
        p.status,
        s.name AS store_name,
        s.address AS store_address,
        c.name AS category_name
    FROM products p
    INNER JOIN stores s ON s.id = p.store_id
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $productQuery);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$productResult = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($productResult);

if (!$product) {
    redirect_route('catalog');
}

$imageQuery = 'SELECT image, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC';
$imageStmt = mysqli_prepare($conn, $imageQuery);
mysqli_stmt_bind_param($imageStmt, 'i', $productId);
mysqli_stmt_execute($imageStmt);
$imageResult = mysqli_stmt_get_result($imageStmt);
$productImages = [];

while ($image = mysqli_fetch_assoc($imageResult)) {
    $productImages[] = $image['image'];
}

if (empty($productImages)) {
    $productImages[] = null;
}

$mainImage = $productImages[0];
$price = (float) $product['price_per_day'];
$originalPrice = $price * 1.35;
?>

<main class="product-detail-page container">
    <section class="product-detail-layout">
        <div class="product-gallery">
            <div class="product-main-image">
                <?php render_product_image($mainImage, $product['name'], true); ?>
            </div>
            <div class="product-thumbnails" aria-label="Galeri produk">
                <?php foreach ($productImages as $image): ?>
                    <button type="button">
                        <?php render_product_image($image, $product['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <section class="product-info">
            <p class="product-store-name"><?= htmlspecialchars($product['store_name']); ?></p>
            <h1><?= htmlspecialchars($product['name']); ?></h1>
            <div class="product-rating">
                <span>★</span>
                <p>5.0 (1.000 Rating)</p>
            </div>
            <div class="product-price">
                <strong>Rp<?= number_format($price, 0, ',', '.'); ?></strong>
                <del>Rp<?= number_format($originalPrice, 0, ',', '.'); ?></del>
            </div>

            <div class="product-attributes">
                <h2>Pilih jenis:</h2>
                <div class="product-chip-list">
                    <span><?= htmlspecialchars($product['category_name']); ?></span>
                    <span><?= htmlspecialchars($product['condition_status'] ?? 'Baik'); ?></span>
                    <span><?= htmlspecialchars($product['status']); ?></span>
                    <span>Stok <?= (int) $product['stock']; ?></span>
                </div>
            </div>

            <div class="product-description">
                <h2>Detail Produk</h2>
                <p><?= nl2br(htmlspecialchars($product['description'] ?: 'Deskripsi produk belum tersedia.')); ?></p>
                <dl>
                    <div>
                        <dt>Kategori</dt>
                        <dd><?= htmlspecialchars($product['category_name']); ?></dd>
                    </div>
                    <div>
                        <dt>Kondisi</dt>
                        <dd><?= htmlspecialchars($product['condition_status'] ?? 'Baik'); ?></dd>
                    </div>
                    <div>
                        <dt>Alamat Toko</dt>
                        <dd><?= htmlspecialchars($product['store_address']); ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <aside class="product-rental-box">
            <h2>Atur Jumlah Catatan</h2>
            <div class="product-qty-row">
                <div class="product-qty-control">
                    <button type="button">−</button>
                    <span>1</span>
                    <button type="button">+</button>
                </div>
                <p>Stok: <?= (int) $product['stock']; ?></p>
            </div>
            <div class="product-subtotal">
                <span>Subtotal:</span>
                <strong>Rp<?= number_format($price, 0, ',', '.'); ?></strong>
            </div>
            <a class="product-primary-action" href="<?= route('rental.checkout', ['product_id' => $product['id']]); ?>">Ajukan Rental</a>
            <a class="product-secondary-action" href="<?= route('contact'); ?>">Chat Toko</a>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
