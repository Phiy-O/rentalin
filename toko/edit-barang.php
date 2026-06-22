<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    set_flash('error', 'Barang tidak valid.');
    redirect_route('toko.products');
}

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$store = mysqli_fetch_assoc(mysqli_stmt_get_result($storeStmt));
mysqli_stmt_close($storeStmt);

if (!$store) {
    set_flash('error', 'Kamu belum memiliki toko. Silakan buat toko terlebih dahulu.');
    redirect_route('toko.create');
}

$storeId = (int) $store['id'];

$productStmt = mysqli_prepare($conn, "
    SELECT id, category_id, name, description, price_per_day, stock, condition_status, status
    FROM products
    WHERE id = ? AND store_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($productStmt, 'ii', $productId, $storeId);
mysqli_stmt_execute($productStmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($productStmt));
mysqli_stmt_close($productStmt);

if (!$product) {
    set_flash('error', 'Barang tidak ditemukan atau bukan milik toko kamu.');
    redirect_route('toko.products');
}

$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    $categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
}

$imagesStmt = mysqli_prepare($conn, "SELECT image, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
mysqli_stmt_bind_param($imagesStmt, 'i', $productId);
mysqli_stmt_execute($imagesStmt);
$images = mysqli_fetch_all(mysqli_stmt_get_result($imagesStmt), MYSQLI_ASSOC);
mysqli_stmt_close($imagesStmt);

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$form = array_merge($product, $old);
$priceValue = isset($old['price_per_day'])
    ? $old['price_per_day']
    : number_format((float) $product['price_per_day'], 0, '', '');
$activeMenu = 'products';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content product-create-page">
            <?php show_flash(); ?>

            <header class="dashboard-header product-create-header">
                <div>
                    <span class="product-create-eyebrow">Edit Barang</span>
                    <h1><?= htmlspecialchars($product['name']); ?></h1>
                    <p>Perbarui informasi barang rental yang tampil di katalog Rentalin.</p>
                </div>
                <div class="owner-product-actions">
                    <a href="<?= route('toko.products.detail', ['id' => $product['id']]); ?>" class="btn btn-outline btn-small">Lihat Detail</a>
                    <a href="<?= route('toko.products'); ?>" class="btn btn-outline btn-small">Kembali</a>
                </div>
            </header>

            <form method="POST" action="<?= route('toko.products.update'); ?>" enctype="multipart/form-data" class="product-create-form">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= $product['id']; ?>">

                <div class="product-create-grid">
                    <div class="product-create-main">
                        <section class="product-form-card">
                            <div class="product-form-card-head">
                                <h2>Informasi Barang</h2>
                                <p>Pastikan nama, kategori, dan kondisi barang tetap akurat.</p>
                            </div>

                            <div class="form-group">
                                <label for="name">Nama Barang <span class="required">*</span></label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($form['name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category_id">Kategori <span class="required">*</span></label>
                                    <select id="category_id" name="category_id" required>
                                        <option value="">Pilih kategori...</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>" <?= (int) ($form['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="condition_status">Kondisi Barang</label>
                                    <input type="text" id="condition_status" name="condition_status" value="<?= htmlspecialchars($form['condition_status'] ?? ''); ?>" placeholder="Contoh: Sangat Baik, Baru, Bekas">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Deskripsi Barang</label>
                                <textarea id="description" name="description" rows="5" placeholder="Jelaskan spesifikasi, kelengkapan, kondisi barang, dan catatan rental penting..."><?= htmlspecialchars($form['description'] ?? ''); ?></textarea>
                            </div>
                        </section>

                        <section class="product-form-card">
                            <div class="product-form-card-head">
                                <h2>Harga & Ketersediaan</h2>
                                <p>Perbarui harga harian, stok unit, dan status barang.</p>
                            </div>

                            <div class="form-row product-price-row">
                                <div class="form-group">
                                    <label for="price_per_day">Harga Sewa per Hari <span class="required">*</span></label>
                                    <div class="price-input-wrap">
                                        <span>Rp</span>
                                        <input type="text" id="price_per_day" name="price_per_day" value="<?= htmlspecialchars($priceValue); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="stock">Stok</label>
                                    <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($form['stock'] ?? '1'); ?>" min="0">
                                </div>

                                <div class="form-group">
                                    <label for="status">Status Barang</label>
                                    <select id="status" name="status">
                                        <option value="available" <?= ($form['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                                        <option value="unavailable" <?= ($form['status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="product-create-side">
                        <section class="product-form-card product-upload-card">
                            <div class="product-form-card-head">
                                <h2>Foto Barang</h2>
                                <p>Foto lama tetap tersimpan. Upload foto baru bila ingin menambah galeri.</p>
                            </div>

                            <?php if (!empty($images)): ?>
                                <div class="edit-current-images">
                                    <?php foreach ($images as $image): ?>
                                        <div class="edit-current-image <?= (int) $image['is_primary'] === 1 ? 'is-primary' : ''; ?>">
                                            <?php render_product_image($image['image'], $product['name']); ?>
                                            <?php if ((int) $image['is_primary'] === 1): ?><span>Sampul</span><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-images-upload">
                                <div class="upload-grid product-upload-grid" id="upload-grid">
                                    <div class="upload-slot upload-trigger" id="upload-trigger">
                                        <span class="upload-plus"></span>
                                        <span class="upload-label">Tambah Foto</span>
                                    </div>
                                </div>
                                <input type="file" name="images[]" id="images-input" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none;">
                                <span class="form-hint">Maksimal total 5 foto. Foto baru tidak menghapus foto lama.</span>
                            </div>
                        </section>

                        <section class="product-create-tips">
                            <h3>Catatan edit</h3>
                            <p>Perubahan harga dan stok langsung mempengaruhi tampilan barang di katalog.</p>
                        </section>
                    </aside>
                </div>

                <div class="store-form-actions product-create-actions">
                    <a href="<?= route('toko.products.detail', ['id' => $product['id']]); ?>" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('images-input');
    const uploadGrid = document.getElementById('upload-grid');
    const uploadTrigger = document.getElementById('upload-trigger');
    const MAX_FILES = 5;
    let files = [];

    uploadTrigger.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        files = files.concat(Array.from(this.files)).slice(0, MAX_FILES);
        renderPreviews();
    });

    function renderPreviews() {
        document.querySelectorAll('.upload-slot:not(.upload-trigger)').forEach(function(el) {
            el.remove();
        });

        files.forEach(function(file, index) {
            var reader = new FileReader();
            var slot = document.createElement('div');
            slot.className = 'upload-slot upload-preview';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'upload-remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function() {
                files.splice(index, 1);
                renderPreviews();
            });

            slot.appendChild(removeBtn);
            uploadGrid.insertBefore(slot, uploadTrigger);

            reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview';
                slot.appendChild(img);
            };
            reader.readAsDataURL(file);
        });

        var dataTransfer = new DataTransfer();
        files.forEach(function(f) { dataTransfer.items.add(f); });
        fileInput.files = dataTransfer.files;
        uploadTrigger.style.display = files.length >= MAX_FILES ? 'none' : 'flex';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
