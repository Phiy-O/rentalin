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

$lateStmt = mysqli_prepare($conn, "UPDATE rentals SET status = 'late' WHERE store_id = ? AND status = 'rented' AND end_date < CURDATE()");
mysqli_stmt_bind_param($lateStmt, 'i', $storeId);
mysqli_stmt_execute($lateStmt);
mysqli_stmt_close($lateStmt);

function countOrders($conn, $storeId, $statuses) {
    if (is_array($statuses)) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $types = str_repeat('s', count($statuses)) . 'i';
        $params = array_merge($statuses, [$storeId]);
    } else {
        $placeholders = '?';
        $types = 'si';
        $params = [$statuses, $storeId];
    }
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rentals WHERE status IN ($placeholders) AND store_id = ?");
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count;
}

$countPending  = countOrders($conn, $storeId, ['pending']);
$countApproved = countOrders($conn, $storeId, ['approved']);
$countRented   = countOrders($conn, $storeId, ['rented']);
$countCompleted = countOrders($conn, $storeId, ['completed']);

$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd = $_GET['date_end'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = ["r.store_id = ?"];
$params = [$storeId];
$types = 'i';

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR u.name LIKE ? OR u.username LIKE ?)";
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= 'sss';
}

if ($filterStatus !== '') {
    $where[] = "r.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterDateStart !== '') {
    $where[] = "r.start_date >= ?";
    $params[] = $filterDateStart;
    $types .= 's';
}

if ($filterDateEnd !== '') {
    $where[] = "r.end_date <= ?";
    $params[] = $filterDateEnd;
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

$countQuery = "
    SELECT COUNT(*)
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    WHERE {$whereClause}
";
$countStmt = mysqli_prepare($conn, $countQuery);
if (!empty($params)) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}
mysqli_stmt_execute($countStmt);
mysqli_stmt_bind_result($countStmt, $totalCount);
mysqli_stmt_fetch($countStmt);
mysqli_stmt_close($countStmt);

$totalPages = max(1, (int) ceil($totalCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$orderQuery = "
    SELECT r.id, r.start_date, r.end_date, r.total_days, r.total_price, r.status, r.notes, r.created_at,
           p.id AS product_id, p.name AS product_name,
           u.id AS user_id, u.name AS user_name, u.username,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS product_image
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    WHERE {$whereClause}
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$ordStmt = mysqli_prepare($conn, $orderQuery);
if (!empty($params)) {
    mysqli_stmt_bind_param($ordStmt, $types, ...$params);
}
mysqli_stmt_execute($ordStmt);
$ordResult = mysqli_stmt_get_result($ordStmt);
$orders = [];
while ($row = mysqli_fetch_assoc($ordResult)) {
    $orders[] = $row;
}
mysqli_stmt_close($ordStmt);

$statusLabels = [
    'pending' => 'Menunggu Konfirmasi',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'rented' => 'Sedang Disewa',
    'late' => 'Terlambat',
    'return_requested' => 'Pengembalian',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

$paginationBaseUrl = route('toko.orders', array_filter([
    'q' => $search ?: null,
    'status' => $filterStatus ?: null,
    'date_start' => $filterDateStart ?: null,
    'date_end' => $filterDateEnd ?: null,
]));
$paginationHtml = render_pagination($totalCount, $perPage, $page, $paginationBaseUrl);

$activeMenu = 'orders';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header">
                <h1>Pesanan Rental</h1>
                <p>Kelola semua pesanan masuk, disetujui, sedang disewa, dan selesai.</p>
            </header>

            <div class="orders-stats">
                <div class="stat-card stat-card-pending">
                    <div class="stat-icon-wrap stat-icon-clock"><?php render_icon('clock'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countPending; ?></span>
                        <span class="stat-label">Menunggu Konfirmasi</span>
                    </div>
                </div>
                <div class="stat-card stat-card-approved">
                    <div class="stat-icon-wrap stat-icon-check"><?php render_icon('circle-check-big'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countApproved; ?></span>
                        <span class="stat-label">Disetujui</span>
                    </div>
                </div>
                <div class="stat-card stat-card-rented">
                    <div class="stat-icon-wrap stat-icon-rent"><?php render_icon('circle-play'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countRented; ?></span>
                        <span class="stat-label">Sedang Disewa</span>
                    </div>
                </div>
                <div class="stat-card stat-card-completed">
                    <div class="stat-icon-wrap stat-icon-done"><?php render_icon('check-check'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countCompleted; ?></span>
                        <span class="stat-label">Selesai</span>
                    </div>
                </div>
            </div>

            <div class="orders-toolbar">
                <form class="orders-filter-form" method="GET" action="<?= route('toko.orders'); ?>">
                    <input type="text" name="q" class="orders-search" placeholder="Cari nama penyewa atau barang..." value="<?= htmlspecialchars($search); ?>">
                    <select name="status" class="orders-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                        <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="rented" <?= $filterStatus === 'rented' ? 'selected' : ''; ?>>Sedang Disewa</option>
                        <option value="late" <?= $filterStatus === 'late' ? 'selected' : ''; ?>>Terlambat</option>
                        <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                    <input type="date" name="date_start" class="orders-date" value="<?= htmlspecialchars($filterDateStart); ?>" placeholder="Tanggal Mulai">
                    <input type="date" name="date_end" class="orders-date" value="<?= htmlspecialchars($filterDateEnd); ?>" placeholder="Tanggal Selesai">
                    <button type="submit" class="btn btn-outline btn-small">Cari</button>
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <div class="empty-orders-icon"><?php render_icon('clipboard-list'); ?></div>
                    <h3>Belum ada pesanan rental</h3>
                    <p>Pesanan dari penyewa akan muncul di halaman ini.</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-card-top">
                                <div class="order-card-image">
                                    <?php render_product_image($order['product_image'] ?? '', $order['product_name']); ?>
                                </div>
                                <span class="status-badge status-<?= str_replace('_', '-', $order['status']); ?>">
                                    <?= htmlspecialchars($statusLabels[$order['status']] ?? ucfirst($order['status'])); ?>
                                </span>
                            </div>
                            <div class="order-card-body">
                                <h4 class="order-card-title"><?= htmlspecialchars($order['product_name']); ?></h4>
                                <div class="order-card-renter">
                                    <span class="order-card-renter-icon"><?php render_icon('circle-user-round'); ?></span>
                                    <?= htmlspecialchars($order['user_name'] ?: $order['username']); ?>
                                </div>
                                <div class="order-card-dates">
                                    <?php
                                    $start = date('j M Y', strtotime($order['start_date']));
                                    $end = date('j M Y', strtotime($order['end_date']));
                                    ?>
                                    <?= $start; ?> — <?= $end; ?>
                                </div>
                                <div class="order-card-total">
                                    Rp<?= number_format((float) $order['total_price'], 0, ',', '.'); ?>
                                </div>
                                <?php if (!empty($order['notes'])): ?>
                                    <div class="order-card-notes">
                                        <span class="notes-label">Catatan:</span>
                                        <?= htmlspecialchars($order['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="order-card-actions">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="<?= route('rental.accept', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-accept" onclick="return confirm('Terima pesanan ini?')"><?php render_icon('check'); ?>Terima</a>
                                    <a href="<?= route('rental.reject', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-reject" onclick="return confirm('Tolak pesanan ini?')"><?php render_icon('x'); ?>Tolak</a>
                                    <a href="<?= route('toko.order.detail', ['id' => $order['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php elseif ($order['status'] === 'approved' || $order['status'] === 'rented'): ?>
                                    <?php if ($order['status'] === 'approved'): ?>
                                        <a href="<?= route('rental.start', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-accept" onclick="return confirm('Mulai rental? Stok barang akan dikurangi.')"><?php render_icon('play'); ?>Mulai Rental</a>
                                    <?php else: ?>
                                        <a href="#" class="btn-action btn-action-chat"><?php render_icon('message-circle-more'); ?>Chat</a>
                                    <?php endif; ?>
                                    <a href="<?= route('toko.order.detail', ['id' => $order['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php else: ?>
                                    <a href="<?= route('toko.order.detail', ['id' => $order['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= $paginationHtml; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
