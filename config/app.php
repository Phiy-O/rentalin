<?php

require_once __DIR__ . '/env.php';

define('APP_NAME', $_ENV['APP_NAME'] ?? 'Rentalin');
define('BASE_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/rentalin', '/'));
define('SESSION_TIMEOUT', 30 * 60);

require_once __DIR__ . '/routes.php';

define('UPLOAD_PRODUCTS_PATH', dirname(__DIR__) . '/uploads/products/');
define('UPLOAD_STORES_PATH', dirname(__DIR__) . '/uploads/stores/');
define('UPLOAD_LOGO_PATH', dirname(__DIR__) . '/uploads/logos/');
define('UPLOAD_RETURNS_PATH', dirname(__DIR__) . '/uploads/returns/');
define('UPLOAD_PROFILES_PATH', dirname(__DIR__) . '/uploads/profiles/');

define('UPLOAD_PRODUCTS_URL', BASE_URL . '/uploads/products/');
define('UPLOAD_STORES_URL', BASE_URL . '/uploads/stores/');
define('UPLOAD_LOGO_URL', BASE_URL . '/uploads/logos/');
define('UPLOAD_RETURNS_URL', BASE_URL . '/uploads/returns/');
define('UPLOAD_PROFILES_URL', BASE_URL . '/uploads/profiles/');

require_once dirname(__DIR__) . '/includes/icon-helper.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
