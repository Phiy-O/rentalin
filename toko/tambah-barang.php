<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$storeResult = mysqli_stmt_get_result($storeStmt);
$store = mysqli_fetch_assoc($storeResult);

if (!$store) {
    set_flash('error', 'Kamu belum memiliki toko. Silakan buat toko terlebih dahulu.');
    redirect_route('toko.create');
}

$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    $categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
}

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$activeMenu = 'product-create';
?>

<main class="create-store-page container">
    <header class="create-store-header">
        <h1>Tambah Barang</h1>
        <p>Tambahkan barang rental baru untuk toko kamu.</p>
    </header>

    <div class="create-store-panel">
        <?php show_flash(); ?>

        <form method="POST" action="<?= route('toko.products.store'); ?>" enctype="multipart/form-data" class="store-form">
            <?= csrf_field(); ?>
            <div class="store-form-single">
                <div class="form-group">
                    <label for="name">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? ''); ?>" placeholder="Contoh: Kamera Canon EOS 700D" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Kategori <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Pilih kategori...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= ($old['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="condition_status">Kondisi Barang</label>
                        <input type="text" id="condition_status" name="condition_status" value="<?= htmlspecialchars($old['condition_status'] ?? ''); ?>" placeholder="Contoh: Sangat Baik, Baru, Bekas">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi Barang</label>
                    <textarea id="description" name="description" rows="4" placeholder="Jelaskan spesifikasi, kelengkapan, dan kondisi barang..."><?= htmlspecialchars($old['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_day">Harga Sewa per Hari <span class="required">*</span></label>
                        <input type="text" id="price_per_day" name="price_per_day" value="<?= htmlspecialchars($old['price_per_day'] ?? ''); ?>" placeholder="Contoh: 150000" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok</label>
                        <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($old['stock'] ?? '1'); ?>" min="0" placeholder="Jumlah barang">
                    </div>

                    <div class="form-group">
                        <label for="status">Status Barang</label>
                        <select id="status" name="status">
                            <option value="available" <?= ($old['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="unavailable" <?= ($old['status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Foto Barang</label>
                    <div class="product-images-upload">
                        <div class="upload-grid" id="upload-grid">
                            <div class="upload-slot upload-trigger" id="upload-trigger">
                                <span class="upload-plus"></span>
                                <span class="upload-label">Tambah Foto</span>
                            </div>
                        </div>
                        <input type="file" name="images[]" id="images-input" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none;">
                        <span class="form-hint">Format: JPG, PNG, WebP, SVG. Maksimal 5 foto. Foto pertama akan menjadi sampul.</span>
                    </div>
                </div>
            </div>

            <div class="store-form-actions">
                <a href="<?= route('toko.products'); ?>" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
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
        const newFiles = Array.from(this.files);
        files = files.concat(newFiles);
        if (files.length > MAX_FILES) {
            files = files.slice(0, MAX_FILES);
        }
        renderPreviews();
        this.value = '';
    });

    function renderPreviews() {
        document.querySelectorAll('.upload-slot:not(.upload-trigger)').forEach(function(el) {
            el.remove();
        });

        files.forEach(function(file, index) {
            var reader = new FileReader();
            var slot = document.createElement('div');
            slot.className = 'upload-slot upload-preview';
            slot.dataset.index = index;

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

        if (files.length >= MAX_FILES) {
            uploadTrigger.style.display = 'none';
        } else {
            uploadTrigger.style.display = 'flex';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
