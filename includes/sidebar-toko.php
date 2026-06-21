<aside class="store-sidebar">
    <h3>Menu Toko</h3>
    <ul>
        <li><a href="<?= route('toko.dashboard'); ?>"<?= (isset($activeMenu) && $activeMenu === 'dashboard') ? ' class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="<?= route('toko.orders'); ?>"<?= (isset($activeMenu) && $activeMenu === 'orders') ? ' class="active"' : ''; ?>>Pesanan Rental</a></li>
        <li><a href="<?= route('toko.products'); ?>"<?= (isset($activeMenu) && $activeMenu === 'products') ? ' class="active"' : ''; ?>>Barang Saya</a></li>
        <li><a href="<?= route('toko.products.create'); ?>"<?= (isset($activeMenu) && $activeMenu === 'product-create') ? ' class="active"' : ''; ?>>Tambah Barang</a></li>
        <li><a href="<?= route('toko.returns'); ?>"<?= (isset($activeMenu) && $activeMenu === 'returns') ? ' class="active"' : ''; ?>>Pengembalian</a></li>
        <li><a href="<?= route('toko.settings'); ?>"<?= (isset($activeMenu) && $activeMenu === 'settings') ? ' class="active"' : ''; ?>>Pengaturan Toko</a></li>
    </ul>
</aside>
