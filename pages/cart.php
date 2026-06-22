<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];

$cartQuery = "
    SELECT
        c.id AS cart_id, c.quantity,
        p.id AS product_id, p.name, p.price_per_day, p.stock, p.status,
        pi.image,
        s.name AS store_name
    FROM carts c
    INNER JOIN products p ON p.id = c.product_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    LEFT JOIN stores s ON s.id = p.store_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
";

$stmt = mysqli_prepare($conn, $cartQuery);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$cartItems = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += (float) $item['price_per_day'] * (int) $item['quantity'];
}
?>
<main class="cart-page">
    <div class="container">

        <div class="cart-header">
            <h1>Keranjang Rental</h1>
            <p class="cart-count"><?= count($cartItems); ?> barang</p>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
            <div class="cart-empty-icon"><?php render_icon('shopping-cart', 'icon-xl icon-muted'); ?></div>
            <h3>Keranjang masih kosong</h3>
            <p>Tambahkan barang rental yang kamu butuhkan dari katalog.</p>
            <a href="<?= route('catalog'); ?>" class="btn btn-primary">Jelajahi Katalog</a>
        </div>
        <?php else: ?>

        <div class="cart-layout">

            <div class="cart-items">
                <?php foreach ($cartItems as $item):
                    $available = $item['status'] === 'available' && (int) $item['stock'] > 0;
                    $subtotal = (float) $item['price_per_day'] * (int) $item['quantity'];
                ?>
                <div class="cart-card">
                    <div class="cart-card-img">
                        <?php render_product_image($item['image'], $item['name']); ?>
                    </div>
                    <div class="cart-card-body">
                        <p class="cart-card-store"><?= htmlspecialchars($item['store_name'] ?? ''); ?></p>
                        <h3 class="cart-card-title"><?= htmlspecialchars($item['name']); ?></h3>
                        <p class="cart-card-price">Rp<?= number_format((float) $item['price_per_day'], 0, ',', '.'); ?> /hari</p>
                        <div class="cart-card-actions">
                            <form action="<?= route('cart.update'); ?>" method="POST" class="cart-qty-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                                <div class="cart-qty">
                                    <button type="button" class="cart-qty-btn cart-qty-minus">−</button>
                                    <input type="number" name="quantity" class="cart-qty-input" value="<?= (int) $item['quantity']; ?>" min="1" max="<?= (int) $item['stock']; ?>">
                                    <button type="button" class="cart-qty-btn cart-qty-plus">+</button>
                                </div>
                            </form>
                            <form action="<?= route('cart.remove'); ?>" method="POST" class="cart-remove-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                                <button type="submit" class="cart-remove-btn">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <div class="cart-card-subtotal">
                        <span class="cart-subtotal-label">Subtotal</span>
                        <span class="cart-subtotal-value">Rp<?= number_format($subtotal, 0, ',', '.'); ?></span>
                        <?php if ($available): ?>
                            <a href="<?= route('rental.checkout', ['product_id' => $item['product_id']]); ?>" class="btn btn-primary btn-small">Ajukan Rental</a>
                        <?php else: ?>
                            <span class="cart-unavailable">Tidak tersedia</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <div class="cart-summary-card">
                    <h3>Ringkasan</h3>
                    <div class="cart-summary-row">
                        <span>Total Barang</span>
                        <span><?= count($cartItems); ?> barang</span>
                    </div>
                    <div class="cart-summary-total">
                        <span>Total Harga</span>
                        <strong>Rp<?= number_format($totalPrice, 0, ',', '.'); ?></strong>
                    </div>
                    <?php if (count($cartItems) === 1): ?>
                        <a href="<?= route('rental.checkout', ['product_id' => $cartItems[0]['product_id']]); ?>" class="btn btn-primary btn-full">Ajukan Rental</a>
                    <?php else: ?>
                        <p class="cart-summary-note">Checkout rental diproses per barang. Pilih tombol Ajukan Rental pada item yang ingin disewa.</p>
                    <?php endif; ?>
                    <a href="<?= route('catalog'); ?>" class="btn btn-outline btn-full" style="margin-top:8px;">Tambah Barang</a>
                </div>
            </aside>

        </div>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cart-qty').forEach(function (qty) {
        var input = qty.querySelector('.cart-qty-input');
        var minus = qty.querySelector('.cart-qty-minus');
        var plus = qty.querySelector('.cart-qty-plus');
        var form = qty.closest('.cart-qty-form');
        var min = parseInt(input.getAttribute('min')) || 1;
        var max = parseInt(input.getAttribute('max')) || 999;

        function submitForm() {
            if (form) form.submit();
        }

        if (minus) {
            minus.addEventListener('click', function () {
                var val = parseInt(input.value) || 1;
                if (val > min) input.value = val - 1;
                submitForm();
            });
        }

        if (plus) {
            plus.addEventListener('click', function () {
                var val = parseInt(input.value) || 1;
                if (val < max) input.value = val + 1;
                submitForm();
            });
        }

        if (input) {
            input.addEventListener('change', function () {
                var val = parseInt(this.value) || 1;
                if (val < min) this.value = min;
                if (val > max) this.value = max;
                submitForm();
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
