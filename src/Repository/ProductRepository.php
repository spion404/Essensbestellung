<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

final class ProductRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                p.id,
                p.name,
                p.unit,
                p.price,
                p.remark,
                GROUP_CONCAT(
                    c.name
                    ORDER BY c.name ASC
                    SEPARATOR ", "
                ) AS categories
            FROM products p
            LEFT JOIN product_categories pc
                ON pc.product_id = p.id
            LEFT JOIN categories c
                ON c.id = pc.category_id
            GROUP BY
                p.id,
                p.name,
                p.unit,
                p.price,
                p.remark
            ORDER BY p.name ASC'
        );

        return $statement->fetchAll();
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

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                name,
                unit,
                price,
                remark
            FROM products
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $product = $statement->fetch();

        if ($product === false) {
            return null;
        }

        return $product;
    }

    public function findCategoryIds(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT category_id
            FROM product_categories
            WHERE product_id = :product_id
            ORDER BY category_id ASC'
        );

        $statement->execute([
            'product_id' => $productId,
        ]);

        return array_map(
            'intval',
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function update(
        int $id,
        string $name,
        ?string $unit,
        string $price,
        ?string $remark,
        array $categoryIds
    ): void {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'UPDATE products
                SET
                    name = :name,
                    unit = :unit,
                    price = :price,
                    remark = :remark
                WHERE id = :id'
            );

            $statement->execute([
                'name' => $name,
                'unit' => $unit,
                'price' => $price,
                'remark' => $remark,
                'id' => $id,
            ]);

            $deleteCategoriesStatement = $this->pdo->prepare(
                'DELETE FROM product_categories
                WHERE product_id = :product_id'
            );

            $deleteCategoriesStatement->execute([
                'product_id' => $id,
            ]);

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
                        'product_id' => $id,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM products
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }
}