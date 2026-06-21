<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_csrf();

$cartId = (int) ($_POST['cart_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$userId = $_SESSION['user_id'];

if ($cartId > 0) {
    $stockStmt = mysqli_prepare($conn, "
        SELECT p.stock FROM carts c
        INNER JOIN products p ON p.id = c.product_id
        WHERE c.id = ? AND c.user_id = ?
    ");
    mysqli_stmt_bind_param($stockStmt, 'ii', $cartId, $userId);
    mysqli_stmt_execute($stockStmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stockStmt));
    mysqli_stmt_close($stockStmt);

    if ($product) {
        $maxStock = (int) $product['stock'];
        $finalQty = min($quantity, $maxStock);

        $stmt = mysqli_prepare($conn, "UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'iii', $finalQty, $cartId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

redirect_route('cart');
