<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

require_csrf_get();

$rentalId = (int) ($_GET['id'] ?? 0);

if ($rentalId <= 0) {
    set_flash('error', 'ID rental tidak valid.');
    redirect_route('rental.returns');
}

// Ensure the rental belongs to the user and is in a state that can request return
$stmt = mysqli_prepare($conn, "
    SELECT r.id FROM rentals r
    INNER JOIN users u ON u.id = r.user_id
    WHERE r.id = ? AND u.id = ? AND r.status IN ('rented','late')
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$rental = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$rental) {
    set_flash('error', 'Rental tidak dapat diajukan pengembalian atau bukan milik kamu.');
    redirect_route('rental.returns');
}

// Update status to return_requested
$updateStmt = mysqli_prepare($conn, "UPDATE rentals SET status = 'return_requested' WHERE id = ?");
mysqli_stmt_bind_param($updateStmt, 'i', $rentalId);
$ok = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if ($ok) {
    set_flash('success', 'Permintaan pengembalian telah dikirim.');
} else {
    set_flash('error', 'Gagal mengajukan pengembalian.');
}

redirect_route('rental.returns');
?>