<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$tab = $_GET['tab'] ?? 'produk';
$selectedCategory = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$priceMin = trim($_GET['price_min'] ?? '');
$priceMax = trim($_GET['price_max'] ?? '');
$selectedLocation = $_GET['location'] ?? '';
$selectedCondition = $_GET['condition'] ?? '';
$selectedRating = $_GET['rating'] ?? '';
$selectedDuration = $_GET['duration'] ?? '';
$selectedStatus = $_GET['status'] ?? '';

$categories = [];
$categoryResult = mysqli_query($conn, "SELECT id, name, slug FROM categories ORDER BY name ASC");
while ($cat = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $cat;
}

$locations = [];
$locResult = mysqli_query($conn, "SELECT DISTINCT city FROM stores WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
while ($loc = mysqli_fetch_assoc($locResult)) {
    $locations[] = $loc['city'];
}

$conditionOpts = [];
$condResult = mysqli_query($conn, "SELECT DISTINCT condition_status FROM products WHERE condition_status IS NOT NULL AND condition_status != '' ORDER BY condition_status ASC");
while ($cond = mysqli_fetch_assoc($condResult)) {
    $conditionOpts[] = $cond['condition_status'];
}

$products = [];
$totalCount = 0;
$stores = [];

if ($tab === 'toko') {
    $storeWhere = ["s.status = 'active'"];
    $storeParams = [];

    if ($search !== '') {
        $safeSearch = mysqli_real_escape_string($conn, $search);
        $storeWhere[] = "(s.name LIKE '%$safeSearch%' OR s.description LIKE '%$safeSearch%' OR s.city LIKE '%$safeSearch%')";
    }
    if ($selectedLocation !== '') {
        $safeLoc = mysqli_real_escape_string($conn, $selectedLocation);
        $storeWhere[] = "s.city = '$safeLoc'";
    }

    $sw = implode(' AND ', $storeWhere);
    $storeSql = "SELECT s.id, s.name, s.slug, s.description, s.city, s.province, s.logo, s.phone,
                        (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id AND p.status = 'available') AS product_count
                 FROM stores s WHERE $sw ORDER BY s.name ASC LIMIT 40";
    $storeResult = mysqli_query($conn, $storeSql);
    while ($row = mysqli_fetch_assoc($storeResult)) {
        $stores[] = $row;
    }

    $countSql = "SELECT COUNT(*) AS total FROM stores s WHERE $sw";
    $countResult = mysqli_query($conn, $countSql);
    $totalCount = mysqli_fetch_assoc($countResult)['total'];
} else {
    $where = ["p.status = 'available'"];

    if ($selectedCategory !== '' && $selectedCategory !== 'semua') {
        $safeCat = mysqli_real_escape_string($conn, $selectedCategory);
        $where[] = "c.slug = '$safeCat'";
    }

    if ($search !== '') {
        $safeSearch = mysqli_real_escape_string($conn, $search);
        $where[] = "(p.name LIKE '%$safeSearch%' OR p.description LIKE '%$safeSearch%' OR s.name LIKE '%$safeSearch%')";
    }

    if ($priceMin !== '' && is_numeric($priceMin)) {
        $where[] = 'p.price_per_day >= ' . (float) $priceMin;
    }

    if ($priceMax !== '' && is_numeric($priceMax)) {
        $where[] = 'p.price_per_day <= ' . (float) $priceMax;
    }

    if ($selectedLocation !== '') {
        $safeLoc = mysqli_real_escape_string($conn, $selectedLocation);
        $where[] = "s.city = '$safeLoc'";
    }

    if ($selectedCondition !== '') {
        $safeCond = mysqli_real_escape_string($conn, $selectedCondition);
        $where[] = "p.condition_status = '$safeCond'";
    }

    $havingClause = '';
    if ($selectedRating === '4') {
        $havingClause = 'HAVING avg_rating >= 4';
    } elseif ($selectedRating === '3') {
        $havingClause = 'HAVING avg_rating >= 3';
    }

    $availabilityJoin = '';
    if ($selectedStatus === 'rented') {
        $where[] = "EXISTS (SELECT 1 FROM rentals r WHERE r.product_id = p.id AND r.status IN ('rented', 'approved'))";
    }

    switch ($sort) {
        case 'price_low': $orderBy = 'p.price_per_day ASC, p.id DESC'; break;
        case 'price_high': $orderBy = 'p.price_per_day DESC, p.id DESC'; break;
        case 'rating': $orderBy = 'avg_rating DESC, p.id DESC'; break;
        case 'newest': $orderBy = 'p.created_at DESC, p.id DESC'; break;
        default: $orderBy = 'p.created_at DESC, p.id DESC'; break;
    }

    $w = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) AS total FROM products p
                 INNER JOIN stores s ON s.id = p.store_id
                 INNER JOIN categories c ON c.id = p.category_id
                 WHERE $w";
    $countResult = mysqli_query($conn, $countSql);
    $totalCount = mysqli_fetch_assoc($countResult)['total'];

    $productSql = "SELECT p.id, p.name, p.price_per_day, p.stock, p.condition_status, p.status, p.description,
                          s.name AS store_name, s.city AS store_city, s.province AS store_province,
                          c.name AS category_name, c.slug AS category_slug,
                          pi.image,
                          ROUND(4.0 + (RAND(p.id) * 1.0), 1) AS avg_rating,
                          (SELECT COUNT(*) FROM rentals r WHERE r.product_id = p.id AND r.status IN ('completed')) AS times_rented
                   FROM products p
                   INNER JOIN stores s ON s.id = p.store_id
                   INNER JOIN categories c ON c.id = p.category_id
                   LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                   WHERE $w
                   $havingClause
                   ORDER BY $orderBy
                   LIMIT 40";
    $productResult = mysqli_query($conn, $productSql);
    while ($row = mysqli_fetch_assoc($productResult)) {
        $products[] = $row;
    }
}

$isSearchMode = $search !== '';
$resultStart = min($totalCount, 1);
$resultEnd = min($totalCount, 40);
?>
<?php if ($isSearchMode): ?>
<main class="catalog-market">
    <div class="catalog-market-container">
        <aside class="filter-sidebar" id="filterSidebar">
            <div class="filter-sidebar-header">
                <h3>Filter</h3>
                <button class="filter-close-btn" id="filterClose" aria-label="Tutup filter"><?php render_icon('x'); ?></button>
            </div>

            <form class="filter-form" method="GET" action="<?= route('catalog'); ?>" id="filterForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab); ?>">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search); ?>">
                <?php endif; ?>

                <div class="filter-section">
                    <h4 class="filter-section-title">Kategori</h4>
                    <div class="filter-options">
                        <a href="<?= route('catalog', array_filter(['tab' => $tab, 'search' => $search ?: null, 'sort' => $sort !== 'relevance' ? $sort : null])); ?>" class="filter-chip <?= $selectedCategory === '' || $selectedCategory === 'semua' ? 'active' : ''; ?>">Semua</a>
                        <?php foreach ($categories as $cat): ?>
                            <?php
                            $catParams = ['tab' => $tab, 'category' => $cat['slug'], 'search' => $search ?: null, 'sort' => $sort !== 'relevance' ? $sort : null];
                            if ($selectedLocation) $catParams['location'] = $selectedLocation;
                            if ($selectedCondition) $catParams['condition'] = $selectedCondition;
                            if ($selectedRating) $catParams['rating'] = $selectedRating;
                            if ($selectedStatus) $catParams['status'] = $selectedStatus;
                            if ($priceMin) $catParams['price_min'] = $priceMin;
                            if ($priceMax) $catParams['price_max'] = $priceMax;
                            ?>
                            <a href="<?= route('catalog', array_filter($catParams)); ?>" class="filter-chip <?= $selectedCategory === $cat['slug'] ? 'active' : ''; ?>"><?= htmlspecialchars($cat['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-section-title">Lokasi</h4>
                    <div class="filter-options filter-options-scroll">
                        <label class="filter-check">
                            <input type="radio" name="location" value="" <?= $selectedLocation === '' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>Semua Lokasi</span>
                        </label>
                        <?php foreach ($locations as $loc): ?>
                            <label class="filter-check">
                                <input type="radio" name="location" value="<?= htmlspecialchars($loc); ?>" <?= $selectedLocation === $loc ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span><?= htmlspecialchars($loc); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-section-title">Harga Sewa</h4>
                    <div class="filter-price-row">
                        <div class="filter-price-field">
                            <span class="filter-price-label">Rp</span>
                            <input type="number" name="price_min" placeholder="Min" value="<?= htmlspecialchars($priceMin); ?>" min="0">
                        </div>
                        <span class="filter-price-sep">-</span>
                        <div class="filter-price-field">
                            <span class="filter-price-label">Rp</span>
                            <input type="number" name="price_max" placeholder="Max" value="<?= htmlspecialchars($priceMax); ?>" min="0">
                        </div>
                    </div>
                </div>

                <?php if ($tab === 'produk'): ?>
                <div class="filter-section">
                    <h4 class="filter-section-title">Kondisi Barang</h4>
                    <div class="filter-options">
                        <label class="filter-check">
                            <input type="radio" name="condition" value="" <?= $selectedCondition === '' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>Semua Kondisi</span>
                        </label>
                        <?php foreach ($conditionOpts as $cond): ?>
                            <label class="filter-check">
                                <input type="radio" name="condition" value="<?= htmlspecialchars($cond); ?>" <?= $selectedCondition === $cond ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span><?= htmlspecialchars($cond); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-section-title">Rating</h4>
                    <div class="filter-options">
                        <label class="filter-check">
                            <input type="radio" name="rating" value="" <?= $selectedRating === '' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>Semua Rating</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="rating" value="4" <?= $selectedRating === '4' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php render_icon('star', 'icon-xs icon-star'); ?> 4 ke atas</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="rating" value="3" <?= $selectedRating === '3' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php render_icon('star', 'icon-xs icon-star'); ?> 3 ke atas</span>
                        </label>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-section-title">Status</h4>
                    <div class="filter-options">
                        <label class="filter-check">
                            <input type="radio" name="status" value="" <?= $selectedStatus === '' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>Semua Status</span>
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="status" value="available" <?= $selectedStatus === 'available' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="status-dot status-dot-green"></span>Tersedia
                        </label>
                        <label class="filter-check">
                            <input type="radio" name="status" value="rented" <?= $selectedStatus === 'rented' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="status-dot status-dot-yellow"></span>Sedang Disewa
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-small btn-full">Terapkan Filter</button>
                    <a href="<?= route('catalog', ['tab' => $tab]); ?>" class="btn btn-outline btn-small btn-full" style="margin-top:8px;">Reset</a>
                </div>
            </form>
        </aside>

        <div class="catalog-main">
            <div class="catalog-main-tabs">
                <a href="<?= route('catalog', ['tab' => 'produk', 'search' => $search ?: null, 'category' => $selectedCategory ?: null]); ?>" class="catalog-tab <?= $tab === 'produk' ? 'active' : ''; ?>">
                    <span class="catalog-tab-icon"><?php render_icon('search'); ?></span>
                    Barang Rental
                </a>
                <a href="<?= route('catalog', ['tab' => 'toko', 'search' => $search ?: null]); ?>" class="catalog-tab <?= $tab === 'toko' ? 'active' : ''; ?>">
                    <span class="catalog-tab-icon"><?php render_icon('store'); ?></span>
                    Toko Rental
                </a>
            </div>

            <div class="catalog-main-header">
                <p class="catalog-result-count">
                    <?php if ($tab === 'toko'): ?>
                        Menampilkan <?= $totalCount; ?> toko rental<?= $search !== '' ? ' untuk "' . htmlspecialchars($search) . '"' : ''; ?>
                    <?php else: ?>
                        Menampilkan <?= $totalCount; ?> barang rental<?= $search !== '' ? ' untuk "' . htmlspecialchars($search) . '"' : ''; ?>
                    <?php endif; ?>
                </p>
                <div class="catalog-sort">
                    <label for="sortSelect">Urutkan:</label>
                    <select id="sortSelect" name="sort" onchange="window.location.href=this.value">
                        <?php
                        $sortOptions = [
                            'relevance' => 'Paling Sesuai',
                            'price_low' => 'Harga Terendah',
                            'price_high' => 'Harga Tertinggi',
                            'rating' => 'Rating Tertinggi',
                            'newest' => 'Terbaru',
                        ];
                        $baseSortUrl = route('catalog', array_filter([
                            'tab' => $tab,
                            'category' => $selectedCategory ?: null,
                            'search' => $search ?: null,
                            'location' => $selectedLocation ?: null,
                            'condition' => $selectedCondition ?: null,
                            'rating' => $selectedRating ?: null,
                            'status' => $selectedStatus ?: null,
                            'price_min' => $priceMin ?: null,
                            'price_max' => $priceMax ?: null,
                        ]));
                        $sortConnector = (strpos($baseSortUrl, '?') !== false) ? '&' : '?';
                        foreach ($sortOptions as $key => $label):
                            $url = $baseSortUrl . $sortConnector . 'sort=' . $key;
                        ?>
                            <option value="<?= htmlspecialchars($url); ?>" <?= $sort === $key ? 'selected' : ''; ?>><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="filter-toggle-btn" id="filterToggle" aria-label="Buka filter">
                    <?php render_icon('search', 'icon-sm'); ?>
                    Filter
                </button>
            </div>

            <?php if ($tab === 'toko'): ?>
                <?php if (empty($stores)): ?>
                    <div class="catalog-empty">
                        <div class="catalog-empty-icon"><?php render_icon('store', 'icon-xxl'); ?></div>
                        <h3>Toko tidak ditemukan</h3>
                        <p>Coba gunakan kata kunci lain atau ubah filter pencarian.</p>
                        <a href="<?= route('catalog', ['tab' => 'toko']); ?>" class="btn btn-outline">Reset Filter</a>
                    </div>
                <?php else: ?>
                    <div class="store-grid">
                        <?php foreach ($stores as $store): ?>
                            <article class="store-card">
                                <div class="store-card-logo">
                                    <?php if ($store['logo']): ?>
                                        <img src="<?= BASE_URL . '/uploads/logos/' . htmlspecialchars($store['logo']); ?>" alt="<?= htmlspecialchars($store['name']); ?>">
                                    <?php else: ?>
                                        <div class="store-card-logo-placeholder"><?= mb_substr($store['name'], 0, 1); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="store-card-body">
                                    <h3><?= htmlspecialchars($store['name']); ?></h3>
                                    <?php if ($store['city']): ?>
                                        <p class="store-card-location"><?php render_icon('map-pin', 'icon-xs'); ?><?= htmlspecialchars($store['city']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($store['description']): ?>
                                        <p class="store-card-desc"><?= htmlspecialchars(mb_substr($store['description'], 0, 80)) . (mb_strlen($store['description']) > 80 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                    <p class="store-card-count"><?= $store['product_count']; ?> barang tersedia</p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($products)): ?>
                    <div class="catalog-empty">
                        <div class="catalog-empty-icon"><?php render_icon('search', 'icon-xxl'); ?></div>
                        <h3>Barang rental tidak ditemukan</h3>
                        <p>Coba gunakan kata kunci lain atau ubah filter pencarian.</p>
                        <a href="<?= route('catalog', ['tab' => 'produk']); ?>" class="btn btn-outline">Reset Filter</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $index => $product): ?>
                            <?php include __DIR__ . '/../includes/components/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<div class="filter-overlay" id="filterOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('filterToggle');
    var close = document.getElementById('filterClose');
    var sidebar = document.getElementById('filterSidebar');
    var overlay = document.getElementById('filterOverlay');

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function () {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        function closeFilter() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (close) close.addEventListener('click', closeFilter);
        overlay.addEventListener('click', closeFilter);
    }
});
</script>
<?php else: ?>
<main class="catalog-browse container">
    <section class="catalog-banner-carousel" aria-label="Promo Rentalin">
        <div class="catalog-banner-track" id="catalogBannerTrack">
            <a class="catalog-banner-slide active" href="#products">
                <img src="<?= BASE_URL; ?>/assets/images/banner1.png" alt="Promo Rentalin 1">
            </a>
            <a class="catalog-banner-slide" href="#products">
                <img src="<?= BASE_URL; ?>/assets/images/banner2.png" alt="Promo Rentalin 2">
            </a>
            <a class="catalog-banner-slide" href="#products">
                <img src="<?= BASE_URL; ?>/assets/images/banner3.png" alt="Promo Rentalin 3">
            </a>
        </div>

        <button class="catalog-banner-nav prev" type="button" id="catalogBannerPrev" aria-label="Banner sebelumnya">
            <span aria-hidden="true">&#8249;</span>
        </button>
        <button class="catalog-banner-nav next" type="button" id="catalogBannerNext" aria-label="Banner berikutnya">
            <span aria-hidden="true">&#8250;</span>
        </button>

        <div class="catalog-banner-dots" id="catalogBannerDots" aria-label="Navigasi banner">
            <button class="active" type="button" aria-label="Tampilkan banner 1"></button>
            <button type="button" aria-label="Tampilkan banner 2"></button>
            <button type="button" aria-label="Tampilkan banner 3"></button>
        </div>
    </section>

    <section class="catalog-products" id="products">
        <h2>All Products</h2>

        <div class="catalog-tabs">
            <a class="<?= $selectedCategory === '' || $selectedCategory === 'semua' ? 'active' : ''; ?>" href="<?= route('catalog'); ?>">Semua</a>
            <?php foreach ($categories as $category): ?>
                <a class="<?= $selectedCategory === $category['slug'] ? 'active' : ''; ?>" href="<?= route('catalog', ['category' => $category['slug']]); ?>"><?= htmlspecialchars($category['name']); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="catalog-empty-state">
                <h3>Produk belum tersedia</h3>
                <p>Belum ada toko yang menambahkan produk pada kategori ini</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
                <?php include __DIR__ . '/../includes/components/product-card.php'; ?>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var slides = Array.prototype.slice.call(document.querySelectorAll('.catalog-banner-slide'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('#catalogBannerDots button'));
    var prev = document.getElementById('catalogBannerPrev');
    var next = document.getElementById('catalogBannerNext');
    var index = 0;
    var timer;

    if (!slides.length) return;

    function showSlide(nextIndex) {
        index = (nextIndex + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            slide.classList.toggle('active', i === index);
        });

        dots.forEach(function (dot, i) {
            dot.classList.toggle('active', i === index);
        });
    }

    function startAutoPlay() {
        clearInterval(timer);
        timer = setInterval(function () {
            showSlide(index + 1);
        }, 4500);
    }

    if (prev) {
        prev.addEventListener('click', function () {
            showSlide(index - 1);
            startAutoPlay();
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            showSlide(index + 1);
            startAutoPlay();
        });
    }

    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
            showSlide(i);
            startAutoPlay();
        });
    });

    startAutoPlay();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
