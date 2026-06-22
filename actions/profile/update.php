<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('profile');
}

require_csrf();

$userId = (int) $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$address = trim($_POST['address'] ?? '');
$errors = [];

if ($name === '') $errors[] = 'Nama wajib diisi.';
if ($username === '') $errors[] = 'Username wajib diisi.';

if ($username !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $username, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'Username sudah digunakan.';
    }
    mysqli_stmt_close($stmt);
}

$profileImage = null;

if (!empty($_FILES['profile_image']['name'])) {
    if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Foto profil gagal diupload.';
    } elseif ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Ukuran foto profil maksimal 5MB.';
    } else {
        $mime = '';
        $info = @getimagesize($_FILES['profile_image']['tmp_name']);
        if (!empty($info['mime'])) {
            $mime = strtolower($info['mime']);
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            $errors[] = 'Format foto harus JPG, PNG, atau WebP.';
        } else {
            if (!is_dir(UPLOAD_PROFILES_PATH)) {
                mkdir(UPLOAD_PROFILES_PATH, 0777, true);
            }

            $profileImage = 'profile_' . $userId . '_' . time() . '.webp';
            $dest = UPLOAD_PROFILES_PATH . $profileImage;
            $source = false;

            if ($mime === 'image/jpeg') $source = @imagecreatefromjpeg($_FILES['profile_image']['tmp_name']);
            if ($mime === 'image/png') $source = @imagecreatefrompng($_FILES['profile_image']['tmp_name']);
            if ($mime === 'image/webp') $source = @imagecreatefromwebp($_FILES['profile_image']['tmp_name']);

            if ($source && function_exists('imagewebp')) {
                $width = imagesx($source);
                $height = imagesy($source);
                $maxSize = 480;
                $scale = min(1, $maxSize / max($width, $height));
                $newWidth = max(1, (int) floor($width * $scale));
                $newHeight = max(1, (int) floor($height * $scale));
                $canvas = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                $saved = imagewebp($canvas, $dest, 80);
                imagedestroy($source);
                imagedestroy($canvas);

                if (!$saved) {
                    $errors[] = 'Foto profil gagal diproses.';
                    $profileImage = null;
                }
            } else {
                $fallback = 'profile_' . $userId . '_' . time() . '.' . $allowed[$mime];
                if ($source) {
                    imagedestroy($source);
                }
                $profileImage = move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PROFILES_PATH . $fallback) ? $fallback : null;
                if (!$profileImage) {
                    $errors[] = 'Foto profil gagal disimpan.';
                }
            }
        }
    }
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('profile');
}

if ($profileImage) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, username = ?, address = ?, profile_image = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $username, $address, $profileImage, $userId);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, username = ?, address = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'sssi', $name, $username, $address, $userId);
}

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash('error', 'Gagal memperbarui profil. Silakan coba lagi.');
    redirect_route('profile');
}
mysqli_stmt_close($stmt);

$_SESSION['name'] = $name;
$_SESSION['username'] = $username;

set_flash('success', 'Profil berhasil diperbarui.');
redirect_route('profile');
