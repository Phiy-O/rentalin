<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

require_csrf_get();

$rentalId = (int) ($_GET['id'] ?? 0);

if ($rentalId <= 0) {
    set_flash('error', 'ID pengembalian tidak valid.');
    redirect_route('toko.returns');
}

$stmt = mysqli_prepare($conn, "
    SELECT r.id
    FROM rentals r
    INNER JOIN stores s ON s.id = r.store_id
    WHERE r.id = ? AND s.user_id = ? AND r.status IN ('return_requested', 'late')
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$rental = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$rental) {
    set_flash('error', 'Pengembalian tidak dapat diselesaikan atau bukan milik toko kamu.');
    redirect_route('toko.returns');
}

mysqli_begin_transaction($conn);

$existingStmt = mysqli_prepare($conn, "SELECT id FROM rental_returns WHERE rental_id = ? ORDER BY id DESC LIMIT 1");
$existingReturnId = 0;
mysqli_stmt_bind_param($existingStmt, 'i', $rentalId);
mysqli_stmt_execute($existingStmt);
mysqli_stmt_bind_result($existingStmt, $existingReturnId);
mysqli_stmt_fetch($existingStmt);
mysqli_stmt_close($existingStmt);

if ($existingReturnId > 0) {
    $returnStmt = mysqli_prepare($conn, "UPDATE rental_returns SET return_date = CURDATE(), status = 'completed' WHERE id = ?");
    mysqli_stmt_bind_param($returnStmt, 'i', $existingReturnId);
} else {
    $returnStmt = mysqli_prepare($conn, "INSERT INTO rental_returns (rental_id, return_date, status) VALUES (?, CURDATE(), 'completed')");
    mysqli_stmt_bind_param($returnStmt, 'i', $rentalId);
}
$returnOk = mysqli_stmt_execute($returnStmt);
mysqli_stmt_close($returnStmt);

// Increment product stock after successful return
$stockIncStmt = mysqli_prepare($conn, "UPDATE products SET stock = stock + 1 WHERE id = (SELECT product_id FROM rentals WHERE id = ?) && stock >= 0");
mysqli_stmt_bind_param($stockIncStmt, 'i', $rentalId);
mysqli_stmt_execute($stockIncStmt);
mysqli_stmt_close($stockIncStmt);

// Update rental status to completed
$rentalStmt = mysqli_prepare($conn, "UPDATE rentals SET status = 'completed' WHERE id = ?");
mysqli_stmt_bind_param($rentalStmt, 'i', $rentalId);
$rentalOk = mysqli_stmt_execute($rentalStmt);
mysqli_stmt_close($rentalStmt);

if ($returnOk && $rentalOk) {
    mysqli_commit($conn);
    set_flash('success', 'Pengembalian berhasil diselesaikan.');
} else {
    mysqli_rollback($conn);
    set_flash('error', 'Gagal menyelesaikan pengembalian.');
}

redirect_route('toko.returns');
