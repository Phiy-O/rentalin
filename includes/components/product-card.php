<?php
$productId = $product['id'] ?? 0;
$productName = $product['name'] ?? 'Produk Rental';
$productImage = $product['image'] ?? null;
$categoryName = $product['category_name'] ?? '';
$pricePerDay = $product['price_per_day'] ?? 0;
$storeName = $product['store_name'] ?? '';
$isPriorityImage = isset($index) && $index < 4;
$storeLocation = trim(($product['store_city'] ?? '') . (!empty($product['store_province']) ? ', ' . $product['store_province'] : ''));
$timesRented = (int) ($product['times_rented'] ?? 0);
// $avgRating = $product['avg_rating'] ?? 0;
// $fullStars = floor($avgRating);
// $hasHalf = ($avgRating - $fullStars) >= 0.3 && ($avgRating - $fullStars) < 0.8;
// $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);
?>

<article class="product-card">
    <a href="<?= route('product.detail', ['id' => $productId]); ?>" class="product-card-link">
        <div class="product-card-image">
            <div class="product-card-img">
                <?php render_product_image($productImage, $productName, $isPriorityImage); ?>
            </div>
            <span class="product-card-badge badge badge-available">Tersedia</span>
        </div>
        <div class="product-card-body">
            <p class="product-card-category"><?= htmlspecialchars($categoryName); ?></p>
            <h3 class="product-card-title"><?= htmlspecialchars($productName); ?></h3>
            <p class="product-card-price">Rp<?= number_format((float) $pricePerDay, 0, ',', '.'); ?><span class="product-card-price-unit"> /hari</span></p>
            <div class="product-card-meta">
                <?php if ($storeLocation): ?>
                    <span class="product-card-location"><?php render_icon('map-pin', 'icon-xs'); ?><?= htmlspecialchars($storeLocation); ?></span>
                <?php endif; ?>
            </div>
            <div class="product-card-footer">
                <span class="product-card-store"><?php render_icon('store', 'icon-xs'); ?><?= htmlspecialchars($storeName); ?></span>
                <?php if ($timesRented > 0): ?>
                    <span class="product-card-rented"><?= $timesRented; ?>x disewa</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>
