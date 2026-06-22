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

$stmt = mysqli_prepare($conn, "
    UPDATE rentals r
    INNER JOIN stores s ON s.id = r.store_id
    SET r.status = 'rented'
    WHERE r.id = ? AND s.user_id = ? AND r.status = 'approved'
");

// Decrease product stock by 1
$stockStmt = mysqli_prepare($conn, "UPDATE products SET stock = stock - 1 WHERE id = (SELECT product_id FROM rentals WHERE id = ?) AND stock > 0");
mysqli_stmt_bind_param($stockStmt, 'i', $rentalId);
mysqli_stmt_execute($stockStmt);
mysqli_stmt_close($stockStmt);
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    set_flash('success', 'Rental berhasil dimulai.');
} else {
    set_flash('error', 'Rental tidak dapat dimulai atau bukan milik toko kamu.');
}
mysqli_stmt_close($stmt);

redirect_route('toko.orders');
