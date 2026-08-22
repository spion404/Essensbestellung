<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;

final class SettingsRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function get(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                camp_name,
                budget_full_day,
                budget_half_day,
                budget_visitor_day,
                order_cutoff_time,
                arrival_date,
                week1_start_date,
                week1_end_date,
                week1_departure_date,
                visitor_date,
                week2_start_date,
                week2_end_date,
                week2_departure_date
            FROM settings
            WHERE id = :id'
        );

        $statement->execute([
            'id' => 1,
        ]);

        $settings = $statement->fetch();

        if ($settings === false) {
            throw new RuntimeException(
                'Die Einstellungen konnten nicht gefunden werden.'
            );
        }

        return $settings;
    }
}