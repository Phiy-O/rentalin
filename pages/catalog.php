<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$selectedCategory = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

$categories = [];
$categoryResult = mysqli_query($conn, 'SELECT name, slug FROM categories ORDER BY name ASC');

while ($category = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $category;
}

$conditions = ["p.status = 'available'"];

if ($selectedCategory !== '' && $selectedCategory !== 'semua') {
    $safeCategory = mysqli_real_escape_string($conn, $selectedCategory);
    $conditions[] = "c.slug = '{$safeCategory}'";
}

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(p.name LIKE '%{$safeSearch}%' OR p.description LIKE '%{$safeSearch}%' OR s.name LIKE '%{$safeSearch}%')";
}

$whereClause = implode(' AND ', $conditions);
$productQuery = "
    SELECT
        p.id,
        p.name,
        p.price_per_day,
        p.stock,
        p.condition_status,
        s.name AS store_name,
        c.name AS category_name,
        c.slug AS category_slug,
        pi.image
    FROM products p
    INNER JOIN stores s ON s.id = p.store_id
    INNER JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE {$whereClause}
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 24
";

$productResult = mysqli_query($conn, $productQuery);
$products = [];

while ($product = mysqli_fetch_assoc($productResult)) {
    $products[] = $product;
}
?>

<main class="catalog-page container">
    <section class="catalog-promo">
        <div class="catalog-promo-content">
            <h1>CALL TO ACTION<br>CUST Rp30.000,00-</h1>
            <p>
                Temukan barang rental terbaik untuk kebutuhan harian, acara, hobi,
                dan perjalananmu dengan harga yang lebih hemat.
            </p>
            <a class="btn btn-primary" href="<?= route('catalog'); ?>#products">Lihat Produk</a>
        </div>
        <div class="catalog-promo-visual"></div>
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
                <p>Import `database/dummy_products.sql` untuk menambahkan produk dummy pertama.</p>
            </div>
        <?php else: ?>
            <div class="catalog-grid">
            <?php foreach ($products as $index => $product): ?>
                <?php require __DIR__ . '/../includes/components/product-card.php'; ?>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
