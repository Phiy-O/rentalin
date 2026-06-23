<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('register');
}

require_csrf();

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $username === '' || $email === '' || $password === '') {
    set_flash('error', 'Nama, username, email, dan password wajib diisi.');
    redirect_route('register');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Format email tidak valid.');
    redirect_route('register');
}

if (strlen($password) < 8) {
    set_flash('error', 'Password minimal 8 karakter.');
    redirect_route('register');
}

$checkQuery = 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1';
$checkStmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($checkStmt, 'ss', $username, $email);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    set_flash('error', 'Username atau email sudah terdaftar.');
    redirect_route('register');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insertQuery = 'INSERT INTO users (name, username, email, password, phone) VALUES (?, ?, ?, ?, ?)';
$insertStmt = mysqli_prepare($conn, $insertQuery);
mysqli_stmt_bind_param($insertStmt, 'sssss', $name, $username, $email, $hashedPassword, $phone);

if (mysqli_stmt_execute($insertStmt)) {
    session_regenerate_id(true); // Regenerate session ID after successful registration
    set_flash('success', 'Registrasi berhasil. Silakan login.');
    redirect_route('login');
}

set_flash('error', 'Registrasi gagal. Silakan coba lagi.');
redirect_route('register');
