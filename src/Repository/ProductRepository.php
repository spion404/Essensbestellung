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
        $this->pdo->beginTransaction();

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

            $this->pdo->commit();

            return $productId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}