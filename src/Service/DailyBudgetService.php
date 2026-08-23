<?php

declare(strict_types=1);

namespace App\Service;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final class DailyBudgetService
{
    public function calculate(
        array $settings,
        array $group
    ): array {
        $days = [];

        $this->addParticipants(
            $days,
            $settings['arrival_date'] ?? null,
            0,
            (int) ($group['participants_arrival_half'] ?? 0),
            0,
            'Anreise'
        );

        $this->addFullDayRange(
            $days,
            $settings['week1_start_date'] ?? null,
            $settings['week1_end_date'] ?? null,
            (int) ($group['participants_week1_full'] ?? 0),
            '1. Woche'
        );

        $this->addParticipants(
            $days,
            $settings['week1_departure_date'] ?? null,
            (int) ($group['participants_week1_departure_full'] ?? 0),
            (int) ($group['participants_week1_departure_half'] ?? 0),
            0,
            'Abreise 1. Woche'
        );

        $this->addFullDayRange(
            $days,
            $settings['week2_start_date'] ?? null,
            $settings['week2_end_date'] ?? null,
            (int) ($group['participants_week2_full'] ?? 0),
            '2. Woche'
        );

        $this->addParticipants(
            $days,
            $settings['week2_departure_date'] ?? null,
            0,
            (int) ($group['participants_week2_departure_half'] ?? 0),
            0,
            'Abreise 2. Woche'
        );

        /*
         * Der Besuchstag wird bewusst zuletzt addiert.
         * So bleibt ein bereits vorhandener normaler Lagertag erhalten
         * und die zusätzlichen Besucher kommen nur als Zuschlag dazu.
         */
        $this->addParticipants(
            $days,
            $settings['visitor_date'] ?? null,
            0,
            0,
            (int) ($group['participants_visitors'] ?? 0),
            'Besuchstag'
        );

        $budgetFullDayCents = $this->moneyToCents(
            (string) ($settings['budget_full_day'] ?? '0.00')
        );

        $budgetHalfDayCents = $this->moneyToCents(
            (string) ($settings['budget_half_day'] ?? '0.00')
        );

        $budgetVisitorDayCents = $this->moneyToCents(
            (string) ($settings['budget_visitor_day'] ?? '0.00')
        );

        ksort($days);

        $totalBudgetCents = 0;

        foreach ($days as $date => &$day) {
            $day['date'] = $date;

            $day['budget_cents'] =
                ($day['full_participants'] * $budgetFullDayCents)
                + ($day['half_participants'] * $budgetHalfDayCents)
                + ($day['visitor_participants'] * $budgetVisitorDayCents);

            $totalBudgetCents += $day['budget_cents'];
        }

        unset($day);

        return [
            'days' => array_values($days),
            'total_budget_cents' => $totalBudgetCents,
        ];
    }

    private function addParticipants(
        array &$days,
        mixed $date,
        int $fullParticipants,
        int $halfParticipants,
        int $visitorParticipants,
        string $label
    ): void {
        $date = $this->normalizeDate($date);

        if ($date === null) {
            return;
        }

        if (!isset($days[$date])) {
            $days[$date] = [
                'full_participants' => 0,
                'half_participants' => 0,
                'visitor_participants' => 0,
                'labels' => [],
            ];
        }

        $days[$date]['full_participants'] += $fullParticipants;
        $days[$date]['half_participants'] += $halfParticipants;
        $days[$date]['visitor_participants'] += $visitorParticipants;

        if (!in_array($label, $days[$date]['labels'], true)) {
            $days[$date]['labels'][] = $label;
        }
    }

    private function addFullDayRange(
        array &$days,
        mixed $startDate,
        mixed $endDate,
        int $participants,
        string $label
    ): void {
        $startDate = $this->normalizeDate($startDate);
        $endDate = $this->normalizeDate($endDate);

        if ($startDate === null || $endDate === null) {
            return;
        }

        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        if ($end < $start) {
            throw new InvalidArgumentException(
                'Das Enddatum von "' . $label
                . '" liegt vor dem Startdatum.'
            );
        }

        $oneDay = new DateInterval('P1D');

        for (
            $date = $start;
            $date <= $end;
            $date = $date->add($oneDay)
        ) {
            $this->addParticipants(
                $days,
                $date->format('Y-m-d'),
                $participants,
                0,
                0,
                $label
            );
        }
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException(
                'Ungültiges Datum: ' . $value
            );
        }

        return $value;
    }

    private function moneyToCents(string $amount): int
    {
        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Ungültiger Geldbetrag: ' . $amount
            );
        }

        [$wholePart, $decimalPart] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $decimalPart = str_pad(
            $decimalPart,
            2,
            '0'
        );

        return ((int) $wholePart * 100)
            + (int) $decimalPart;
    }
}