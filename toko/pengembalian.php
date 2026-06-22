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

$lateStmt = mysqli_prepare($conn, "UPDATE rentals SET status = 'late' WHERE store_id = ? AND status = 'rented' AND end_date < CURDATE()");
mysqli_stmt_bind_param($lateStmt, 'i', $storeId);
mysqli_stmt_execute($lateStmt);
mysqli_stmt_close($lateStmt);

function countReturns($conn, $storeId, $statuses) {
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

$countPendingReturn = countReturns($conn, $storeId, ['return_requested']);
$countLate          = countReturns($conn, $storeId, ['late']);
$countRented        = countReturns($conn, $storeId, ['rented']);
$countCompleted     = countReturns($conn, $storeId, ['completed']);

$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterDateStart = $_GET['date_start'] ?? '';
$filterDateEnd = $_GET['date_end'] ?? '';

$where = ["r.store_id = ?", "r.status IN ('return_requested','late','rented','completed')"];
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
    $where[] = "r.end_date >= ?";
    $params[] = $filterDateStart;
    $types .= 's';
}

if ($filterDateEnd !== '') {
    $where[] = "r.end_date <= ?";
    $params[] = $filterDateEnd;
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

$returnQuery = "
    SELECT r.id, r.start_date, r.end_date, r.total_days, r.total_price, r.status, r.notes, r.created_at,
           p.id AS product_id, p.name AS product_name,
           u.id AS user_id, u.name AS user_name, u.username,
           GREATEST(0, DATEDIFF(CURDATE(), r.end_date)) AS late_days,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS product_image
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    WHERE {$whereClause}
    ORDER BY FIELD(r.status, 'return_requested','late','rented','completed'), r.end_date ASC
";

$retStmt = mysqli_prepare($conn, $returnQuery);
if (!empty($params)) {
    mysqli_stmt_bind_param($retStmt, $types, ...$params);
}
mysqli_stmt_execute($retStmt);
$retResult = mysqli_stmt_get_result($retStmt);
$returns = [];
while ($row = mysqli_fetch_assoc($retResult)) {
    $returns[] = $row;
}
mysqli_stmt_close($retStmt);

$statusLabels = [
    'return_requested' => 'Menunggu Konfirmasi',
    'late' => 'Terlambat',
    'rented' => 'Sedang Disewa',
    'completed' => 'Selesai',
];

$activeMenu = 'returns';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header">
                <h1>Pengembalian Barang</h1>
                <p>Kelola pengajuan pengembalian, keterlambatan, dan status barang yang sedang disewa.</p>
            </header>

            <div class="orders-stats">
                <div class="stat-card stat-card-pending">
                    <div class="stat-icon-wrap stat-icon-clock"><?php render_icon('rotate-ccw'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countPendingReturn; ?></span>
                        <span class="stat-label">Menunggu Konfirmasi</span>
                    </div>
                </div>
                <div class="stat-card stat-card-late">
                    <div class="stat-icon-wrap stat-icon-warn"><?php render_icon('triangle-alert'); ?></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $countLate; ?></span>
                        <span class="stat-label">Terlambat</span>
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
                <form class="orders-filter-form" method="GET" action="<?= route('toko.returns'); ?>">
                    <input type="text" name="q" class="orders-search" placeholder="Cari nama penyewa atau barang..." value="<?= htmlspecialchars($search); ?>">
                    <select name="status" class="orders-select">
                        <option value="">Semua Status</option>
                        <option value="return_requested" <?= $filterStatus === 'return_requested' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                        <option value="late" <?= $filterStatus === 'late' ? 'selected' : ''; ?>>Terlambat</option>
                        <option value="rented" <?= $filterStatus === 'rented' ? 'selected' : ''; ?>>Sedang Disewa</option>
                        <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                    <input type="date" name="date_start" class="orders-date" value="<?= htmlspecialchars($filterDateStart); ?>" placeholder="Tanggal Mulai">
                    <input type="date" name="date_end" class="orders-date" value="<?= htmlspecialchars($filterDateEnd); ?>" placeholder="Tanggal Akhir">
                    <button type="submit" class="btn btn-outline btn-small">Cari</button>
                </form>
            </div>

            <?php if (empty($returns)): ?>
                <div class="empty-orders">
                    <div class="empty-orders-icon"><?php render_icon('rotate-ccw'); ?></div>
                    <h3>Belum ada pengembalian barang</h3>
                    <p>Data pengembalian akan muncul saat penyewa mengajukan pengembalian barang.</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($returns as $return): ?>
                        <div class="order-card">
                            <div class="order-card-top">
                                <div class="order-card-image">
                                    <?php render_product_image($return['product_image'] ?? '', $return['product_name']); ?>
                                </div>
                                <span class="status-badge status-<?= str_replace('_', '-', $return['status']); ?>">
                                    <?= htmlspecialchars($statusLabels[$return['status']] ?? ucfirst($return['status'])); ?>
                                </span>
                            </div>
                            <div class="order-card-body">
                                <h4 class="order-card-title"><?= htmlspecialchars($return['product_name']); ?></h4>
                                <div class="order-card-renter">
                                    <span class="order-card-renter-icon"><?php render_icon('circle-user-round'); ?></span>
                                    <?= htmlspecialchars($return['user_name'] ?: $return['username']); ?>
                                </div>
                                <div class="order-card-dates">
                                    <?php
                                    $start = date('j M Y', strtotime($return['start_date']));
                                    $end = date('j M Y', strtotime($return['end_date']));
                                    ?>
                                    <?= $start; ?> — <?= $end; ?>
                                </div>
                                <div class="order-card-total">
                                    Rp<?= number_format((float) $return['total_price'], 0, ',', '.'); ?>
                                </div>
                                <?php if ($return['status'] === 'late' && $return['late_days'] > 0): ?>
                                    <div class="return-late-badge">
                                        <?php render_icon('triangle-alert'); ?>Terlambat <?= $return['late_days']; ?> Hari
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($return['notes'])): ?>
                                    <div class="order-card-notes">
                                        <span class="notes-label">Catatan:</span>
                                        <?= htmlspecialchars($return['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="order-card-actions">
                                <?php if ($return['status'] === 'return_requested'): ?>
                                    <a href="<?= route('rental.return.complete', ['id' => $return['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-accept"><?php render_icon('check'); ?>Terima</a>
                                    <a href="<?= route('rental.return.reject', ['id' => $return['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-reject"><?php render_icon('x'); ?>Tolak</a>
                                    <a href="<?= route('toko.order.detail', ['id' => $return['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php elseif ($return['status'] === 'late'): ?>
                                    <a href="<?= route('rental.return.complete', ['id' => $return['id'], '_token' => generate_csrf_token()]); ?>" class="btn-action btn-action-accept"><?php render_icon('check-check'); ?>Selesaikan</a>
                                    <a href="<?= route('toko.order.detail', ['id' => $return['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php elseif ($return['status'] === 'rented'): ?>
                                    <a href="#" class="btn-action btn-action-chat"><?php render_icon('message-circle-more'); ?>Chat</a>
                                    <a href="<?= route('toko.order.detail', ['id' => $return['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php else: ?>
                                    <a href="<?= route('toko.order.detail', ['id' => $return['id']]); ?>" class="btn-action btn-action-detail"><?php render_icon('eye'); ?>Detail</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
