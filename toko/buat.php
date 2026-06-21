<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$currentStep = $_GET['step'] ?? '1';

$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    $categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'step1') {
    require_csrf();
    $errors = [];

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $googleMaps = trim($_POST['google_maps'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $selectedCategories = $_POST['categories'] ?? [];
    if (is_string($selectedCategories)) {
        $selectedCategories = array_filter(explode(',', $selectedCategories), function($v) {
            return $v !== '';
        });
    }

    if ($name === '') $errors[] = 'Nama toko wajib diisi.';
    if ($slug === '') $errors[] = 'Username toko wajib diisi.';
    if ($address === '') $errors[] = 'Alamat wajib diisi.';
    if ($city === '') $errors[] = 'Kota/Kabupaten wajib diisi.';
    if ($province === '') $errors[] = 'Provinsi wajib diisi.';
    if ($phone === '') $errors[] = 'Nomor HP wajib diisi.';
    if ($email === '') $errors[] = 'Email toko wajib diisi.';

    $slugClean = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($slug)));
    if ($slugClean !== $slug) {
        $errors[] = 'Username toko hanya boleh huruf kecil, angka, dan tanda strip.';
    }

    $slugCheck = mysqli_prepare($conn, "SELECT id FROM stores WHERE slug = ?");
    mysqli_stmt_bind_param($slugCheck, 's', $slugClean);
    mysqli_stmt_execute($slugCheck);
    mysqli_stmt_store_result($slugCheck);
    if (mysqli_stmt_num_rows($slugCheck) > 0) {
        $errors[] = 'Username toko sudah digunakan.';
    }
    mysqli_stmt_close($slugCheck);

    $logoFilename = '';
    if (!empty($_FILES['logo']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'Logo harus berformat JPG, PNG, WebP, atau SVG.';
        } elseif ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengupload logo.';
        } else {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logoFilename = 'logo_' . $slugClean . '_' . time() . '.' . $ext;
            $tempPath = dirname(__DIR__) . '/uploads/temp/' . $logoFilename;
            if (!is_dir(dirname(__DIR__) . '/uploads/temp/')) {
                mkdir(dirname(__DIR__) . '/uploads/temp/', 0777, true);
            }
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $tempPath)) {
                $errors[] = 'Gagal menyimpan logo.';
            }
        }
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
        $_SESSION['store_form'] = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'categories' => $selectedCategories,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'google_maps' => $googleMaps,
            'phone' => $phone,
            'email' => $email,
        ];
        if ($logoFilename) {
            $_SESSION['store_form']['logo_temp'] = $logoFilename;
        }
        redirect_route('toko.create');
    }

    $_SESSION['store_form'] = [
        'name' => $name,
        'slug' => $slugClean,
        'description' => $description,
        'categories' => $selectedCategories,
        'address' => $address,
        'city' => $city,
        'province' => $province,
        'google_maps' => $googleMaps,
        'phone' => $phone,
        'email' => $email,
    ];
    if ($logoFilename) {
        $_SESSION['store_form']['logo_temp'] = $logoFilename;
    }

    set_flash('success', 'Lanjutkan mengisi aturan rental.');
    redirect_route('toko.create', ['step' => '2']);
}

$formData = $_SESSION['store_form'] ?? [];

if (isset($formData['categories']) && is_string($formData['categories'])) {
    $formData['categories'] = array_filter(explode(',', $formData['categories']), function($v) {
        return $v !== '';
    });
}
?>

<main class="create-store-page container">
    <header class="create-store-header">
        <h1>Buat Toko</h1>
        <p>Isi data toko rental kamu untuk mulai menyewakan barang.</p>
    </header>

    <div class="create-store-panel">
        <?php show_flash(); ?>

        <?php if ($currentStep === '1'): ?>

        <div class="step-label">Step 1 dari 2 — Informasi Toko</div>

        <form method="POST" action="<?= route('toko.create'); ?>" enctype="multipart/form-data" class="store-form">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="step1">

            <div class="store-form-grid">
                <div class="store-form-left">
                    <h2>Informasi Toko</h2>

                    <div class="form-group">
                        <label for="name">Nama Toko <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? ''); ?>" placeholder="Contoh: Renty Camera" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">Username Toko <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($formData['slug'] ?? ''); ?>" placeholder="Contoh: renty-camera" required>
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
                        <input type="hidden" name="categories" id="categories-input" value="<?= htmlspecialchars(implode(',', $formData['categories'] ?? [])); ?>">
                        <span class="form-hint">Pilih kategori barang yang akan kamu sewakan.</span>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi Toko</label>
                        <textarea id="description" name="description" rows="5" placeholder="Jelaskan tentang toko rental kamu..."><?= htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Logo Toko</label>
                        <div class="logo-upload-area">
                            <div class="logo-upload-placeholder" id="logo-preview">
                                <span class="logo-upload-icon"></span>
                                <p>Klik untuk upload logo</p>
                                <span class="logo-upload-hint">JPG, PNG, WebP, SVG. Max 2 MB.</span>
                            </div>
                            <input type="file" name="logo" id="logo-input" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none;">
                        </div>
                    </div>
                </div>

                <div class="store-form-right">
                    <h2>Lokasi Pengambilan</h2>

                    <div class="form-group">
                        <label for="address">Alamat Lengkap <span class="required">*</span></label>
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
                        <input type="url" id="google_maps" name="google_maps" value="<?= htmlspecialchars($formData['google_maps'] ?? ''); ?>" placeholder="https://maps.google.com/?q=...">
                        <span class="form-hint">Tempel link lokasi toko dari Google Maps.</span>
                    </div>

                    <h2 class="contact-heading">Kontak Toko</h2>

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

            <div class="store-form-actions">
                <a href="<?= route('catalog'); ?>" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Berikutnya</button>
            </div>
        </form>

        <?php else: ?>

        <div class="step-label">Step 2 dari 2 — Aturan Rental</div>

        <form method="POST" action="<?= route('toko.create.store'); ?>" class="store-form">
            <?= csrf_field(); ?>
            <div class="store-form-single">
                <h2>Aturan Rental</h2>

                <div class="form-group">
                    <label>Jam Operasional</label>
                    <div class="time-range">
                        <input type="time" id="open_time" name="open_time" value="<?= htmlspecialchars($formData['open_time'] ?? '08:00'); ?>">
                        <span class="time-separator">—</span>
                        <input type="time" id="close_time" name="close_time" value="<?= htmlspecialchars($formData['close_time'] ?? '21:00'); ?>">
                    </div>
                    <span class="form-hint">Jam buka dan tutup toko untuk pengambilan barang.</span>
                </div>

                <div class="form-group">
                    <label for="rental_terms">Syarat Rental</label>
                    <textarea id="rental_terms" name="rental_terms" rows="4" placeholder="Contoh: Wajib membawa identitas, wajib membayar deposit, barang harus dikembalikan tepat waktu."><?= htmlspecialchars($formData['rental_terms'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="deposit_policy">Kebijakan Deposit</label>
                    <textarea id="deposit_policy" name="deposit_policy" rows="4" placeholder="Contoh: Deposit dikembalikan setelah barang kembali dalam kondisi baik."><?= htmlspecialchars($formData['deposit_policy'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="fine_policy">Kebijakan Denda</label>
                    <textarea id="fine_policy" name="fine_policy" rows="4" placeholder="Contoh: Denda keterlambatan Rp50.000 per hari atau potongan deposit jika barang rusak."><?= htmlspecialchars($formData['fine_policy'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="store-form-actions">
                <a href="<?= route('toko.create'); ?>" class="btn btn-outline">Kembali</a>
                <button type="submit" class="btn btn-primary">Buat Toko</button>
            </div>
        </form>

        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('category-select');
    const addBtn = document.getElementById('add-category-btn');
    const chipsContainer = document.getElementById('category-chips');
    const categoriesInput = document.getElementById('categories-input');

    let selectedCategories = [];

    <?php $formCats = $formData['categories'] ?? []; ?>
    <?php if (!empty($formCats)): ?>
    selectedCategories = <?= json_encode(array_map('intval', $formCats)); ?>;
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

    addBtn.addEventListener('click', function() {
        var val = parseInt(catSelect.value);
        if (!val) return;
        if (selectedCategories.indexOf(val) !== -1) return;
        selectedCategories.push(val);
        renderChips();
        catSelect.value = '';
    });

    catSelect.addEventListener('change', function() {
        var val = parseInt(this.value);
        if (!val) return;
        if (selectedCategories.indexOf(val) !== -1) { this.value = ''; return; }
        selectedCategories.push(val);
        renderChips();
        this.value = '';
    });

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
