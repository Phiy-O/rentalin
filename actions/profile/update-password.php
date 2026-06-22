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
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$errors = [];

if ($currentPassword === '') {
    $errors[] = 'Password saat ini wajib diisi.';
}

if (strlen($newPassword) < 8) {
    $errors[] = 'Password baru minimal 8 karakter.';
}

if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    $errors[] = 'Password baru harus berisi huruf dan angka.';
}

if ($newPassword !== $confirmPassword) {
    $errors[] = 'Konfirmasi password baru tidak sama.';
}

$stmt = mysqli_prepare($conn, 'SELECT password FROM users WHERE id = ? LIMIT 1');
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

if ($newPassword !== '' && password_verify($newPassword, $user['password'])) {
    $errors[] = 'Password baru tidak boleh sama dengan password saat ini.';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('profile', ['tab' => 'security']);
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'si', $passwordHash, $userId);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash('error', 'Gagal mengganti password. Silakan coba lagi.');
    redirect_route('profile', ['tab' => 'security']);
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'DELETE FROM remember_tokens WHERE user_id = ?');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

set_flash('success', 'Password berhasil diganti. Login tersimpan di perangkat lain sudah dicabut.');
redirect_route('profile', ['tab' => 'security']);
