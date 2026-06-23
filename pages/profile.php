<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$activeTab = $_GET['tab'] ?? 'biodata';
$isSecurityTab = $activeTab === 'security';
$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT name, username, email, phone, address, profile_image, role, created_at FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    redirect_route('logout');
}

$profileName = $user['name'] ?: $user['username'];
$initial = strtoupper(mb_substr($profileName, 0, 1));
$joinedDate = !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-';
$phone = $user['phone'] ?: 'Belum ditambahkan';
$address = $user['address'] ?: 'Belum ditambahkan';
$avatarUrl = '';

if (!empty($user['profile_image'])) {
    $avatarPath = UPLOAD_PROFILES_PATH . basename($user['profile_image']);
    if (file_exists($avatarPath)) {
        $avatarUrl = UPLOAD_PROFILES_URL . rawurlencode(basename($user['profile_image']));
    }
}
?>

<main class="profile-page container">
    <?php show_flash(); ?>

    <section class="profile-hero">
        <div class="profile-hero-main">
            <div class="profile-avatar-large">
                <?php if ($avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl); ?>" alt="Foto profil <?= htmlspecialchars($profileName); ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars($initial); ?></span>
                <?php endif; ?>
            </div>
            <div>
                <p class="profile-eyebrow">Akun Rentalin</p>
                <h1><?= htmlspecialchars($profileName); ?></h1>
                <p class="profile-username">@<?= htmlspecialchars($user['username']); ?></p>
            </div>
        </div>

        <div class="profile-hero-meta">
            <div>
                <span>Status</span>
                <strong><?= htmlspecialchars(ucfirst($user['role'])); ?></strong>
            </div>
            <div>
                <span>Bergabung</span>
                <strong><?= htmlspecialchars($joinedDate); ?></strong>
            </div>
        </div>
    </section>

    <section class="profile-panel">
        <nav class="profile-tabs" aria-label="Menu profil">
            <a class="<?= !$isSecurityTab ? 'active' : ''; ?>" href="<?= route('profile'); ?>">Biodata Diri</a>
            <a class="<?= $isSecurityTab ? 'active' : ''; ?>" href="<?= route('profile', ['tab' => 'security']); ?>">Keamanan</a>
        </nav>

        <?php if (!$isSecurityTab): ?>
            <form class="profile-content profile-biodata profile-edit-form" method="POST" action="<?= route('profile.update'); ?>" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <aside class="profile-photo-card">
                    <div class="profile-photo-preview">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= htmlspecialchars($avatarUrl); ?>" alt="Foto profil <?= htmlspecialchars($profileName); ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($initial); ?></span>
                        <?php endif; ?>
                    </div>
                    <label class="profile-upload-button" for="profile_image">Upload Foto</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp" class="profile-file-input">
                    <p>Format JPG, PNG, atau WebP. Maksimal 5MB. Foto akan dikompres otomatis agar ringan saat ditampilkan.</p>
                </aside>

                <section class="profile-details-card">
                    <div class="profile-section-head">
                        <span>Informasi Akun</span>
                        <h2>Biodata Pengguna</h2>
                        <p>Informasi dasar akun yang digunakan untuk aktivitas rental di Rentalin.</p>
                    </div>

                    <div class="profile-form-grid">
                        <div class="profile-form-group">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="profile-form-group profile-form-wide">
                            <label for="address">Alamat Utama</label>
                            <textarea id="address" name="address" rows="4" placeholder="Masukkan alamat utama kamu..."><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="profile-note-card">
                        <span class="profile-note-dot"></span>
                        <p>Email, nomor HP, dan password dipindahkan ke tab Keamanan karena termasuk informasi sensitif akun.</p>
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </section>
            </form>
        <?php else: ?>
            <div class="profile-content profile-security">
                <section class="profile-details-card profile-security-card profile-security-summary">
                    <div class="profile-section-head">
                        <span>Keamanan</span>
                        <h2>Keamanan Akun</h2>
                        <p>Kelola informasi sensitif akun. Setiap perubahan wajib dikonfirmasi dengan password saat ini.</p>
                    </div>

                    <div class="profile-security-list">
                        <div class="profile-security-row">
                            <div>
                                <strong>Email</strong>
                                <span><?= htmlspecialchars($user['email']); ?></span>
                            </div>
                            <em class="security-pill verified">Aktif</em>
                        </div>
                        <div class="profile-security-row">
                            <div>
                                <strong>Nomor HP</strong>
                                <span><?= htmlspecialchars($phone); ?></span>
                            </div>
                            <em class="security-pill <?= $user['phone'] ? 'verified' : 'warning'; ?>"><?= $user['phone'] ? 'Terisi' : 'Belum ada'; ?></em>
                        </div>
                        <div class="profile-security-row">
                            <div>
                                <strong>Password</strong>
                                <span>********</span>
                            </div>
                            <em class="security-pill neutral">Tersimpan</em>
                        </div>
                    </div>
                </section>

                <section class="profile-details-card profile-security-card">
                    <div class="profile-section-head compact">
                        <span>Email</span>
                        <h2>Ganti Email</h2>
                        <p>Email baru langsung aktif. Verifikasi email bisa ditambahkan saat layanan email tersedia.</p>
                    </div>

                    <form class="profile-security-form" method="POST" action="<?= route('profile.security.email'); ?>">
                        <?php csrf_field(); ?>
                        <div class="profile-form-grid">
                            <div class="profile-form-group">
                                <label for="email">Email Baru</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="profile-form-group">
                                <label for="email_password">Password Saat Ini</label>
                                <input type="password" id="email_password" name="current_password" placeholder="Konfirmasi password" required>
                            </div>
                        </div>
                        <div class="profile-form-actions compact">
                            <button type="submit" class="btn btn-primary">Simpan Email</button>
                        </div>
                    </form>
                </section>

                <section class="profile-details-card profile-security-card">
                    <div class="profile-section-head compact">
                        <span>Nomor HP</span>
                        <h2>Ganti Nomor HP</h2>
                        <p>Nomor HP digunakan untuk komunikasi rental dan toko.</p>
                    </div>

                    <form class="profile-security-form" method="POST" action="<?= route('profile.security.phone'); ?>">
                        <?php csrf_field(); ?>
                        <div class="profile-form-grid">
                            <div class="profile-form-group">
                                <label for="phone">Nomor HP Baru</label>
                                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Contoh: 081234567890" required>
                            </div>
                            <div class="profile-form-group">
                                <label for="phone_password">Password Saat Ini</label>
                                <input type="password" id="phone_password" name="current_password" placeholder="Konfirmasi password" required>
                            </div>
                        </div>
                        <div class="profile-form-actions compact">
                            <button type="submit" class="btn btn-primary">Simpan Nomor HP</button>
                        </div>
                    </form>
                </section>

                <section class="profile-details-card profile-security-card">
                    <div class="profile-section-head compact">
                        <span>Password</span>
                        <h2>Ganti Password</h2>
                        <p>Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.</p>
                    </div>

                    <form class="profile-security-form" method="POST" action="<?= route('profile.security.password'); ?>">
                        <?php csrf_field(); ?>
                        <div class="profile-form-grid">
                            <div class="profile-form-group">
                                <label for="current_password">Password Saat Ini</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="profile-form-group">
                                <label for="new_password">Password Baru</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="profile-form-group profile-form-wide">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="profile-form-actions compact">
                            <button type="submit" class="btn btn-primary">Ganti Password</button>
                        </div>
                    </form>

                    <div class="profile-logout-box">
                        <div>
                            <strong>Keluar dari akun</strong>
                            <p>Akhiri sesi login kamu di perangkat ini.</p>
                        </div>
                        <a href="<?= route('logout'); ?>">Logout</a>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('profile_image');
    var preview = document.querySelector('.profile-photo-preview');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (event) {
            preview.innerHTML = '';
            var img = document.createElement('img');
            img.src = event.target.result;
            img.alt = 'Preview foto profil';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
