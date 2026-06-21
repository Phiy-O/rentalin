<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_csrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    $_SESSION['flash_error'] = 'Produk tidak valid.';
    redirect_route('catalog');
}

$stockStmt = mysqli_prepare($conn, "SELECT stock, status FROM products WHERE id = ?");
mysqli_stmt_bind_param($stockStmt, 'i', $productId);
mysqli_stmt_execute($stockStmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stockStmt));
mysqli_stmt_close($stockStmt);

if (!$product || $product['status'] !== 'available' || (int) $product['stock'] <= 0) {
    $_SESSION['flash_error'] = 'Barang tidak tersedia saat ini.';
    redirect_route('product.detail', ['id' => $productId]);
}

$maxStock = (int) $product['stock'];

$check = mysqli_prepare($conn, "SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
mysqli_stmt_bind_param($check, 'ii', $userId, $productId);
mysqli_stmt_execute($check);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
mysqli_stmt_close($check);

$currentCartQty = $existing ? (int) $existing['quantity'] : 0;
$newQty = $currentCartQty + $quantity;

if ($newQty > $maxStock) {
    $newQty = $maxStock;
    if ($currentCartQty >= $maxStock) {
        $_SESSION['flash_error'] = 'Stok barang tidak mencukupi. Keranjang sudah berisi stok maksimal.';
        redirect_route('product.detail', ['id' => $productId]);
    }
    $_SESSION['flash_info'] = 'Stok tersedia hanya ' . $maxStock . ' unit. Jumlah disesuaikan.';
}

if ($existing) {
    $update = mysqli_prepare($conn, "UPDATE carts SET quantity = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, 'ii', $newQty, $existing['id']);
    mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
} else {
    $insert = mysqli_prepare($conn, "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insert, 'iii', $userId, $productId, $newQty);
    mysqli_stmt_execute($insert);
    mysqli_stmt_close($insert);
}

$_SESSION['flash_success'] = 'Barang berhasil ditambahkan ke keranjang.';
redirect_route('product.detail', ['id' => $productId]);
