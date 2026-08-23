<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CategoryRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                id,
                name
            FROM categories
            ORDER BY name ASC'
        );

        return $statement->fetchAll();
    }

    public function findProductCategoryIds(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                product_id,
                category_id
            FROM product_categories
            ORDER BY
                product_id ASC,
                category_id ASC'
        );

        $categoryIdsByProduct = [];

        foreach ($statement->fetchAll() as $row) {
            $productId = (int) $row['product_id'];

            if (!isset($categoryIdsByProduct[$productId])) {
                $categoryIdsByProduct[$productId] = [];
            }

            $categoryIdsByProduct[$productId][] =
                (int) $row['category_id'];
        }

        return $categoryIdsByProduct;
    }

    public function findIdByName(string $name): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT id
            FROM categories
            WHERE name = :name
            LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
        ]);

        $id = $statement->fetchColumn();

        if ($id === false) {
            return null;
        }

        return (int) $id;
    }

    public function findOrCreateId(string $name): int
    {
        $id = $this->findIdByName($name);

        if ($id !== null) {
            return $id;
        }

        return $this->create($name);
    }

    public function nameExists(string $name): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
            FROM categories
            WHERE name = :name
            LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (
                name
            ) VALUES (
                :name
            )'
        );

        $statement->execute([
            'name' => $name,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}