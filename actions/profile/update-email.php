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
$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$errors = [];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email baru tidak valid.';
}

if ($currentPassword === '') {
    $errors[] = 'Password saat ini wajib diisi.';
}

$stmt = mysqli_prepare($conn, 'SELECT email, password FROM users WHERE id = ? LIMIT 1');
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

if ($email !== '' && strcasecmp($email, $user['email']) === 0) {
    $errors[] = 'Email baru masih sama dengan email saat ini.';
}

if ($email !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'Email sudah digunakan.';
    }
    mysqli_stmt_close($stmt);
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect_route('profile', ['tab' => 'security']);
}

$stmt = mysqli_prepare($conn, 'UPDATE users SET email = ? WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'si', $email, $userId);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    set_flash('error', 'Gagal mengganti email. Silakan coba lagi.');
    redirect_route('profile', ['tab' => 'security']);
}
mysqli_stmt_close($stmt);

$_SESSION['email'] = $email;

set_flash('success', 'Email berhasil diganti. Verifikasi email dapat ditambahkan saat layanan email sudah tersedia.');
redirect_route('profile', ['tab' => 'security']);
