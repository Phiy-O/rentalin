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

// Rental can be cancelled only if pending (user cancellation) or return_requested (cancel return)
$stmt = mysqli_prepare($conn, "
    SELECT id, status FROM rentals WHERE id = ? AND user_id = ? AND status IN ('pending', 'return_requested')
");
mysqli_stmt_bind_param($stmt, 'ii', $rentalId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$rental = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$rental) {
    set_flash('error', 'Rental tidak dapat dibatalkan atau bukan milik kamu.');
    redirect_route('rental.returns');
}

$newStatus = ($rental['status'] === 'pending') ? 'cancelled' : 'rented';
$updateStmt = mysqli_prepare($conn, "UPDATE rentals SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($updateStmt, 'si', $newStatus, $rentalId);
$ok = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if ($ok) {
    $msg = ($newStatus === 'cancelled') ? 'Rental berhasil dibatalkan.' : 'Pengembalian berhasil dibatalkan, rental kembali aktif.';
    set_flash('success', $msg);
} else {
    set_flash('error', 'Gagal membatalkan rental.');
}

redirect_route('rental.returns');
?>