<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_csrf_get();

$rentalId = (int) ($_GET['id'] ?? 0);

if ($rentalId <= 0) {
    set_flash('error', 'ID pesanan tidak valid.');
    redirect_route('toko.dashboard');
}

$stmt = mysqli_prepare($conn, "UPDATE rentals SET status = 'rejected' WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $rentalId);

if (mysqli_stmt_execute($stmt)) {
    set_flash('success', 'Pesanan berhasil ditolak.');
} else {
    set_flash('error', 'Gagal menolak pesanan.');
}

redirect_route('toko.dashboard');
