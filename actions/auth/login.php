<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/remember-me.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('login');
}

require_csrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($email === '' || $password === '') {
    set_flash('error', 'Email dan password wajib diisi.');
    redirect_route('login');
}

$query = 'SELECT id, name, username, email, password, role FROM users WHERE email = ? LIMIT 1';
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password'])) {
    set_flash('error', 'Email atau password salah.');
    redirect_route('login');
}

session_regenerate_id(true);
set_user_session($user);

if ($remember) {
    create_remember_token($conn, (int) $user['id']);
} else {
    clear_remember_token($conn);
}

set_flash('success', 'Login berhasil. Selamat datang, ' . $user['name'] . '.');
redirect_route('catalog');
