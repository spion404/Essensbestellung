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

    public function nameExists(string $name): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
            FROM groups
            WHERE name = :name
            LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function nameExistsForOtherGroup(
        string $name,
        int $id
    ): bool {
        $statement = $this->pdo->prepare(
            'SELECT 1
            FROM groups
            WHERE name = :name
            AND id <> :id
            LIMIT 1'
        );

        $statement->execute([
            'name' => $name,
            'id' => $id,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(array $group): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO groups (
                name,
                password_encrypted,
                participants_arrival_half,
                participants_week1_full,
                participants_week1_departure_half,
                participants_week1_departure_full,
                participants_visitors,
                participants_week2_full,
                participants_week2_departure_half
            ) VALUES (
                :name,
                :password_encrypted,
                :participants_arrival_half,
                :participants_week1_full,
                :participants_week1_departure_half,
                :participants_week1_departure_full,
                :participants_visitors,
                :participants_week2_full,
                :participants_week2_departure_half
            )'
        );

        $statement->execute([
            'name' => $group['name'],
            'password_encrypted' => $group['password_encrypted'],
            'participants_arrival_half'
                => $group['participants_arrival_half'],
            'participants_week1_full'
                => $group['participants_week1_full'],
            'participants_week1_departure_half'
                => $group['participants_week1_departure_half'],
            'participants_week1_departure_full'
                => $group['participants_week1_departure_full'],
            'participants_visitors'
                => $group['participants_visitors'],
            'participants_week2_full'
                => $group['participants_week2_full'],
            'participants_week2_departure_half'
                => $group['participants_week2_departure_half'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                name,
                password_encrypted,
                participants_arrival_half,
                participants_week1_full,
                participants_week1_departure_half,
                participants_week1_departure_full,
                participants_visitors,
                participants_week2_full,
                participants_week2_departure_half
            FROM groups
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $group = $statement->fetch();

        if ($group === false) {
            return null;
        }

        return $group;
    }

    public function update(int $id, array $group): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE groups
            SET
                name = :name,
                password_encrypted = :password_encrypted,
                participants_arrival_half = :participants_arrival_half,
                participants_week1_full = :participants_week1_full,
                participants_week1_departure_half =
                    :participants_week1_departure_half,
                participants_week1_departure_full =
                    :participants_week1_departure_full,
                participants_visitors = :participants_visitors,
                participants_week2_full = :participants_week2_full,
                participants_week2_departure_half =
                    :participants_week2_departure_half
            WHERE id = :id'
        );

        $statement->execute([
            'name' => $group['name'],
            'password_encrypted' => $group['password_encrypted'],

            'participants_arrival_half'
                => $group['participants_arrival_half'],

            'participants_week1_full'
                => $group['participants_week1_full'],

            'participants_week1_departure_half'
                => $group['participants_week1_departure_half'],

            'participants_week1_departure_full'
                => $group['participants_week1_departure_full'],

            'participants_visitors'
                => $group['participants_visitors'],

            'participants_week2_full'
                => $group['participants_week2_full'],

            'participants_week2_departure_half'
                => $group['participants_week2_departure_half'],

            'id' => $id,
        ]);
    }
}