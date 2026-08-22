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