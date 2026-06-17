<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function show_flash()
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $type = htmlspecialchars($flash['type']);
    $message = htmlspecialchars($flash['message']);

    echo "<div class=\"alert alert-{$type}\">{$message}</div>";
}
