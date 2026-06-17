<?php
$productId = $product['id'] ?? 0;
$productName = $product['name'] ?? 'Produk Rental';
$storeName = $product['store_name'] ?? 'Rentalin Store';
$pricePerDay = $product['price_per_day'] ?? 0;
$productImage = $product['image'] ?? null;
$isPriorityImage = isset($index) && $index < 2;
?>

<article class="catalog-card">
    <div class="catalog-card-image">
        <?php render_product_image($productImage, $productName, $isPriorityImage); ?>
    </div>
    <div class="catalog-card-body">
        <h3><?= htmlspecialchars($productName); ?></h3>
        <p><?= htmlspecialchars($storeName); ?></p>
        <div class="catalog-card-footer">
            <strong>Rp<?= number_format((float) $pricePerDay, 0, ',', '.'); ?>/hari</strong>
            <a href="<?= route('product.detail', ['id' => $productId]); ?>">Detail</a>
        </div>
    </div>
</article>
