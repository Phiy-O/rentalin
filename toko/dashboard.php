<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';
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

$totalProducts = 0;
$pendingOrders = 0;
$activeRentals = 0;
$returnRequests = 0;

$prodStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE store_id = ?");
mysqli_stmt_bind_param($prodStmt, 'i', $storeId);
mysqli_stmt_execute($prodStmt);
mysqli_stmt_bind_result($prodStmt, $totalProducts);
mysqli_stmt_fetch($prodStmt);
mysqli_stmt_close($prodStmt);

$pendStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rentals WHERE store_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($pendStmt, 'i', $storeId);
mysqli_stmt_execute($pendStmt);
mysqli_stmt_bind_result($pendStmt, $pendingOrders);
mysqli_stmt_fetch($pendStmt);
mysqli_stmt_close($pendStmt);

$actvStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rentals WHERE store_id = ? AND status IN ('approved', 'rented')");
mysqli_stmt_bind_param($actvStmt, 'i', $storeId);
mysqli_stmt_execute($actvStmt);
mysqli_stmt_bind_result($actvStmt, $activeRentals);
mysqli_stmt_fetch($actvStmt);
mysqli_stmt_close($actvStmt);

$retStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rentals WHERE store_id = ? AND status = 'return_requested'");
mysqli_stmt_bind_param($retStmt, 'i', $storeId);
mysqli_stmt_execute($retStmt);
mysqli_stmt_bind_result($retStmt, $returnRequests);
mysqli_stmt_fetch($retStmt);
mysqli_stmt_close($retStmt);

$recentOrders = [];
$ordStmt = mysqli_prepare($conn, "
    SELECT r.id, r.start_date, r.end_date, r.total_days, r.total_price, r.status, r.created_at,
           p.id as product_id, p.name as product_name,
           u.username, u.name as user_name,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as product_image
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    WHERE r.store_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($ordStmt, 'i', $storeId);
mysqli_stmt_execute($ordStmt);
$ordResult = mysqli_stmt_get_result($ordStmt);
while ($row = mysqli_fetch_assoc($ordResult)) {
    $recentOrders[] = $row;
}
mysqli_stmt_close($ordStmt);

$activeMenu = 'dashboard';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header">
                <h1>Dashboard <?= htmlspecialchars($store['name']); ?></h1>
                <p>Ringkasan aktivitas toko rental kamu.</p>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-products"></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $totalProducts; ?></span>
                        <span class="stat-label">Total Barang</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-orders"></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $pendingOrders; ?></span>
                        <span class="stat-label">Pesanan Masuk</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-active"></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $activeRentals; ?></span>
                        <span class="stat-label">Rental Aktif</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-returns"></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $returnRequests; ?></span>
                        <span class="stat-label">Menunggu Pengembalian</span>
                    </div>
                </div>
            </div>

            <section class="recent-orders">
                <h2>Pesanan Masuk</h2>

                <?php if (empty($recentOrders)): ?>
                    <div class="empty-orders">
                        <p>Belum ada pesanan masuk.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-table">
                        <div class="orders-header">
                            <span class="col-product">Barang</span>
                            <span class="col-user">Penyewa</span>
                            <span class="col-date">Tanggal Sewa</span>
                            <span class="col-status">Status</span>
                            <span class="col-actions">Aksi</span>
                        </div>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="orders-row">
                                <div class="col-product">
                                    <div class="order-product">
                                        <?php
                                        render_product_image($order['product_image'] ?? '', $order['product_name']);
                                        ?>
                                        <span class="order-product-name"><?= htmlspecialchars($order['product_name']); ?></span>
                                    </div>
                                </div>
                                <div class="col-user">
                                    <span class="order-user"><?= htmlspecialchars($order['user_name'] ?: $order['username']); ?></span>
                                </div>
                                <div class="col-date">
                                    <?php
                                    $start = date('j M', strtotime($order['start_date']));
                                    $end = date('j M', strtotime($order['end_date']));
                                    ?>
                                    <span class="order-date"><?= $start; ?> — <?= $end; ?></span>
                                </div>
                                <div class="col-status">
                                    <span class="status-badge status-<?= str_replace('_', '-', $order['status']); ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                                </div>
                                <div class="col-actions">
                                    <div class="order-actions">
                                        <a href="<?= route('rental.reject', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-reject" title="Tolak">Tolak</a>
                                        <a href="<?= route('rental.accept', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-accept" title="Terima">Terima</a>
                                        <a href="<?= route('toko.order.detail', ['id' => $order['id']]); ?>" class="btn-action btn-action-detail" title="Detail">Detail</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
