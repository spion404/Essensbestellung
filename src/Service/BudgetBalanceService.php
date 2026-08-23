<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class BudgetBalanceService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly DailyBudgetService $dailyBudgetService
    ) {
    }

    public function forDeliveryDate(
        array $settings,
        array $group,
        string $deliveryDate
    ): array {
        $deliveryDate = $this->normalizeDate(
            $deliveryDate
        );

        $calculation =
            $this->dailyBudgetService->calculate(
                $settings,
                $group
            );

        $submittedByDate = [];

        foreach (
            $this->orderRepository
                ->findSubmittedFinancialsByGroup(
                    (int) $group['id']
                )
            as $order
        ) {
            $submittedByDate[
                (string) $order['delivery_date']
            ] = [
                'total_cents' =>
                    $this->moneyToCents(
                        (string) $order['total_amount']
                    ),

                'rounding_cents' =>
                    $this->signedMoneyToCents(
                        (string) $order['rounding_amount']
                    ),
            ];
        }

        $balanceCents = 0;
        $roundingCents = 0;
        $targetDay = null;

        foreach ($calculation['days'] as $day) {
            $date = (string) $day['date'];

            if ($date === $deliveryDate) {
                $targetDay = $day;
                break;
            }

            if ($date > $deliveryDate) {
                break;
            }

            /*
             * Das Budget aller früheren Lagertage
             * wird zuerst vollständig übertragen.
             */
            $balanceCents +=
                (int) $day['budget_cents'];

            /*
             * Nur definitiv bestätigte Bestellungen
             * erzeugen effektive Kosten.
             *
             * Ein Tag ohne bestätigte Bestellung
             * hat somit vorläufig Kosten 0 und sein
             * Budget wird vollständig übertragen.
             */
            $submitted =
                $submittedByDate[$date]
                ?? null;

            if ($submitted === null) {
                continue;
            }

            $balanceCents -=
                (int) $submitted['total_cents']
                + (int) $submitted['rounding_cents'];

            $roundingCents +=
                (int) $submitted['rounding_cents'];
        }

        if ($targetDay === null) {
            throw new InvalidArgumentException(
                'Der Liefertag gehört nicht zum Lagerzeitraum.'
            );
        }

        return [
            'delivery_date' => $deliveryDate,

            'day_budget_cents' =>
                (int) $targetDay['budget_cents'],

            'carryover_cents' =>
                $balanceCents,

            'rounding_cents_before' =>
                $roundingCents,

            'total_budget_cents' =>
                (int) $calculation['total_budget_cents'],
        ];
    }

    private function normalizeDate(
        string $value
    ): string {
        $value = trim($value);

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException(
                'Ungültiges Lieferdatum.'
            );
        }

        return $value;
    }

    private function moneyToCents(
        string $amount
    ): int {
        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Ungültiger Geldbetrag.'
            );
        }

        [$wholePart, $decimalPart] =
            array_pad(
                explode('.', $amount, 2),
                2,
                ''
            );

        $decimalPart =
            str_pad(
                $decimalPart,
                2,
                '0'
            );

        return ((int) $wholePart * 100)
            + (int) $decimalPart;
    }

    private function signedMoneyToCents(
        string $amount
    ): int {
        if (
            preg_match(
                '/^(?<sign>[+-]?)(?<whole>\d+)'
                . '(?:\.(?<decimal>\d{1,2}))?$/',
                $amount,
                $matches
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Ungültiger Rundungsbetrag.'
            );
        }

        $decimal =
            str_pad(
                (string) (
                    $matches['decimal']
                    ?? ''
                ),
                2,
                '0'
            );

        $cents =
            ((int) $matches['whole'] * 100)
            + (int) $decimal;

        return ($matches['sign'] ?? '') === '-'
            ? -$cents
            : $cents;
    }
}