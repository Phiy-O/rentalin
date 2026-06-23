<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

require_csrf_get();

$rentalId = (int) ($_GET['id'] ?? 0);

if ($rentalId <= 0) {
    set_flash('error', 'ID pesanan tidak valid.');
    redirect_route('toko.orders');
}

mysqli_begin_transaction($conn);

$stmt = mysqli_prepare($conn, "
    UPDATE rentals r
    INNER JOIN stores s ON s.id = r.store_id
    SET r.status = 'rented'
    WHERE r.id = ? AND s.user_id = ? AND r.status = 'approved'
");
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);
$rentalOk = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
mysqli_stmt_close($stmt);

$stockOk = true;
if ($rentalOk) {
    $stockStmt = mysqli_prepare($conn, "UPDATE products SET stock = stock - 1 WHERE id = (SELECT product_id FROM rentals WHERE id = ?) AND stock > 0");
    mysqli_stmt_bind_param($stockStmt, 'i', $rentalId);
    $stockOk = mysqli_stmt_execute($stockStmt) && mysqli_stmt_affected_rows($stockStmt) > 0;
    mysqli_stmt_close($stockStmt);
}

if ($rentalOk && $stockOk) {
    mysqli_commit($conn);
    set_flash('success', 'Rental berhasil dimulai.');
} else {
    mysqli_rollback($conn);
    set_flash('error', 'Rental tidak dapat dimulai atau bukan milik toko kamu.');
}

redirect_route('toko.orders');
