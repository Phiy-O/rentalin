<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('toko.products.create');
}

require_csrf();

$storeStmt = mysqli_prepare($conn, "SELECT id FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
mysqli_stmt_bind_result($storeStmt, $storeId);
mysqli_stmt_fetch($storeStmt);
mysqli_stmt_close($storeStmt);

if (!$storeId) {
    set_flash('error', 'Kamu tidak memiliki toko.');
    redirect_route('toko.create');
}

$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$pricePerDay = trim($_POST['price_per_day'] ?? '');
$stock = (int) ($_POST['stock'] ?? 1);
$conditionStatus = trim($_POST['condition_status'] ?? '');
$productStatus = $_POST['status'] ?? 'available';

$errors = [];

if ($name === '') $errors[] = 'Nama barang wajib diisi.';
if ($categoryId <= 0) $errors[] = 'Kategori wajib dipilih.';
if ($pricePerDay === '' || (float) $pricePerDay <= 0) $errors[] = 'Harga sewa per hari wajib diisi dengan angka yang valid.';
if ($stock < 0) $errors[] = 'Stok tidak boleh negatif.';

$pricePerDay = str_replace(['.', ','], ['', '.'], $pricePerDay);
$pricePerDay = (float) $pricePerDay;

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('toko.products.create');
}

$insertStmt = mysqli_prepare($conn, "INSERT INTO products (store_id, category_id, name, description, price_per_day, stock, condition_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, 'iissdiss', $storeId, $categoryId, $name, $description, $pricePerDay, $stock, $conditionStatus, $productStatus);

if (!mysqli_stmt_execute($insertStmt)) {
    set_flash('error', 'Gagal menyimpan barang. Silakan coba lagi.');
    redirect_route('toko.products.create');
}

$productId = mysqli_insert_id($conn);
mysqli_stmt_close($insertStmt);

if (!empty($_FILES['images']['name'][0])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $uploadDir = UPLOAD_PRODUCTS_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $isFirst = true;
    $totalFiles = count($_FILES['images']['name']);

    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (!in_array($_FILES['images']['type'][$i], $allowedTypes)) continue;

        $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $filename = 'product_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $destPath)) {
            $isPrimary = $isFirst ? 1 : 0;
            $isFirst = false;

            $imgStmt = mysqli_prepare($conn, "INSERT INTO product_images (product_id, image, is_primary) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($imgStmt, 'isi', $productId, $filename, $isPrimary);
            mysqli_stmt_execute($imgStmt);
            mysqli_stmt_close($imgStmt);
        }
    }
}

set_flash('success', 'Barang berhasil ditambahkan.');
redirect_route('toko.products');
