<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$activeTab = $_GET['tab'] ?? 'biodata';
$isSecurityTab = $activeTab === 'security';

$profile = [
    'name' => $_SESSION['name'] ?? 'Username',
    'username' => $_SESSION['username'] ?? 'username',
    'email' => $_SESSION['email'] ?? 'user@email.com',
    'birth_date' => '69-69-6969',
    'gender' => 'Laki-laki',
    'address' => '58W7+V7F, Jl. Nasional III, Depok, Ambarketawang, Kec. Gamping, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55294',
    'phone' => '85712358440',
    'verification' => 'Belum Terverifikasi',
];
?>

<main class="profile-page container">
    <header class="profile-header">
        <span class="profile-small-icon"></span>
        <h1><?= htmlspecialchars($profile['username']); ?></h1>
    </header>

    <section class="profile-panel">
        <nav class="profile-tabs" aria-label="Menu profil">
            <a class="<?= !$isSecurityTab ? 'active' : ''; ?>" href="<?= route('profile'); ?>">Biodata Diri</a>
            <a class="<?= $isSecurityTab ? 'active' : ''; ?>" href="<?= route('profile', ['tab' => 'security']); ?>">Keamanan</a>
        </nav>

        <?php if (!$isSecurityTab): ?>
            <div class="profile-content profile-biodata">
                <aside class="profile-photo-card">
                    <div class="profile-photo-placeholder">
                        <span class="profile-avatar-icon"></span>
                    </div>
                    <button type="button">Pilih Foto</button>
                    <p>Besar file maksimum 10.000.000 bytes (10 Megabytes). Ekstensi file yang diperbolehkan: .JPG .JPEG .PNG</p>
                </aside>

                <section class="profile-details">
                    <h2>Biodata Pengguna</h2>
                    <dl>
                        <div>
                            <dt>Nama</dt>
                            <dd><?= htmlspecialchars($profile['name']); ?></dd>
                        </div>
                        <div>
                            <dt>Tanggal Lahir</dt>
                            <dd><?= htmlspecialchars($profile['birth_date']); ?></dd>
                        </div>
                        <div>
                            <dt>Jenis Kelamin</dt>
                            <dd><?= htmlspecialchars($profile['gender']); ?></dd>
                        </div>
                        <div>
                            <dt>Alamat Utama</dt>
                            <dd><?= htmlspecialchars($profile['address']); ?></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><?= htmlspecialchars($profile['email']); ?></dd>
                        </div>
                        <div>
                            <dt>Nomor HP</dt>
                            <dd><?= htmlspecialchars($profile['phone']); ?></dd>
                        </div>
                        <div>
                            <dt>Status Verifikasi</dt>
                            <dd><?= htmlspecialchars($profile['verification']); ?></dd>
                        </div>
                    </dl>
                    <button class="profile-outline-button" type="button">Ubah Biodata</button>
                </section>
            </div>
        <?php else: ?>
            <div class="profile-content profile-security">
                <section class="profile-security-card">
                    <h2>Keamanan Akun</h2>
                    <dl>
                        <div>
                            <dt>Email</dt>
                            <dd><?= htmlspecialchars($profile['email']); ?></dd>
                            <span>terverifikasi</span>
                            <a href="#">ubah</a>
                        </div>
                        <div>
                            <dt>Nomor HP</dt>
                            <dd><?= htmlspecialchars($profile['phone']); ?></dd>
                            <span>terverifikasi</span>
                            <a href="#">ubah</a>
                        </div>
                        <div>
                            <dt>Password</dt>
                            <dd>********</dd>
                            <span></span>
                            <a href="#">ubah</a>
                        </div>
                        <div>
                            <dt>Verifikasi Identitas</dt>
                            <dd>Belum</dd>
                            <span></span>
                            <a href="#">ubah</a>
                        </div>
                    </dl>
                    <div class="profile-logout-box">
                        <p>Keluar dari Akun</p>
                        <a href="<?= route('logout'); ?>">Logout</a>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
