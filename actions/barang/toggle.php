<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_csrf_get();

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    set_flash('error', 'ID barang tidak valid.');
    redirect_route('toko.products');
}

$storeStmt = mysqli_prepare($conn, "SELECT id FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$storeResult = mysqli_stmt_get_result($storeStmt);
$store = mysqli_fetch_assoc($storeResult);

if (!$store) {
    set_flash('error', 'Kamu tidak memiliki toko.');
    redirect_route('toko.create');
}

$checkStmt = mysqli_prepare($conn, "SELECT id, status FROM products WHERE id = ? AND store_id = ?");
mysqli_stmt_bind_param($checkStmt, 'ii', $productId, $store['id']);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$product = mysqli_fetch_assoc($checkResult);

if (!$product) {
    set_flash('error', 'Barang tidak ditemukan.');
    redirect_route('toko.products');
}

$newStatus = $product['status'] === 'available' ? 'unavailable' : 'available';

$updateStmt = mysqli_prepare($conn, "UPDATE products SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($updateStmt, 'si', $newStatus, $productId);

if (mysqli_stmt_execute($updateStmt)) {
    $msg = $newStatus === 'available' ? 'Barang berhasil diaktifkan.' : 'Barang berhasil dinonaktifkan.';
    set_flash('success', $msg);
} else {
    set_flash('error', 'Gagal mengubah status barang.');
}

redirect_route('toko.products');
