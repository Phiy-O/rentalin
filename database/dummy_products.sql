USE rentalin_db;

INSERT INTO users (id, name, username, email, password, phone, address, role)
VALUES
(1001, 'Rentalin Demo Store Owner', 'demo_store_owner', 'demo.store@rentalin.test', '$2y$10$kQpXKc7uGd4Q9zJZB4xNx.VC3lJjhRLwVyB7rD8duVfABkzR8QrkS', '081234567890', 'Yogyakarta', 'user')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO stores (id, user_id, name, description, address, phone, status)
VALUES
(1001, 1001, 'Rentalin Demo Store', 'Toko dummy untuk testing katalog produk.', 'Yogyakarta', '081234567890', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status);

INSERT INTO products (id, store_id, category_id, name, description, price_per_day, stock, condition_status, status)
VALUES
(1001, 1001, (SELECT id FROM categories WHERE slug = 'kamera' LIMIT 1), 'Sony Mirrorless A6000', 'Kamera mirrorless ringan untuk foto produk, event, dan traveling.', 150000, 3, 'Sangat Baik', 'available'),
(1002, 1001, (SELECT id FROM categories WHERE slug = 'perlengkapan-outdoor' LIMIT 1), 'Tenda Camping 4 Orang', 'Tenda camping waterproof untuk kegiatan outdoor dan hiking.', 75000, 5, 'Baik', 'available'),
(1003, 1001, (SELECT id FROM categories WHERE slug = 'elektronik' LIMIT 1), 'Proyektor HD Meeting', 'Proyektor HD untuk meeting, kelas, dan acara keluarga.', 120000, 2, 'Sangat Baik', 'available'),
(1004, 1001, (SELECT id FROM categories WHERE slug = 'kamera' LIMIT 1), 'Tripod Kamera Pro', 'Tripod kokoh untuk kamera DSLR, mirrorless, dan smartphone.', 35000, 8, 'Baik', 'available'),
(1005, 1001, (SELECT id FROM categories WHERE slug = 'elektronik' LIMIT 1), 'Speaker Aktif Portable', 'Speaker aktif untuk acara kecil, presentasi, dan gathering.', 90000, 4, 'Baik', 'available'),
(1006, 1001, (SELECT id FROM categories WHERE slug = 'kamera' LIMIT 1), 'Drone Mini 4K', 'Drone mini untuk dokumentasi aerial dengan kualitas video 4K.', 200000, 1, 'Sangat Baik', 'available'),
(1007, 1001, (SELECT id FROM categories WHERE slug = 'kendaraan' LIMIT 1), 'Sepeda Lipat Harian', 'Sepeda lipat nyaman untuk jalan santai dan mobilitas ringan.', 50000, 4, 'Baik', 'available'),
(1008, 1001, (SELECT id FROM categories WHERE slug = 'kamera' LIMIT 1), 'Lighting Studio Set', 'Paket lighting untuk foto produk dan konten video.', 110000, 3, 'Sangat Baik', 'available')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
description = VALUES(description),
price_per_day = VALUES(price_per_day),
stock = VALUES(stock),
condition_status = VALUES(condition_status),
status = VALUES(status);

DELETE FROM product_images WHERE product_id BETWEEN 1001 AND 1008;

INSERT INTO product_images (product_id, image, is_primary)
VALUES
(1001, 'dummy-camera.svg', 1),
(1002, 'dummy-tent.svg', 1),
(1003, 'dummy-projector.svg', 1),
(1004, 'dummy-tripod.svg', 1),
(1005, 'dummy-speaker.svg', 1),
(1006, 'dummy-drone.svg', 1),
(1007, 'dummy-bike.svg', 1),
(1008, 'dummy-lighting.svg', 1);
