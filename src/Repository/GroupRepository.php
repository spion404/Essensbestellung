<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class GroupRepository
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
                name,
                participants_arrival_half,
                participants_week1_full,
                participants_week1_departure_half,
                participants_week1_departure_full,
                participants_visitors,
                participants_week2_full,
                participants_week2_departure_half
            FROM groups
            ORDER BY name ASC'
        );

        return $statement->fetchAll();
    }
}