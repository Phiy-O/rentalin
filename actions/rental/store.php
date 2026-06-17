<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('catalog');
}

$userId = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$agreeTerms = isset($_POST['agree_terms']);

if ($productId <= 0 || $startDate === '' || $endDate === '') {
    set_flash('error', 'Lengkapi tanggal sewa dan produk terlebih dahulu.');
    redirect_route('catalog');
}

if (!$agreeTerms) {
    set_flash('error', 'Kamu harus menyetujui syarat dan ketentuan rental.');
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

set_flash('success', 'Pengajuan rental berhasil dikirim. Tunggu konfirmasi dari toko.');
redirect_route('catalog');
