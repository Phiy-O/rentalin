<?php

$routes = [
    'home' => '/index.php',
    'login' => '/pages/login.php',
    'register' => '/pages/register.php',
    'catalog' => '/pages/catalog.php',
    'product.detail' => '/pages/product-detail.php',
    'rental.checkout' => '/pages/rental-checkout.php',
    'rental.store' => '/actions/rental/store.php',
    'profile' => '/pages/profile.php',
    'about' => '/pages/about.php',
    'services' => '/pages/services.php',
    'contact' => '/pages/contact.php',
    'logout' => '/actions/auth/logout.php',
    'auth.login' => '/actions/auth/login.php',
    'auth.register' => '/actions/auth/register.php',
    'contact.submit' => '/pages/contact.php',
    'cart' => '/pages/catalog.php',
    'notifications' => '/pages/catalog.php',
    'terms.privacy' => '/pages/contact.php',
    'terms.rental' => '/pages/contact.php',
    'help' => '/pages/contact.php',
    'toko.create' => '/toko/buat.php',
    'toko.create.store' => '/actions/toko/create.php',
    'toko.dashboard' => '/toko/dashboard.php',
    'toko.orders' => '/toko/pesanan.php',
    'toko.products' => '/toko/barang-saya.php',
    'toko.products.create' => '/toko/tambah-barang.php',
    'toko.products.edit' => '/toko/edit-barang.php',
    'toko.products.detail' => '/toko/detail-barang.php',
    'toko.products.toggle' => '/actions/barang/toggle.php',
    'toko.products.store' => '/actions/barang/store.php',
    'toko.returns' => '/toko/pengembalian.php',
    'toko.settings' => '/toko/pengaturan.php',
    'toko.settings.update' => '/actions/toko/update.php',
    'toko.order.detail' => '/toko/detail-pesanan.php',
    'rental.accept' => '/actions/rental/accept.php',
    'rental.reject' => '/actions/rental/reject.php',
];

if (!function_exists('route')) {
    function route($name, $params = [])
    {
        global $routes;

        if (!isset($routes[$name])) {
            return BASE_URL;
        }

        $url = BASE_URL . $routes[$name];

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}

if (!function_exists('redirect_route')) {
    function redirect_route($name, $params = [])
    {
        header('Location: ' . route($name, $params));
        exit;
    }
}
