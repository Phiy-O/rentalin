<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('catalog');
}

require_csrf();

$userId = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'cod');
$agreeTerms = isset($_POST['agree_terms']);

if ($productId <= 0 || $startDate === '' || $endDate === '') {
    set_flash('error', 'Lengkapi tanggal sewa dan produk terlebih dahulu.');
    redirect_route('catalog');
}

if (!$agreeTerms) {
    set_flash('error', 'Kamu harus menyetujui syarat dan ketentuan rental.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

if ($paymentMethod !== 'cod') {
    set_flash('error', 'Metode pembayaran belum tersedia.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

if (mb_strlen($notes) > 1000) {
    set_flash('error', 'Catatan maksimal 1000 karakter.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

$start = DateTime::createFromFormat('Y-m-d', $startDate);
$end = DateTime::createFromFormat('Y-m-d', $endDate);
$today = new DateTime('today');

if (!$start || !$end || $start < $today || $end < $start) {
    set_flash('error', 'Tanggal sewa tidak valid.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

$totalDays = $start->diff($end)->days + 1;

$productQuery = "
    SELECT id, store_id, price_per_day, stock, status
    FROM products
    WHERE id = ?
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $productQuery);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product || $product['status'] !== 'available' || (int) $product['stock'] <= 0) {
    set_flash('error', 'Produk tidak tersedia untuk disewa.');
    redirect_route('catalog');
}

$storeId = (int) $product['store_id'];
$pricePerDay = (float) $product['price_per_day'];
$totalPrice = $pricePerDay * $totalDays;

$overlapQuery = "
    SELECT COUNT(*) AS total
    FROM rentals
    WHERE product_id = ?
      AND status IN ('pending', 'approved', 'rented', 'late', 'return_requested')
      AND start_date <= ?
      AND end_date >= ?
";
$overlapStmt = mysqli_prepare($conn, $overlapQuery);
mysqli_stmt_bind_param($overlapStmt, 'iss', $productId, $endDate, $startDate);
mysqli_stmt_execute($overlapStmt);
$overlapCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($overlapStmt))['total'];
mysqli_stmt_close($overlapStmt);

if ($overlapCount >= (int) $product['stock']) {
    set_flash('error', 'Produk tidak tersedia pada tanggal yang dipilih. Silakan pilih jadwal lain.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

$duplicateQuery = "
    SELECT id
    FROM rentals
    WHERE user_id = ?
      AND product_id = ?
      AND start_date = ?
      AND end_date = ?
      AND status IN ('pending', 'approved', 'rented', 'late', 'return_requested')
    LIMIT 1
";
$duplicateStmt = mysqli_prepare($conn, $duplicateQuery);
mysqli_stmt_bind_param($duplicateStmt, 'iiss', $userId, $productId, $startDate, $endDate);
mysqli_stmt_execute($duplicateStmt);
$duplicateRental = mysqli_fetch_assoc(mysqli_stmt_get_result($duplicateStmt));
mysqli_stmt_close($duplicateStmt);

if ($duplicateRental) {
    set_flash('error', 'Pengajuan rental yang sama sudah pernah kamu kirim.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

$insertQuery = "
    INSERT INTO rentals (user_id, product_id, store_id, start_date, end_date, total_days, total_price, status, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
";
$insertStmt = mysqli_prepare($conn, $insertQuery);
mysqli_stmt_bind_param($insertStmt, 'iiissids', $userId, $productId, $storeId, $startDate, $endDate, $totalDays, $totalPrice, $notes);

if (!mysqli_stmt_execute($insertStmt)) {
    set_flash('error', 'Pengajuan rental gagal. Silakan coba lagi.');
    redirect_route('rental.checkout', ['product_id' => $productId]);
}

mysqli_stmt_close($insertStmt);

set_flash('success', 'Pengajuan rental berhasil dikirim. Tunggu konfirmasi dari toko.');
redirect_route('catalog');
