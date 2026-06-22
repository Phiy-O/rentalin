<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('toko.settings');
}

require_csrf();

$storeStmt = mysqli_prepare($conn, "SELECT * FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$storeResult = mysqli_stmt_get_result($storeStmt);
$store = mysqli_fetch_assoc($storeResult);

if (!$store) {
    set_flash('error', 'Kamu tidak memiliki toko.');
    redirect_route('toko.create');
}

$storeId = (int) $store['id'];

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$googleMaps = trim($_POST['google_maps'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$openTime = trim($_POST['open_time'] ?? '');
$closeTime = trim($_POST['close_time'] ?? '');
$rentalTerms = trim($_POST['rental_terms'] ?? '');
$depositPolicy = trim($_POST['deposit_policy'] ?? '');
$finePolicy = trim($_POST['fine_policy'] ?? '');
$storeStatus = trim($_POST['status'] ?? 'active');
$selectedCategories = $_POST['categories'] ?? [];
if (is_string($selectedCategories)) {
    $selectedCategories = array_filter(explode(',', $selectedCategories), function($v) {
        return $v !== '';
    });
}

$errors = [];

if ($name === '') $errors[] = 'Nama toko wajib diisi.';
if ($slug === '') $errors[] = 'Username toko wajib diisi.';
if ($address === '') $errors[] = 'Alamat wajib diisi.';
if ($city === '') $errors[] = 'Kota/Kabupaten wajib diisi.';
if ($province === '') $errors[] = 'Provinsi wajib diisi.';

$slugClean = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($slug)));
if ($slugClean !== $slug) {
    $slug = $slugClean;
}

$slugCheck = mysqli_prepare($conn, "SELECT id FROM stores WHERE slug = ? AND id != ?");
mysqli_stmt_bind_param($slugCheck, 'si', $slugClean, $storeId);
mysqli_stmt_execute($slugCheck);
mysqli_stmt_store_result($slugCheck);
if (mysqli_stmt_num_rows($slugCheck) > 0) {
    $errors[] = 'Username toko sudah digunakan toko lain.';
}
mysqli_stmt_close($slugCheck);

$logoFilename = $store['logo'];
if (!empty($_FILES['logo']['name'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
        $errors[] = 'Logo harus berformat JPG, PNG, WebP, atau SVG.';
    } elseif ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengupload logo.';
    } else {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logoFilename = 'logo_' . $slugClean . '_' . time() . '.' . $ext;

        if (!is_dir(UPLOAD_LOGO_PATH)) {
            mkdir(UPLOAD_LOGO_PATH, 0777, true);
        }

        if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_LOGO_PATH . $logoFilename)) {
            if ($store['logo'] && file_exists(UPLOAD_LOGO_PATH . $store['logo'])) {
                @unlink(UPLOAD_LOGO_PATH . $store['logo']);
            }
        } else {
            $errors[] = 'Gagal menyimpan logo.';
            $logoFilename = $store['logo'];
        }
    }
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    $_SESSION['old_input'] = $_POST;
    redirect_route('toko.settings');
}

$updateStmt = mysqli_prepare($conn, "UPDATE stores SET name = ?, slug = ?, description = ?, logo = ?, address = ?, city = ?, province = ?, google_maps_link = ?, phone = ?, email = ?, open_time = ?, close_time = ?, rental_terms = ?, deposit_policy = ?, fine_policy = ?, status = ? WHERE id = ?");

mysqli_stmt_bind_param(
    $updateStmt,
    'ssssssssssssssssi',
    $name, $slug, $description, $logoFilename,
    $address, $city, $province, $googleMaps,
    $phone, $email, $openTime, $closeTime,
    $rentalTerms, $depositPolicy, $finePolicy,
    $storeStatus, $storeId
);

if (!mysqli_stmt_execute($updateStmt)) {
    set_flash('error', 'Gagal menyimpan pengaturan.');
    redirect_route('toko.settings');
}
mysqli_stmt_close($updateStmt);

$deleteCatStmt = mysqli_prepare($conn, "DELETE FROM store_categories WHERE store_id = ?");
mysqli_stmt_bind_param($deleteCatStmt, 'i', $storeId);
mysqli_stmt_execute($deleteCatStmt);
mysqli_stmt_close($deleteCatStmt);

if (!empty($selectedCategories)) {
    $catStmt = mysqli_prepare($conn, "INSERT INTO store_categories (store_id, category_id) VALUES (?, ?)");
    foreach ($selectedCategories as $catId) {
        $catId = (int) $catId;
        if ($catId > 0) {
            mysqli_stmt_bind_param($catStmt, 'ii', $storeId, $catId);
            mysqli_stmt_execute($catStmt);
        }
    }
    mysqli_stmt_close($catStmt);
}

set_flash('success', 'Pengaturan toko berhasil disimpan.');
redirect_route('toko.settings');
