<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/product-upload-helper.php';

function parse_rental_price($value)
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/', '', $value);

    if ($value === '') {
        return 0;
    }

    $hasComma = strpos($value, ',') !== false;
    $hasDot = strpos($value, '.') !== false;

    if ($hasComma && $hasDot) {
        if (strrpos($value, ',') > strrpos($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($hasComma) {
        $value = str_replace(',', '.', $value);
    } elseif ($hasDot) {
        $parts = explode('.', $value);
        $last = end($parts);
        if (strlen($last) === 3) {
            $value = str_replace('.', '', $value);
        }
    }

    return is_numeric($value) ? (float) $value : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('toko.products');
}

require_csrf();

$productId = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$pricePerDayInput = trim($_POST['price_per_day'] ?? '');
$stock = (int) ($_POST['stock'] ?? 1);
$conditionStatus = trim($_POST['condition_status'] ?? '');
$productStatus = $_POST['status'] ?? 'available';

if ($productId <= 0) {
    set_flash('error', 'Barang tidak valid.');
    redirect_route('toko.products');
}

$_SESSION['old_input'] = [
    'name' => $name,
    'category_id' => $categoryId,
    'description' => $description,
    'price_per_day' => $pricePerDayInput,
    'stock' => $stock,
    'condition_status' => $conditionStatus,
    'status' => $productStatus,
];

$storeStmt = mysqli_prepare($conn, "SELECT id FROM stores WHERE user_id = ?");
mysqli_stmt_bind_param($storeStmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($storeStmt);
$store = mysqli_fetch_assoc(mysqli_stmt_get_result($storeStmt));
mysqli_stmt_close($storeStmt);

if (!$store) {
    set_flash('error', 'Kamu tidak memiliki toko.');
    redirect_route('toko.create');
}

$storeId = (int) $store['id'];

$checkStmt = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ? AND store_id = ?");
mysqli_stmt_bind_param($checkStmt, 'ii', $productId, $storeId);
mysqli_stmt_execute($checkStmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
mysqli_stmt_close($checkStmt);

if (!$product) {
    set_flash('error', 'Barang tidak ditemukan atau bukan milik toko kamu.');
    redirect_route('toko.products');
}

$pricePerDay = parse_rental_price($pricePerDayInput);

$errors = [];
if ($name === '') $errors[] = 'Nama barang wajib diisi.';
if ($categoryId <= 0) $errors[] = 'Kategori wajib dipilih.';
if ($pricePerDay <= 0) $errors[] = 'Harga sewa per hari wajib diisi dengan angka yang valid.';
if ($stock < 0) $errors[] = 'Stok tidak boleh negatif.';
if (!in_array($productStatus, ['available', 'unavailable'], true)) $errors[] = 'Status barang tidak valid.';

if ($categoryId > 0) {
    $catStmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($catStmt, 'i', $categoryId);
    mysqli_stmt_execute($catStmt);
    mysqli_stmt_store_result($catStmt);
    if (mysqli_stmt_num_rows($catStmt) === 0) {
        $errors[] = 'Kategori tidak ditemukan.';
    }
    mysqli_stmt_close($catStmt);
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('toko.products.edit', ['id' => $productId]);
}

$updateStmt = mysqli_prepare($conn, "
    UPDATE products
    SET category_id = ?, name = ?, description = ?, price_per_day = ?, stock = ?, condition_status = ?, status = ?
    WHERE id = ? AND store_id = ?
");
mysqli_stmt_bind_param($updateStmt, 'issdissii', $categoryId, $name, $description, $pricePerDay, $stock, $conditionStatus, $productStatus, $productId, $storeId);

if (!mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    set_flash('error', 'Gagal memperbarui barang. Silakan coba lagi.');
    redirect_route('toko.products.edit', ['id' => $productId]);
}
mysqli_stmt_close($updateStmt);

if (!empty($_FILES['images']['name'][0])) {
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM product_images WHERE product_id = ?");
    mysqli_stmt_bind_param($countStmt, 'i', $productId);
    mysqli_stmt_execute($countStmt);
    $currentImages = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
    mysqli_stmt_close($countStmt);

    $maxFiles = max(0, 5 - $currentImages);
    $maxSize = 10 * 1024 * 1024;
    $failedUploads = 0;
    $uploadDir = UPLOAD_PRODUCTS_PATH;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $totalFiles = min(count($_FILES['images']['name']), $maxFiles);
    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
            $failedUploads++;
            continue;
        }
        if ($_FILES['images']['size'][$i] > $maxSize) {
            $failedUploads++;
            continue;
        }

        $filename = save_product_upload(
            $_FILES['images']['tmp_name'][$i],
            $_FILES['images']['name'][$i],
            $_FILES['images']['type'][$i],
            $productId,
            $i,
            'edit'
        );

        if ($filename) {
            $isPrimary = $currentImages === 0 && $i === 0 ? 1 : 0;
            $imgStmt = mysqli_prepare($conn, "INSERT INTO product_images (product_id, image, is_primary) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($imgStmt, 'isi', $productId, $filename, $isPrimary);
            mysqli_stmt_execute($imgStmt);
            mysqli_stmt_close($imgStmt);
        } else {
            $failedUploads++;
        }
    }
}

unset($_SESSION['old_input']);
set_flash('success', 'Barang berhasil diperbarui.' . (!empty($failedUploads) ? ' Beberapa foto gagal diupload.' : ''));
redirect_route('toko.products.detail', ['id' => $productId]);
