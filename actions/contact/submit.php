<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_route('contact');
}

require_csrf();

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$errors = [];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email tidak valid.';
}

if ($name === '') {
    $errors[] = 'Nama wajib diisi.';
}

if ($message === '') {
    $errors[] = 'Pesan wajib diisi.';
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
    $errors[] = 'Nomor telepon tidak valid.';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    $_SESSION['contact_old'] = [
        'email' => $email,
        'name' => $name,
        'phone' => $phone,
        'message' => $message,
    ];
    redirect_route('contact');
}

unset($_SESSION['contact_old']);
set_flash('success', 'Pesan berhasil dikirim. Tim Rentalin akan segera menghubungi kamu.');
redirect_route('contact');
