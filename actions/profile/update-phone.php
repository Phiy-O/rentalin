<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('profile', ['tab' => 'security']);
}

require_csrf();

$userId = (int) $_SESSION['user_id'];
$phone = trim($_POST['phone'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$errors = [];

if ($phone === '') {
    $errors[] = 'Nomor HP wajib diisi.';
} elseif (!preg_match('/^(?:\+62|62|0)8[0-9]{8,13}$/', $phone)) {
    $errors[] = 'Format nomor HP tidak valid. Gunakan format 08..., 62..., atau +62...';
}

if ($currentPassword === '') {
    $errors[] = 'Password saat ini wajib diisi.';
}

$stmt = mysqli_prepare($conn, 'SELECT phone, password FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    redirect_route('logout');
}

if ($currentPassword !== '' && !password_verify($currentPassword, $user['password'])) {
    $errors[] = 'Password saat ini salah.';
}

if ($phone !== '' && $phone === ($user['phone'] ?? '')) {
    $errors[] = 'Nomor HP baru masih sama dengan nomor saat ini.';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('profile', ['tab' => 'security']);
}

$stmt = mysqli_prepare($conn, 'UPDATE users SET phone = ? WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'si', $phone, $userId);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash('error', 'Gagal mengganti nomor HP. Silakan coba lagi.');
    redirect_route('profile', ['tab' => 'security']);
}
mysqli_stmt_close($stmt);

set_flash('success', 'Nomor HP berhasil diganti.');
redirect_route('profile', ['tab' => 'security']);
