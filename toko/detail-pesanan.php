<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$rentalId = (int) ($_GET['id'] ?? 0);

if ($rentalId <= 0) {
    set_flash('error', 'ID pesanan tidak valid.');
    redirect_route('toko.orders');
}

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$store = mysqli_fetch_assoc(mysqli_stmt_get_result($storeStmt));
mysqli_stmt_close($storeStmt);

if (!$store) {
    set_flash('error', 'Kamu belum memiliki toko. Silakan buat toko terlebih dahulu.');
    redirect_route('toko.create');
}

$orderStmt = mysqli_prepare($conn, "
    SELECT r.*, p.name AS product_name, p.description AS product_description, p.price_per_day,
           u.name AS user_name, u.username, u.email AS user_email, u.phone AS user_phone, u.address AS user_address,
           rr.return_date, rr.condition_notes, rr.status AS return_status,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS product_image
    FROM rentals r
    INNER JOIN products p ON p.id = r.product_id
    INNER JOIN users u ON u.id = r.user_id
    LEFT JOIN rental_returns rr ON rr.rental_id = r.id
    WHERE r.id = ? AND r.store_id = ?
    ORDER BY rr.id DESC
    LIMIT 1
");
mysqli_stmt_bind_param($orderStmt, 'ii', $rentalId, $store['id']);
mysqli_stmt_execute($orderStmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($orderStmt));
mysqli_stmt_close($orderStmt);

if (!$order) {
    set_flash('error', 'Pesanan tidak ditemukan atau bukan milik toko kamu.');
    redirect_route('toko.orders');
}

$statusLabels = [
    'pending' => 'Menunggu Konfirmasi',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'rented' => 'Sedang Disewa',
    'late' => 'Terlambat',
    'return_requested' => 'Pengembalian Diajukan',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

$activeMenu = in_array($order['status'], ['return_requested', 'late', 'completed'], true) ? 'returns' : 'orders';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header">
                <h1>Detail Pesanan #<?= (int) $order['id']; ?></h1>
                <p>Informasi lengkap pesanan rental dan status pengembaliannya.</p>
            </header>

            <div class="profile-content profile-biodata">
                <aside class="profile-photo-card">
                    <div class="order-card-image" style="width:100%;height:220px;border-radius:16px;overflow:hidden;">
                        <?php render_product_image($order['product_image'] ?? '', $order['product_name']); ?>
                    </div>
                    <p><?= htmlspecialchars($order['product_description'] ?: 'Tidak ada deskripsi produk.'); ?></p>
                </aside>

                <section class="profile-details-card">
                    <div class="profile-section-head">
                        <span>Status Pesanan</span>
                        <h2><?= htmlspecialchars($order['product_name']); ?></h2>
                        <p><span class="status-badge status-<?= str_replace('_', '-', $order['status']); ?>"><?= htmlspecialchars($statusLabels[$order['status']] ?? ucfirst($order['status'])); ?></span></p>
                    </div>

                    <div class="profile-info-grid">
                        <div class="profile-info-item">
                            <span>Penyewa</span>
                            <strong><?= htmlspecialchars($order['user_name'] ?: $order['username']); ?></strong>
                        </div>
                        <div class="profile-info-item">
                            <span>Kontak</span>
                            <strong><?= htmlspecialchars($order['user_phone'] ?: $order['user_email']); ?></strong>
                        </div>
                        <div class="profile-info-item">
                            <span>Tanggal Sewa</span>
                            <strong><?= date('j M Y', strtotime($order['start_date'])); ?> - <?= date('j M Y', strtotime($order['end_date'])); ?></strong>
                        </div>
                        <div class="profile-info-item">
                            <span>Durasi</span>
                            <strong><?= (int) $order['total_days']; ?> hari</strong>
                        </div>
                        <div class="profile-info-item">
                            <span>Harga per Hari</span>
                            <strong>Rp<?= number_format((float) $order['price_per_day'], 0, ',', '.'); ?></strong>
                        </div>
                        <div class="profile-info-item">
                            <span>Total</span>
                            <strong>Rp<?= number_format((float) $order['total_price'], 0, ',', '.'); ?></strong>
                        </div>
                        <div class="profile-info-item profile-info-wide">
                            <span>Alamat Penyewa</span>
                            <strong><?= htmlspecialchars($order['user_address'] ?: 'Belum ditambahkan'); ?></strong>
                        </div>
                        <div class="profile-info-item profile-info-wide">
                            <span>Catatan Pesanan</span>
                            <strong><?= htmlspecialchars($order['notes'] ?: 'Tidak ada catatan.'); ?></strong>
                        </div>
                        <?php if (!empty($order['return_date'])): ?>
                            <div class="profile-info-item">
                                <span>Tanggal Pengembalian</span>
                                <strong><?= date('j M Y', strtotime($order['return_date'])); ?></strong>
                            </div>
                            <div class="profile-info-item">
                                <span>Status Pengembalian</span>
                                <strong><?= htmlspecialchars(ucfirst($order['return_status'])); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-form-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="<?= route('rental.reject', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-outline" onclick="return confirm('Tolak pesanan ini?')">Tolak</a>
                            <a href="<?= route('rental.accept', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-primary" onclick="return confirm('Terima pesanan ini?')">Terima</a>
                        <?php elseif ($order['status'] === 'approved'): ?>
                            <a href="<?= route('rental.start', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-primary" onclick="return confirm('Mulai rental? Stok barang akan dikurangi.')">Mulai Rental</a>
                        <?php elseif (in_array($order['status'], ['return_requested', 'late'], true)): ?>
                            <?php if ($order['status'] === 'return_requested'): ?>
                                <a href="<?= route('rental.return.reject', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-outline" onclick="return confirm('Tolak pengembalian ini?')">Tolak Pengembalian</a>
                            <?php endif; ?>
                            <a href="<?= route('rental.return.complete', ['id' => $order['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-primary" onclick="return confirm('Selesaikan pengembalian ini?')">Selesaikan Pengembalian</a>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
