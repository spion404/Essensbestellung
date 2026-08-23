CREATE TABLE orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_id INT UNSIGNED NOT NULL,
    delivery_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    submitted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY orders_group_delivery_unique (
        group_id,
        delivery_date
    ),
    KEY orders_delivery_date_index (delivery_date),
    KEY orders_status_index (status),

    CONSTRAINT orders_group_fk
        FOREIGN KEY (group_id)
        REFERENCES groups (id)
        ON DELETE RESTRICT,

    CONSTRAINT orders_status_check
        CHECK (status IN ('draft', 'submitted'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;


CREATE TABLE order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,

    article_number VARCHAR(100) NULL,
    product_name VARCHAR(200) NOT NULL,
    unit VARCHAR(50) NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity DECIMAL(10, 3) NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY order_items_order_product_unique (
        order_id,
        product_id
    ),
    KEY order_items_product_index (product_id),

    CONSTRAINT order_items_order_fk
        FOREIGN KEY (order_id)
        REFERENCES orders (id)
        ON DELETE CASCADE,

    CONSTRAINT order_items_product_fk
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON DELETE SET NULL,

    CONSTRAINT order_items_unit_price_check
        CHECK (unit_price >= 0),

    CONSTRAINT order_items_quantity_check
        CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;