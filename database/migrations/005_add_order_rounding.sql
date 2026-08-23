ALTER TABLE orders
    ADD COLUMN rounding_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
    AFTER status;