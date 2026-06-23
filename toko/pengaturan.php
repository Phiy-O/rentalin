<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/image-helper.php';
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

$storeCatIds = [];
$scResult = mysqli_query($conn, "SELECT category_id FROM store_categories WHERE store_id = " . (int) $store['id']);
if ($scResult) {
    while ($row = mysqli_fetch_assoc($scResult)) {
        $storeCatIds[] = (int) $row['category_id'];
    }
}

$store['open_time_display'] = $store['open_time'] ? date('H:i', strtotime($store['open_time'])) : '08:00';
$store['close_time_display'] = $store['close_time'] ? date('H:i', strtotime($store['close_time'])) : '21:00';

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$formData = array_merge($store, $old);

$activeMenu = 'settings';
?>

<main class="dashboard-page container">
    <div class="dashboard-layout">
        <?php require_once __DIR__ . '/../includes/sidebar-toko.php'; ?>

        <section class="dashboard-content">
            <?php show_flash(); ?>

            <header class="dashboard-header settings-header">
                <div>
                    <h1>Pengaturan <?= htmlspecialchars($store['name']); ?></h1>
                    <p>Kelola informasi toko, lokasi, kontak, dan aturan rental.</p>
                </div>
                <button type="submit" form="settings-form" class="btn btn-primary">Simpan Perubahan</button>
            </header>

            <form method="POST" action="<?= route('toko.settings.update'); ?>" enctype="multipart/form-data" id="settings-form" class="settings-form">
                <?php csrf_field(); ?>

                <div class="settings-grid">
                    <div class="settings-card">
                        <h2>Informasi Toko</h2>

                        <div class="form-group">
                            <label for="name">Nama Toko <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? ''); ?>" placeholder="Masukkan nama toko rental" required>
                        </div>

                        <div class="form-group">
                            <label for="slug">Username Toko <span class="required">*</span></label>
                            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($formData['slug'] ?? ''); ?>" placeholder="contoh: jogja-camera-rental" required>
                            <span class="form-hint">Huruf kecil, angka, dan tanda strip. Digunakan sebagai identitas URL toko.</span>
                        </div>

                        <div class="form-group">
                            <label>Kategori Rental</label>
                            <div class="category-select-wrapper">
                                <select id="category-select">
                                    <option value="">Pilih kategori...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline btn-small" id="add-category-btn">Tambah</button>
                            </div>
                            <div class="category-chips" id="category-chips"></div>
                            <input type="hidden" name="categories" id="categories-input" value="<?= htmlspecialchars(implode(',', $storeCatIds)); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi Toko</label>
                            <textarea id="description" name="description" rows="5" placeholder="Ceritakan singkat tentang toko rentalmu..."><?= htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Logo Toko</label>
                            <div class="logo-upload-area">
                                <div class="logo-upload-placeholder settings-logo-preview" id="logo-preview">
                                    <?php if (!empty($store['logo'])): ?>
                                        <img src="<?= UPLOAD_LOGO_URL . rawurlencode($store['logo']); ?>" alt="Logo <?= htmlspecialchars($store['name']); ?>" class="logo-preview-img">
                                    <?php else: ?>
                                        <span class="logo-upload-icon"></span>
                                        <p>Klik untuk upload logo</p>
                                        <span class="logo-upload-hint">JPG, PNG, WebP, SVG. Max 2 MB.</span>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="logo" id="logo-input" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none;">
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h2>Lokasi & Kontak</h2>

                        <div class="form-group">
                            <label for="address">Alamat <span class="required">*</span></label>
                            <textarea id="address" name="address" rows="3" placeholder="Contoh: Jl. Mawar No. 123, RT 01 RW 02" required><?= htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Kota/Kabupaten <span class="required">*</span></label>
                                <input type="text" id="city" name="city" value="<?= htmlspecialchars($formData['city'] ?? ''); ?>" placeholder="Contoh: Sleman" required>
                            </div>
                            <div class="form-group">
                                <label for="province">Provinsi <span class="required">*</span></label>
                                <input type="text" id="province" name="province" value="<?= htmlspecialchars($formData['province'] ?? ''); ?>" placeholder="Contoh: DI Yogyakarta" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="google_maps">Link Google Maps</label>
                            <input type="url" id="google_maps" name="google_maps" value="<?= htmlspecialchars($formData['google_maps_link'] ?? ''); ?>" placeholder="https://maps.google.com/?q=...">
                        </div>

                        <div class="form-group">
                            <label for="phone">Nomor HP/WhatsApp <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? ''); ?>" placeholder="Contoh: 081234567890" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Toko <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? ''); ?>" placeholder="Contoh: toko@email.com" required>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <h2>Aturan Rental</h2>

                    <div class="form-group">
                        <label>Jam Operasional</label>
                        <div class="time-range">
                            <input type="time" id="open_time" name="open_time" value="<?= htmlspecialchars($formData['open_time_display'] ?? '08:00'); ?>">
                            <span class="time-separator">—</span>
                            <input type="time" id="close_time" name="close_time" value="<?= htmlspecialchars($formData['close_time_display'] ?? '21:00'); ?>">
                        </div>
                        <span class="form-hint">Jam buka dan tutup toko untuk pengambilan barang.</span>
                    </div>

                    <div class="form-group">
                        <label for="rental_terms">Syarat Rental</label>
                        <textarea id="rental_terms" name="rental_terms" rows="4" placeholder="Contoh: Wajib membawa kartu identitas saat pengambilan barang."><?= htmlspecialchars($formData['rental_terms'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="deposit_policy">Kebijakan Deposit</label>
                        <textarea id="deposit_policy" name="deposit_policy" rows="4" placeholder="Contoh: Deposit dikembalikan setelah barang kembali dalam kondisi baik."><?= htmlspecialchars($formData['deposit_policy'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fine_policy">Kebijakan Denda</label>
                        <textarea id="fine_policy" name="fine_policy" rows="4" placeholder="Contoh: Denda Rp20.000/hari jika terlambat mengembalikan barang."><?= htmlspecialchars($formData['fine_policy'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="settings-card settings-status-card">
                    <div class="status-card-row">
                        <div>
                            <h2>Status Toko</h2>
                            <div class="status-indicator">
                                <?php if ($store['status'] === 'active'): ?>
                                    <span class="status-badge status-available">Aktif</span>
                                <?php elseif ($store['status'] === 'inactive'): ?>
                                    <span class="status-badge status-unavailable">Nonaktif</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="status-action">
                            <?php if ($store['status'] === 'active'): ?>
                                <button type="submit" form="deactivate-form" class="btn btn-outline btn-small" style="color:#991b1b;border-color:#fca5a5;">Nonaktifkan Toko</button>
                            <?php else: ?>
                                <button type="submit" form="activate-form" class="btn btn-outline btn-small" style="color:#166534;border-color:#86efac;">Aktifkan Toko</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($store['status'] === 'active'): ?>
                        <p class="status-warning">Jika toko dinonaktifkan, barang rental tidak akan tampil di katalog.</p>
                    <?php endif; ?>
                </div>

                <div class="settings-form-actions">
                    <a href="<?= route('toko.dashboard'); ?>" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>

            <?php if ($store['status'] === 'active'): ?>
            <form method="POST" action="<?= route('toko.settings.update'); ?>" id="deactivate-form" style="display:none;">
                <?php csrf_field(); ?>
                <input type="hidden" name="status" value="inactive">
                <?php foreach ($_POST as $key => $value): ?>
                    <?php if (is_string($value) && $key !== 'status'): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key); ?>" value="<?= htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            </form>
            <?php else: ?>
            <form method="POST" action="<?= route('toko.settings.update'); ?>" id="activate-form" style="display:none;">
                <?php csrf_field(); ?>
                <input type="hidden" name="status" value="active">
            </form>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var catSelect = document.getElementById('category-select');
    var addBtn = document.getElementById('add-category-btn');
    var chipsContainer = document.getElementById('category-chips');
    var categoriesInput = document.getElementById('categories-input');

    var selectedCategories = [];

    <?php if (!empty($storeCatIds)): ?>
    selectedCategories = <?= json_encode($storeCatIds); ?>;
    renderChips();
    <?php endif; ?>

    function renderChips() {
        chipsContainer.innerHTML = '';
        categoriesInput.value = selectedCategories.join(',');

        selectedCategories.forEach(function(catId) {
            var option = catSelect.querySelector('option[value="' + catId + '"]');
            if (!option) return;
            var chip = document.createElement('span');
            chip.className = 'category-chip';
            chip.innerHTML = option.text + ' <button type="button" class="chip-remove" data-id="' + catId + '">&times;</button>';
            chipsContainer.appendChild(chip);
        });

        document.querySelectorAll('.chip-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = parseInt(this.getAttribute('data-id'));
                selectedCategories = selectedCategories.filter(function(c) { return c !== id; });
                renderChips();
            });
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var val = parseInt(catSelect.value);
            if (!val) return;
            if (selectedCategories.indexOf(val) !== -1) return;
            selectedCategories.push(val);
            renderChips();
            catSelect.value = '';
        });
    }

    if (catSelect) {
        catSelect.addEventListener('change', function() {
            var val = parseInt(this.value);
            if (!val) return;
            if (selectedCategories.indexOf(val) !== -1) { this.value = ''; return; }
            selectedCategories.push(val);
            renderChips();
            this.value = '';
        });
    }

    var logoInput = document.getElementById('logo-input');
    var logoPreview = document.getElementById('logo-preview');

    if (logoPreview && logoInput) {
        logoPreview.addEventListener('click', function() { logoInput.click(); });
        logoInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Logo preview" class="logo-preview-img">';
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
