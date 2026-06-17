<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/app.php';

if (!isset($_SESSION['user_id'])) {
    redirect_route('login');
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Session berakhir karena tidak ada aktivitas. Silakan login kembali.',
    ];

    redirect_route('login');
}

$_SESSION['last_activity'] = time();
