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
    UPDATE rentals r
    INNER JOIN stores s ON s.id = r.store_id
    SET r.status = 'rented'
    WHERE r.id = ? AND s.user_id = ? AND r.status = 'return_requested'
");
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $existingStmt = mysqli_prepare($conn, "SELECT id FROM rental_returns WHERE rental_id = ? ORDER BY id DESC LIMIT 1");
    $existingReturnId = 0;
    mysqli_stmt_bind_param($existingStmt, 'i', $rentalId);
    mysqli_stmt_execute($existingStmt);
    mysqli_stmt_bind_result($existingStmt, $existingReturnId);
    mysqli_stmt_fetch($existingStmt);
    mysqli_stmt_close($existingStmt);

    if ($existingReturnId > 0) {
        $returnStmt = mysqli_prepare($conn, "UPDATE rental_returns SET return_date = CURDATE(), status = 'problem' WHERE id = ?");
        mysqli_stmt_bind_param($returnStmt, 'i', $existingReturnId);
    } else {
        $returnStmt = mysqli_prepare($conn, "INSERT INTO rental_returns (rental_id, return_date, status) VALUES (?, CURDATE(), 'problem')");
        mysqli_stmt_bind_param($returnStmt, 'i', $rentalId);
    }
    mysqli_stmt_execute($returnStmt);
    mysqli_stmt_close($returnStmt);
    set_flash('success', 'Pengembalian ditolak dan rental dikembalikan ke status sedang disewa.');
} else {
    set_flash('error', 'Pengembalian tidak dapat ditolak atau bukan milik toko kamu.');
}
mysqli_stmt_close($stmt);

redirect_route('toko.returns');
