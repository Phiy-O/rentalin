<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/flash.php';

function generate_csrf_token()
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf_token($token)
{
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrf_field()
{
    echo '<input type="hidden" name="_token" value="' . generate_csrf_token() . '">';
}

function csrf_url_param()
{
    return '_token=' . urlencode(generate_csrf_token());
}

function require_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        set_flash('error', 'Metode request tidak valid.');
        redirect_route('home');
    }

    $token = $_POST['_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Sesi form tidak valid. Silakan coba lagi.');
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer && str_starts_with($referer, BASE_URL)) {
            header('Location: ' . $referer);
            exit;
        }
        redirect_route('home');
    }
}

function require_csrf_get()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        set_flash('error', 'Metode request tidak valid.');
        redirect_route('home');
    }

    $token = $_GET['_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Sesi form tidak valid. Silakan coba lagi.');
        redirect_route('home');
    }
}
