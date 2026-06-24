<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$storeResult = mysqli_stmt_get_result($storeStmt);
$store = mysqli_fetch_assoc($storeResult);

if (!$store) {
    set_flash('error', 'Kamu belum memiliki toko. Silakan buat toko terlebih dahulu.');
    redirect_route('toko.create');
}

$storeId = (int) $store['id'];

$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = ["p.store_id = ?"];
$params = [$storeId];
$types = 'i';

if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

if ($filterStatus === 'available' || $filterStatus === 'unavailable') {
    $where[] = "p.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterCategory !== '' && is_numeric($filterCategory)) {
    $where[] = "p.category_id = ?";
    $params[] = (int) $filterCategory;
    $types .= 'i';
}

$whereClause = implode(' AND ', $where);

$countQuery = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE {$whereClause}";
$countStmt = mysqli_prepare($conn, $countQuery);
mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
mysqli_stmt_bind_result($countStmt, $totalCount);
mysqli_stmt_fetch($countStmt);
mysqli_stmt_close($countStmt);

$totalPages = max(1, (int) ceil($totalCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$productQuery = "
    SELECT p.id, p.name, p.price_per_day, p.stock, p.condition_status, p.status, p.created_at,
           c.name AS category_name,
           (SELECT image FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS product_image
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE {$whereClause}
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$prodStmt = mysqli_prepare($conn, $productQuery);
mysqli_stmt_bind_param($prodStmt, $types, ...$params);
mysqli_stmt_execute($prodStmt);
$prodResult = mysqli_stmt_get_result($prodStmt);
$products = [];
while ($row = mysqli_fetch_assoc($prodResult)) {
    $products[] = $row;
}
mysqli_stmt_close($prodStmt);

$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    $categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
}

$paginationBaseUrl = route('toko.products', array_filter([
    'q' => $search ?: null,
    'status' => $filterStatus ?: null,
    'category' => $filterCategory !== '' ? $filterCategory : null,
]));
$paginationHtml = render_pagination($totalCount, $perPage, $page, $paginationBaseUrl);

$activeMenu = 'products';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header">
                <h1>Barang Saya</h1>
                <p>Kelola daftar barang rental yang tersedia di toko kamu.</p>
            </header>

            <div class="products-toolbar">
                <form class="products-filter-form" method="GET" action="<?= route('toko.products'); ?>">
                    <input type="text" name="q" class="products-search" placeholder="Cari barang..." value="<?= htmlspecialchars($search); ?>">
                    <select name="status" class="products-select">
                        <option value="">Semua Status</option>
                        <option value="available" <?= $filterStatus === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="unavailable" <?= $filterStatus === 'unavailable' ? 'selected' : ''; ?>>Tidak Aktif</option>
                    </select>
                    <select name="category" class="products-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= $filterCategory == $cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline btn-small">Cari</button>
                </form>
                <a href="<?= route('toko.products.create'); ?>" class="btn btn-primary btn-small">+ Tambah Barang</a>
            </div>

            <div class="products-list-card">
                <?php if (empty($products)): ?>
                    <div class="empty-products">
                        <div class="empty-products-icon"></div>
                        <h3>Belum ada barang rental</h3>
                        <p>Mulai tambahkan barang pertama untuk toko kamu.</p>
                        <a href="<?= route('toko.products.create'); ?>" class="btn btn-primary">+ Tambah Barang</a>
                    </div>
                <?php else: ?>
                    <div class="products-list">
                        <?php foreach ($products as $product): ?>
                            <div class="product-item">
                                <div class="product-item-image">
                                    <?php render_product_image($product['product_image'] ?? '', $product['name']); ?>
                                </div>
                                <div class="product-item-info">
                                    <h4 class="product-item-name"><?= htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-item-meta">
                                        <span class="product-item-price">Rp<?= number_format((float) $product['price_per_day'], 0, ',', '.'); ?>/hari</span>
                                        <?php if (!empty($product['category_name'])): ?>
                                            <span class="product-item-category"><?= htmlspecialchars($product['category_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-item-status">
                                    <?php if ($product['status'] === 'available'): ?>
                                        <span class="status-badge status-available">Tersedia</span>
                                    <?php else: ?>
                                        <span class="status-badge status-unavailable">Tidak Aktif</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-item-actions">
                                    <a href="<?= route('toko.products.edit', ['id' => $product['id']]); ?>" class="btn-action btn-action-edit">Edit</a>
                                    <a href="<?= route('toko.products.toggle', ['id' => $product['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action <?= $product['status'] === 'available' ? 'btn-action-disable' : 'btn-action-enable'; ?>" onclick="return confirm('<?= $product['status'] === 'available' ? 'Nonaktifkan' : 'Aktifkan'; ?> barang ini?')">
                                        <?= $product['status'] === 'available' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                    </a>
                                    <a href="<?= route('toko.products.detail', ['id' => $product['id']]); ?>" class="btn-action btn-action-detail">Detail</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= $paginationHtml; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
