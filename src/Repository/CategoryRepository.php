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

    public function create(
        string $name,
        ?string $unit,
        string $price,
        ?string $remark,
        array $categoryIds
    ): int {
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO products (
                    name,
                    unit,
                    price,
                    remark
                ) VALUES (
                    :name,
                    :unit,
                    :price,
                    :remark
                )'
            );

            $statement->execute([
                'name' => $name,
                'unit' => $unit,
                'price' => $price,
                'remark' => $remark,
            ]);

            $productId = (int) $this->pdo->lastInsertId();

            if ($categoryIds !== []) {
                $categoryStatement = $this->pdo->prepare(
                    'INSERT INTO product_categories (
                        product_id,
                        category_id
                    ) VALUES (
                        :product_id,
                        :category_id
                    )'
                );

                foreach ($categoryIds as $categoryId) {
                    $categoryStatement->execute([
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $productId;
        } catch (Throwable $exception) {
            if (
                $ownsTransaction
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}