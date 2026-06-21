<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['rentalin_remember'])) {
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/includes/remember-me.php';
    try_remember_login($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? APP_NAME : 'Rentalin'; ?></title>
    <?php $cssVersion = filemtime(dirname(__DIR__) . '/assets/css/style.css'); ?>
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : ''; ?>/assets/css/style.css?v=<?= $cssVersion; ?>">
</head>
<body>
