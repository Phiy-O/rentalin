<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/header.php';
// Load page‑specific stylesheet
echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/rental-return.css?v=' . filemtime(__DIR__ . '/../assets/css/rental-return.css') . '">';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int) $_SESSION['user_id'];

// Fetch rentals that can be returned
$query = "
    SELECT r.id, r.start_date, r.end_date, r.total_price, r.status,
           p.name AS product_name, s.name AS store_name, p.stock
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN stores s ON r.store_id = s.id
    WHERE r.user_id = ? AND r.status IN ('pending','rented','late')
    ORDER BY r.end_date ASC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rentals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rentals[] = $row;
}
mysqli_stmt_close($stmt);
?>
<main class="container rental-returns-page">
    <div class="rental-layout">
        <?php show_flash(); ?>
        <h1>Pengembalian Rental Saya</h1>
        <?php if (empty($rentals)): ?>
            <p>Tidak ada rental yang dapat diajukan pengembalian.</p>
        <?php else: ?>
            <table class="rental-returns-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Toko</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['product_name']); ?></td>
                            <td><?= htmlspecialchars($r['store_name']); ?></td>
                            <td><?= htmlspecialchars($r['start_date']); ?> → <?= htmlspecialchars($r['end_date']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['status'])); ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <a href="<?= route('rental.cancel', ['id' => $r['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-warning">Batalkan Rental</a>
                                <?php else: ?>
                                    <a href="<?= route('rental.request.return', ['id' => $r['id'], '_token' => generate_csrf_token()]); ?>" class="btn btn-primary">Ajukan Pengembalian</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>