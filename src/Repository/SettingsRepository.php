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

    public function update(array $settings): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE settings
            SET
                camp_name = :camp_name,
                budget_full_day = :budget_full_day,
                budget_half_day = :budget_half_day,
                budget_visitor_day = :budget_visitor_day,
                order_cutoff_time = :order_cutoff_time,
                arrival_date = :arrival_date,
                week1_start_date = :week1_start_date,
                week1_end_date = :week1_end_date,
                week1_departure_date = :week1_departure_date,
                visitor_date = :visitor_date,
                week2_start_date = :week2_start_date,
                week2_end_date = :week2_end_date,
                week2_departure_date = :week2_departure_date
            WHERE id = :id'
        );

        $statement->execute([
            'camp_name' => $settings['camp_name'],
            'budget_full_day' => $settings['budget_full_day'],
            'budget_half_day' => $settings['budget_half_day'],
            'budget_visitor_day' => $settings['budget_visitor_day'],
            'order_cutoff_time' => $settings['order_cutoff_time'],
            'arrival_date' => $settings['arrival_date'],
            'week1_start_date' => $settings['week1_start_date'],
            'week1_end_date' => $settings['week1_end_date'],
            'week1_departure_date' => $settings['week1_departure_date'],
            'visitor_date' => $settings['visitor_date'],
            'week2_start_date' => $settings['week2_start_date'],
            'week2_end_date' => $settings['week2_end_date'],
            'week2_departure_date' => $settings['week2_departure_date'],
            'id' => 1,
        ]);
    }
}