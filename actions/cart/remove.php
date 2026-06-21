<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_csrf();

$cartId = (int) ($_POST['cart_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($cartId > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM carts WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $cartId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$_SESSION['flash_success'] = 'Barang dihapus dari keranjang.';
redirect_route('cart');
