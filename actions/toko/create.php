<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('toko.create');
}

require_csrf();

$formData = $_SESSION['store_form'] ?? [];

if (empty($formData) || empty($formData['name']) || empty($formData['slug'])) {
    set_flash('error', 'Data toko tidak lengkap. Silakan mulai dari awal.');
    redirect_route('toko.create');
}

$openTime = trim($_POST['open_time'] ?? '');
$closeTime = trim($_POST['close_time'] ?? '');
$rentalTerms = trim($_POST['rental_terms'] ?? '');
$depositPolicy = trim($_POST['deposit_policy'] ?? '');
$finePolicy = trim($_POST['fine_policy'] ?? '');

$errors = [];

if ($openTime === '') $errors[] = 'Jam buka wajib diisi.';
if ($closeTime === '') $errors[] = 'Jam tutup wajib diisi.';

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    $_SESSION['store_form']['open_time'] = $openTime;
    $_SESSION['store_form']['close_time'] = $closeTime;
    $_SESSION['store_form']['rental_terms'] = $rentalTerms;
    $_SESSION['store_form']['deposit_policy'] = $depositPolicy;
    $_SESSION['store_form']['fine_policy'] = $finePolicy;
    redirect_route('toko.create', ['step' => '2']);
}

$finalLogo = '';
if (!empty($formData['logo_temp'])) {
    $tempPath = dirname(__DIR__, 2) . '/uploads/temp/' . $formData['logo_temp'];
    $finalPath = UPLOAD_LOGO_PATH . $formData['logo_temp'];
    if (file_exists($tempPath)) {
        if (!is_dir(UPLOAD_LOGO_PATH)) {
            mkdir(UPLOAD_LOGO_PATH, 0777, true);
        }
        rename($tempPath, $finalPath);
        $finalLogo = $formData['logo_temp'];
    }
}

$categories = [];
if (!empty($formData['categories'])) {
    if (is_string($formData['categories'])) {
        $categories = array_map('intval', explode(',', $formData['categories']));
    } elseif (is_array($formData['categories'])) {
        $categories = array_map('intval', $formData['categories']);
    }
}

$stmt = mysqli_prepare($conn, "INSERT INTO stores (user_id, name, slug, description, logo, address, city, province, google_maps_link, phone, email, open_time, close_time, rental_terms, deposit_policy, fine_policy, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

mysqli_stmt_bind_param(
    $stmt,
    'isssssssssssssss',
    $_SESSION['user_id'],
    $formData['name'],
    $formData['slug'],
    $formData['description'],
    $finalLogo,
    $formData['address'],
    $formData['city'],
    $formData['province'],
    $formData['google_maps'],
    $formData['phone'],
    $formData['email'],
    $openTime,
    $closeTime,
    $rentalTerms,
    $depositPolicy,
    $finePolicy
);

if (mysqli_stmt_execute($stmt)) {
    $storeId = mysqli_insert_id($conn);

    if (!empty($categories)) {
        $catStmt = mysqli_prepare($conn, "INSERT INTO store_categories (store_id, category_id) VALUES (?, ?)");
        foreach ($categories as $categoryId) {
            mysqli_stmt_bind_param($catStmt, 'ii', $storeId, $categoryId);
            mysqli_stmt_execute($catStmt);
        }
        mysqli_stmt_close($catStmt);
    }

    $tempDir = dirname(__DIR__, 2) . '/uploads/temp/';
    if (!empty($formData['logo_temp']) && file_exists($tempDir . $formData['logo_temp'])) {
        @unlink($tempDir . $formData['logo_temp']);
    }

    unset($_SESSION['store_form']);

    set_flash('success', 'Toko berhasil dibuat! Selamat datang di dashboard toko.');
    redirect_route('toko.dashboard');
}

set_flash('error', 'Gagal menyimpan toko. Silakan coba lagi.');
redirect_route('toko.create', ['step' => '2']);
