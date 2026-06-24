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
        p.id, p.name, p.description, p.price_per_day, p.stock,
        p.condition_status, p.status, p.category_id,
        s.id AS store_id, s.name AS store_name, s.address AS store_address,
        s.city AS store_city, s.province AS store_province,
        s.google_maps_link, s.phone, s.open_time, s.close_time,
        s.rental_terms, s.deposit_policy, s.fine_policy,
        c.name AS category_name, c.slug AS category_slug
    FROM products p
    INNER JOIN stores s ON s.id = p.store_id
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $productQuery);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    redirect_route('catalog');
}

$imageStmt = mysqli_prepare($conn, "SELECT image, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
mysqli_stmt_bind_param($imageStmt, 'i', $productId);
mysqli_stmt_execute($imageStmt);
$productImages = mysqli_fetch_all(mysqli_stmt_get_result($imageStmt), MYSQLI_ASSOC);

if (empty($productImages)) {
    $productImages[] = ['image' => null, 'is_primary' => 1];
}

$mainImage = $productImages[0]['image'];
$price = (float) $product['price_per_day'];

$operationalHours = '';
if ($product['open_time'] && $product['close_time']) {
    $open = date('H.i', strtotime($product['open_time']));
    $close = date('H.i', strtotime($product['close_time']));
    $operationalHours = $open . ' - ' . $close;
}

$storeLocation = trim(($product['store_city'] ?? '') . (!empty($product['store_province']) ? ', ' . $product['store_province'] : ''));
$isAvailable = $product['status'] === 'available' && (int) $product['stock'] > 0;

$statusClass = 'pd-st-available';
$statusLabel = 'Tersedia';
if ($product['status'] === 'available' && (int) $product['stock'] > 0) {
    $statusClass = 'pd-st-available';
    $statusLabel = 'Tersedia';
} elseif ($product['status'] === 'available' && (int) $product['stock'] <= 0) {
    $statusClass = 'pd-st-empty';
    $statusLabel = 'Stok Habis';
} elseif ($product['status'] === 'unavailable') {
    $statusClass = 'pd-st-inactive';
    $statusLabel = 'Tidak Aktif';
}
?>
<main class="pd-page">
    <div class="container">

        <nav class="pd-breadcrumb">
            <a href="<?= route('catalog'); ?>">Home</a>
            <span class="pd-breadcrumb-sep">/</span>
            <a href="<?= route('catalog', ['category' => $product['category_slug']]); ?>"><?= htmlspecialchars($product['category_name']); ?></a>
            <span class="pd-breadcrumb-sep">/</span>
            <span class="pd-breadcrumb-current"><?= htmlspecialchars($product['name']); ?></span>
        </nav>

        <div class="pd-layout">

            <div class="pd-gallery">
                <div class="pd-gallery-main" id="pdMainImage">
                    <?php render_product_image($mainImage, $product['name'], true); ?>
                </div>
                <?php if (count($productImages) > 1): ?>
                <div class="pd-gallery-thumbs" id="pdThumbs">
                    <?php foreach ($productImages as $i => $img): ?>
                    <button type="button" class="pd-thumb <?= $i === 0 ? 'active' : ''; ?>" data-image="<?= htmlspecialchars($img['image'] ?? ''); ?>">
                        <?php render_product_image($img['image'], $product['name'] . ' ' . ($i + 1)); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="pd-info">
                <a href="<?= route('catalog', ['tab' => 'toko', 'search' => $product['store_name']]); ?>" class="pd-store-link">
                    <?php render_icon('store', 'icon-xs'); ?>
                    <?= htmlspecialchars($product['store_name']); ?>
                </a>

                <h1 class="pd-title"><?= htmlspecialchars($product['name']); ?></h1>

                <div class="pd-rating-row">
                    <div class="pd-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php render_icon('star', 'icon-xs ' . ($i < 4 ? 'icon-star' : 'icon-star-empty')); ?>
                        <?php endfor; ?>
                    </div>
                    <span class="pd-rating-text">4.0 (12 rating)</span>
                    <span class="pd-rating-divider">|</span>
                    <span class="pd-rating-text">Terjual 8</span>
                </div>

                <div class="pd-price-section">
                    <div class="pd-price-main">
                        <span class="pd-price-amount">Rp<?= number_format($price, 0, ',', '.'); ?></span>
                        <span class="pd-price-unit">/hari</span>
                    </div>
                    <?php if ($product['deposit_policy']): ?>
                    <div class="pd-deposit-row">
                        <span class="pd-deposit-label">Deposit</span>
                        <span class="pd-deposit-value"><?= htmlspecialchars($product['deposit_policy']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['fine_policy']): ?>
                    <div class="pd-fine-row">
                        <span class="pd-deposit-label">Denda keterlambatan</span>
                        <span class="pd-deposit-value"><?= htmlspecialchars($product['fine_policy']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pd-desc">
                    <h2 class="pd-desc-heading">Deskripsi</h2>
                    <p><?= nl2br(htmlspecialchars($product['description'] ?: 'Deskripsi produk belum tersedia.')); ?></p>
                </div>

                <div class="pd-tabs">
                    <div class="pd-tab-nav">
                        <button type="button" class="pd-tab-btn active" data-tab="detail">Detail Produk</button>
                        <button type="button" class="pd-tab-btn" data-tab="guide">Panduan Rental</button>
                    </div>
                    <div class="pd-tab-panels">
                        <div class="pd-tab-panel active" id="tab-detail">
                            <div class="pd-detail-info">
                                <p><strong>Kategori</strong> <span><?= htmlspecialchars($product['category_name']); ?></span></p>
                                <p><strong>Kondisi Barang</strong> <span><?= htmlspecialchars($product['condition_status'] ?? '-'); ?></span></p>
                                <p><strong>Stok Tersedia</strong> <span><?= (int) $product['stock']; ?> unit</span></p>
                                <p><strong>Lokasi Toko</strong> <span><?= htmlspecialchars($product['store_address'] ?: $storeLocation ?: '-'); ?></span></p>
                            </div>
                            <div class="pd-detail-desc">
                                <h3>Deskripsi Lengkap</h3>
                                <p><?= nl2br(htmlspecialchars($product['description'] ?: 'Deskripsi produk belum tersedia.')); ?></p>
                            </div>
                        </div>
                        <div class="pd-tab-panel" id="tab-guide">
                            <div class="pd-guide-grid">
                                <?php if ($operationalHours): ?>
                                <div class="pd-guide-item">
                                    <span class="pd-guide-icon"><?php render_icon('calendar', 'icon-sm'); ?></span>
                                    <div>
                                        <strong>Jam Operasional</strong>
                                        <p><?= htmlspecialchars($operationalHours); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($storeLocation): ?>
                                <div class="pd-guide-item">
                                    <span class="pd-guide-icon"><?php render_icon('map-pin', 'icon-sm'); ?></span>
                                    <div>
                                        <strong>Lokasi Pengambilan</strong>
                                        <p><?= htmlspecialchars($storeLocation); ?></p>
                                        <?php if ($product['store_address']): ?>
                                        <p class="pd-guide-sub"><?= htmlspecialchars($product['store_address']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($product['google_maps_link']): ?>
                                <div class="pd-guide-item">
                                    <span class="pd-guide-icon"><?php render_icon('map-pin', 'icon-sm'); ?></span>
                                    <div>
                                        <strong>Lokasi Maps</strong>
                                        <a href="<?= htmlspecialchars($product['google_maps_link']); ?>" target="_blank" rel="noopener" class="pd-guide-link">Lihat di Google Maps</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($product['phone']): ?>
                                <div class="pd-guide-item">
                                    <span class="pd-guide-icon"><?php render_icon('message-circle-more', 'icon-sm'); ?></span>
                                    <div>
                                        <strong>Kontak Toko</strong>
                                        <p><?= htmlspecialchars($product['phone']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['rental_terms']): ?>
                            <div class="pd-guide-section">
                                <h3>Syarat Rental</h3>
                                <p><?= nl2br(htmlspecialchars($product['rental_terms'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($product['deposit_policy']): ?>
                            <div class="pd-guide-section">
                                <h3>Kebijakan Deposit</h3>
                                <p><?= nl2br(htmlspecialchars($product['deposit_policy'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($product['fine_policy']): ?>
                            <div class="pd-guide-section">
                                <h3>Kebijakan Denda</h3>
                                <p><?= nl2br(htmlspecialchars($product['fine_policy'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="pd-sidebar">
                <div class="pd-sidebar-card">
                    <div class="pd-sidebar-preview">
                        <div class="pd-sidebar-img">
                            <?php render_product_image($mainImage, $product['name']); ?>
                        </div>
                        <div class="pd-sidebar-meta">
                            <p class="pd-sidebar-name"><?= htmlspecialchars($product['name']); ?></p>
                            <p class="pd-sidebar-store"><?= htmlspecialchars($product['store_name']); ?></p>
                        </div>
                    </div>

                    <div class="pd-sidebar-price">
                        <span class="pd-sidebar-price-amount">Rp<?= number_format($price, 0, ',', '.'); ?></span>
                        <span class="pd-sidebar-price-unit">/hari</span>
                    </div>

                    <div class="pd-qty">
                        <label class="pd-qty-label">Jumlah</label>
                        <div class="pd-qty-control">
                            <button type="button" class="pd-qty-btn" id="pdQtyMinus" <?= !$isAvailable ? 'disabled' : ''; ?>>−</button>
                            <input type="number" class="pd-qty-input" id="pdQtyInput" value="1" min="1" max="<?= (int) $product['stock']; ?>" <?= !$isAvailable ? 'disabled' : ''; ?>>
                            <button type="button" class="pd-qty-btn" id="pdQtyPlus" <?= !$isAvailable ? 'disabled' : ''; ?>>+</button>
                        </div>
                        <span class="pd-qty-stock">Stok: <?= (int) $product['stock']; ?></span>
                    </div>

                    <div class="pd-subtotal">
                        <span class="pd-subtotal-label">Subtotal</span>
                        <span class="pd-subtotal-amount" id="pdSubtotal">Rp<?= number_format($price, 0, ',', '.'); ?></span>
                    </div>

                    <?php if ($isAvailable): ?>
                        <form action="<?= route('cart.add'); ?>" method="POST" class="pd-sidebar-actions">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                            <input type="hidden" name="quantity" id="pdQtyHidden" value="1">
                            <button type="submit" class="pd-btn pd-btn-cart">Masukkan Keranjang</button>
                        </form>
                        <a href="<?= route('rental.checkout', ['product_id' => $product['id']]); ?>" class="pd-btn pd-btn-rent">Ajukan Rental</a>
                    <?php else: ?>
                        <div class="pd-unavailable">
                            <p>Barang tidak tersedia</p>
                        </div>
                        <button class="pd-btn pd-btn-cart" disabled>Masukkan Keranjang</button>
                        <button class="pd-btn pd-btn-rent" disabled>Ajukan Rental</button>
                    <?php endif; ?>

                    <div class="pd-sidebar-extra">
                        <a href="#" class="pd-extra-link">
                            <?php render_icon('message-circle-more', 'icon-xs'); ?>
                            Chat Toko
                        </a>
                        <a href="<?= route('catalog', ['tab' => 'toko', 'search' => $product['store_name']]); ?>" class="pd-extra-link">
                            <?php render_icon('store', 'icon-xs'); ?>
                            Kunjungi Toko
                        </a>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var thumbs = document.querySelectorAll('.pd-thumb');
    var mainImg = document.getElementById('pdMainImage');
    var qtyInput = document.getElementById('pdQtyInput');
    var qtyMinus = document.getElementById('pdQtyMinus');
    var qtyPlus = document.getElementById('pdQtyPlus');
    var subtotalEl = document.getElementById('pdSubtotal');
    var qtyHidden = document.getElementById('pdQtyHidden');
    var pricePerDay = <?= json_encode($price); ?>;
    var maxStock = <?= (int) $product['stock']; ?>;

    if (thumbs.length) {
        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var imgSrc = this.getAttribute('data-image');
                var imgTag = mainImg.querySelector('img');
                if (imgTag && imgSrc) {
                    imgTag.src = '<?= BASE_URL; ?>/uploads/products/' + encodeURIComponent(imgSrc);
                } else if (imgTag) {
                    imgTag.src = '<?= BASE_URL; ?>/assets/images/product-placeholder.svg';
                }
                thumbs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    }

    function updateSubtotal() {
        var qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;
        qtyInput.value = qty;
        if (qtyHidden) qtyHidden.value = qty;
        var total = qty * pricePerDay;
        subtotalEl.textContent = 'Rp' + total.toLocaleString('id-ID');
    }

    if (qtyMinus) {
        qtyMinus.addEventListener('click', function () {
            var val = parseInt(qtyInput.value) || 1;
            if (val > 1) qtyInput.value = val - 1;
            updateSubtotal();
        });
    }

    if (qtyPlus) {
        qtyPlus.addEventListener('click', function () {
            var val = parseInt(qtyInput.value) || 1;
            if (val < maxStock) qtyInput.value = val + 1;
            updateSubtotal();
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', updateSubtotal);
        qtyInput.addEventListener('input', function () {
            var val = parseInt(this.value) || 1;
            if (val < 1) this.value = 1;
            if (val > maxStock) this.value = maxStock;
            updateSubtotal();
        });
    }

    var tabBtns = document.querySelectorAll('.pd-tab-btn');
    var tabPanels = document.querySelectorAll('.pd-tab-panel');

    if (tabBtns.length) {
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = this.getAttribute('data-tab');
                tabBtns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                tabPanels.forEach(function (p) { p.classList.remove('active'); });
                var panel = document.getElementById('tab-' + target);
                if (panel) panel.classList.add('active');
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
