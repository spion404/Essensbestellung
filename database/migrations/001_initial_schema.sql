CREATE TABLE settings (
    id TINYINT UNSIGNED NOT NULL,

    camp_name VARCHAR(150) NOT NULL DEFAULT 'Pfadilager',

    budget_full_day DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    budget_half_day DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    budget_visitor_day DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    order_cutoff_time TIME NOT NULL DEFAULT '20:00:00',

    arrival_date DATE NULL,

    week1_start_date DATE NULL,
    week1_end_date DATE NULL,

    week1_departure_date DATE NULL,

    visitor_date DATE NULL,

    week2_start_date DATE NULL,
    week2_end_date DATE NULL,

    week2_departure_date DATE NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


CREATE TABLE groups (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(150) NOT NULL,

    password_encrypted TEXT NOT NULL,

    participants_arrival_half INT UNSIGNED NOT NULL DEFAULT 0,

    participants_week1_full INT UNSIGNED NOT NULL DEFAULT 0,

    participants_week1_departure_half INT UNSIGNED NOT NULL DEFAULT 0,
    participants_week1_departure_full INT UNSIGNED NOT NULL DEFAULT 0,

    participants_visitors INT UNSIGNED NOT NULL DEFAULT 0,

    participants_week2_full INT UNSIGNED NOT NULL DEFAULT 0,

    participants_week2_departure_half INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY groups_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(100) NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(200) NOT NULL,
    unit VARCHAR(50) NULL,

    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    remark TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


CREATE TABLE product_categories (
    product_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (product_id, category_id),

    CONSTRAINT product_categories_product_fk
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON DELETE CASCADE,

    CONSTRAINT product_categories_category_fk
        FOREIGN KEY (category_id)
        REFERENCES categories (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


INSERT INTO settings (
    id
) VALUES (
    1
);