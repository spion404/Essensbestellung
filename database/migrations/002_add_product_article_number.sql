ALTER TABLE products
    ADD COLUMN article_number VARCHAR(100) NULL AFTER id,
    ADD UNIQUE KEY products_article_number_unique (article_number);