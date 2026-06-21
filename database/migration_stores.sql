USE rentalin_db;

ALTER TABLE stores
    ADD COLUMN slug VARCHAR(120) NOT NULL UNIQUE AFTER name,
    ADD COLUMN logo VARCHAR(255) NULL AFTER description,
    ADD COLUMN city VARCHAR(100) NULL AFTER address,
    ADD COLUMN province VARCHAR(100) NULL AFTER city,
    ADD COLUMN google_maps_link TEXT NULL AFTER province,
    ADD COLUMN email VARCHAR(100) NULL AFTER phone,
    ADD COLUMN open_time TIME NULL AFTER email,
    ADD COLUMN close_time TIME NULL AFTER open_time,
    ADD COLUMN rental_terms TEXT NULL AFTER close_time,
    ADD COLUMN deposit_policy TEXT NULL AFTER rental_terms,
    ADD COLUMN fine_policy TEXT NULL AFTER deposit_policy;

ALTER TABLE stores
    MODIFY COLUMN status ENUM('pending', 'active', 'inactive') NOT NULL DEFAULT 'active';

CREATE TABLE IF NOT EXISTS store_categories (
    store_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (store_id, category_id),
    CONSTRAINT fk_sc_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT fk_sc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
