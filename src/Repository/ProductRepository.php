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
                p.article_number,
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
                p.article_number,
                p.name,
                p.unit,
                p.price,
                p.remark
            ORDER BY p.name ASC'
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                article_number,
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

    public function findByArticleNumber(
        string $articleNumber
    ): ?array {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                article_number,
                name,
                unit,
                price,
                remark
            FROM products
            WHERE article_number = :article_number
            LIMIT 1'
        );

        $statement->execute([
            'article_number' => $articleNumber,
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

    public function create(
        string $name,
        ?string $unit,
        string $price,
        ?string $remark,
        array $categoryIds,
        ?string $articleNumber = null
    ): int {
        $ownsTransaction =
            !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO products (
                    article_number,
                    name,
                    unit,
                    price,
                    remark
                ) VALUES (
                    :article_number,
                    :name,
                    :unit,
                    :price,
                    :remark
                )'
            );

            $statement->execute([
                'article_number' => $articleNumber,
                'name' => $name,
                'unit' => $unit,
                'price' => $price,
                'remark' => $remark,
            ]);

            $productId =
                (int) $this->pdo->lastInsertId();

            $this->replaceCategories(
                $productId,
                $categoryIds
            );

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

    public function update(
        int $id,
        string $name,
        ?string $unit,
        string $price,
        ?string $remark,
        array $categoryIds
    ): void {
        $ownsTransaction =
            !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

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

            $this->replaceCategories(
                $id,
                $categoryIds
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
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

    public function updateByArticleNumber(
        string $articleNumber,
        string $name,
        ?string $unit,
        string $price,
        ?string $remark,
        array $categoryIds
    ): void {
        $product = $this->findByArticleNumber(
            $articleNumber
        );

        if ($product === null) {
            throw new \RuntimeException(
                'Produkt mit Artikelnummer '
                . $articleNumber
                . ' wurde nicht gefunden.'
            );
        }

        $this->update(
            (int) $product['id'],
            $name,
            $unit,
            $price,
            $remark,
            $categoryIds
        );
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

    public function deleteAll(): int
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM products'
        );

        $statement->execute();

        return $statement->rowCount();
    }

    private function replaceCategories(
        int $productId,
        array $categoryIds
    ): void {
        $deleteStatement = $this->pdo->prepare(
            'DELETE FROM product_categories
            WHERE product_id = :product_id'
        );

        $deleteStatement->execute([
            'product_id' => $productId,
        ]);

        if ($categoryIds === []) {
            return;
        }

        $insertStatement = $this->pdo->prepare(
            'INSERT INTO product_categories (
                product_id,
                category_id
            ) VALUES (
                :product_id,
                :category_id
            )'
        );

        foreach ($categoryIds as $categoryId) {
            $insertStatement->execute([
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);
        }
    }
}