<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class OrderService
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly SettingsRepository $settingsRepository,
        private readonly ProductRepository $productRepository,
        private readonly OrderRepository $orderRepository,
        private readonly DailyBudgetService $dailyBudgetService,
        private readonly OrderCutoffService $orderCutoffService
    ) {
    }

    public function saveDraft(
        int $groupId,
        string $deliveryDate,
        array $rawQuantities
    ): array {
        $this->assertOrderingOpen(
            $deliveryDate
        );

        $day = $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $prepared =
            $this->prepareItems(
                $rawQuantities
            );

        $orderId =
            $this->orderRepository->saveDraft(
                $groupId,
                $deliveryDate,
                $prepared['items']
            );

        return $this->buildSaveResult(
            $orderId,
            'draft',
            $day,
            $prepared['total_cents']
        );
    }

    public function saveAsAdmin(
        int $groupId,
        string $deliveryDate,
        array $rawQuantities,
        bool $submit = false,
        string $rawRoundingAmount = '0'
    ): array {
        $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $roundingAmount =
            $this->normalizeRoundingAmount(
                $rawRoundingAmount
            );

        $existingSnapshots =
            $this->findExistingSnapshots(
                $groupId,
                $deliveryDate
            );

        $prepared =
            $this->prepareItems(
                $rawQuantities,
                $existingSnapshots
            );

        if (
            $prepared['items'] === []
            && !$this->hasOrphanedItems(
                $groupId,
                $deliveryDate
            )
        ) {
            throw new RuntimeException(
                'Die Admin-Bestellung muss mindestens '
                . 'ein Produkt enthalten.'
            );
        }

        $orderId =
            $this->orderRepository->saveAsAdmin(
                $groupId,
                $deliveryDate,
                $prepared['items'],
                $roundingAmount
            );

        if ($submit) {
            $this->orderRepository->submit(
                $orderId
            );
        }

        return $this->getSummary(
            $groupId,
            $deliveryDate
        );
    }

    public function submit(
        int $groupId,
        string $deliveryDate
    ): array {
        $this->assertOrderingOpen(
            $deliveryDate
        );

        return $this->submitWithoutCutoff(
            $groupId,
            $deliveryDate
        );
    }

    public function submitAsAdmin(
        int $groupId,
        string $deliveryDate
    ): array {
        return $this->submitWithoutCutoff(
            $groupId,
            $deliveryDate
        );
    }

    public function getSummary(
        int $groupId,
        string $deliveryDate
    ): array {
        $day = $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $order =
            $this->orderRepository->findByGroupAndDate(
                $groupId,
                $deliveryDate
            );

        if ($order === null) {
            throw new RuntimeException(
                'Für diesen Liefertag existiert '
                . 'noch keine Bestellung.'
            );
        }

        $items =
            $this->orderRepository->findItems(
                (int) $order['id']
            );

        $totalCents = 0;

        foreach ($items as $item) {
            $totalCents +=
                $this->calculateLineTotalCents(
                    (string) $item['unit_price'],
                    (int) $item['quantity']
                );
        }

        $budgetCents =
            (int) $day['budget_cents'];

        $roundingCents =
            $this->signedMoneyToCents(
                (string) (
                    $order['rounding_amount']
                    ?? '0.00'
                )
            );

        $effectiveTotalCents =
            $totalCents + $roundingCents;

        return [
            'order' => $order,
            'items' => $items,
            'budget_day' => $day,

            'budget_cents' =>
                $budgetCents,

            'total_cents' =>
                $totalCents,

            'rounding_cents' =>
                $roundingCents,

            'effective_total_cents' =>
                $effectiveTotalCents,

            /*
            * Absichtlich weiterhin nur Tagesbudget
            * gegen den eigentlichen Bestellwert.
            *
            * Übertrag und Rundung verändern die
            * Budgetwarnung der Gruppe nicht.
            */
            'remaining_budget_cents' =>
                $budgetCents - $totalCents,
        ];
    }

    private function submitWithoutCutoff(
        int $groupId,
        string $deliveryDate
    ): array {
        $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $order =
            $this->orderRepository->findByGroupAndDate(
                $groupId,
                $deliveryDate
            );

        if ($order === null) {
            throw new RuntimeException(
                'Für diesen Liefertag existiert '
                . 'noch keine Bestellung.'
            );
        }

        $this->orderRepository->submit(
            (int) $order['id']
        );

        return $this->getSummary(
            $groupId,
            $deliveryDate
        );
    }

    private function assertOrderingOpen(
        string $deliveryDate
    ): void {
        $settings =
            $this->settingsRepository->get();

        $this->orderCutoffService->assertOpen(
            $deliveryDate,
            (string) $settings['order_cutoff_time']
        );
    }

    private function prepareItems(
        array $rawQuantities,
        array $existingSnapshots = []
    ): array {
        $items = [];
        $totalCents = 0;

        foreach (
            $rawQuantities
            as $productIdValue => $rawQuantity
        ) {
            $productId =
                $this->normalizeProductId(
                    $productIdValue
                );

            $quantity =
                $this->normalizeQuantity(
                    (string) $rawQuantity
                );

            if ($quantity === null) {
                continue;
            }

            $product =
                $this->productRepository->findById(
                    $productId
                );

            if ($product === null) {
                throw new InvalidArgumentException(
                    'Ein ausgewähltes Produkt '
                    . 'existiert nicht mehr.'
                );
            }

            $snapshot =
                $existingSnapshots[$productId]
                ?? null;

            if ($snapshot !== null) {
                $articleNumber =
                    $snapshot['article_number'];

                $productName =
                    (string) $snapshot['product_name'];

                $unit =
                    $snapshot['unit'];

                $unitPrice =
                    (string) $snapshot['unit_price'];
            } else {
                $articleNumber =
                    $product['article_number'];

                $productName =
                    (string) $product['name'];

                $unit =
                    $product['unit'];

                $unitPrice =
                    (string) $product['price'];
            }

            $items[] = [
                'product_id' => $productId,
                'article_number' => $articleNumber,
                'product_name' => $productName,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
            ];

            $totalCents +=
                $this->calculateLineTotalCents(
                    $unitPrice,
                    $quantity
                );
        }

        return [
            'items' => $items,
            'total_cents' => $totalCents,
        ];
    }

    private function findExistingSnapshots(
        int $groupId,
        string $deliveryDate
    ): array {
        $order =
            $this->orderRepository->findByGroupAndDate(
                $groupId,
                $deliveryDate
            );

        if ($order === null) {
            return [];
        }

        $snapshots = [];

        foreach (
            $this->orderRepository->findItems(
                (int) $order['id']
            ) as $item
        ) {
            if ($item['product_id'] === null) {
                continue;
            }

            $snapshots[
                (int) $item['product_id']
            ] = $item;
        }

        return $snapshots;
    }

    private function hasOrphanedItems(
        int $groupId,
        string $deliveryDate
    ): bool {
        $order =
            $this->orderRepository->findByGroupAndDate(
                $groupId,
                $deliveryDate
            );

        if ($order === null) {
            return false;
        }

        foreach (
            $this->orderRepository->findItems(
                (int) $order['id']
            ) as $item
        ) {
            if ($item['product_id'] === null) {
                return true;
            }
        }

        return false;
    }

    private function buildSaveResult(
        int $orderId,
        string $status,
        array $day,
        int $totalCents
    ): array {
        $budgetCents =
            (int) $day['budget_cents'];

        return [
            'order_id' => $orderId,
            'status' => $status,
            'budget_cents' => $budgetCents,
            'total_cents' => $totalCents,
            'remaining_budget_cents' =>
                $budgetCents - $totalCents,
        ];
    }

    private function findBudgetDay(
        int $groupId,
        string $deliveryDate
    ): array {
        if ($groupId < 1) {
            throw new InvalidArgumentException(
                'Ungültige Gruppe.'
            );
        }

        $deliveryDate =
            $this->normalizeDate(
                $deliveryDate
            );

        $group =
            $this->groupRepository->findById(
                $groupId
            );

        if ($group === null) {
            throw new InvalidArgumentException(
                'Die gewählte Gruppe wurde nicht gefunden.'
            );
        }

        $settings =
            $this->settingsRepository->get();

        $calculation =
            $this->dailyBudgetService->calculate(
                $settings,
                $group
            );

        foreach ($calculation['days'] as $day) {
            if ($day['date'] === $deliveryDate) {
                return $day;
            }
        }

        throw new InvalidArgumentException(
            'Der gewählte Liefertag gehört nicht zu den '
            . 'konfigurierten Lagertagen dieser Gruppe.'
        );
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

    private function normalizeProductId(
        int|string $value
    ): int {
        $value = (string) $value;

        if (
            $value === ''
            || !ctype_digit($value)
            || (int) $value < 1
        ) {
            throw new InvalidArgumentException(
                'Ungültige Produkt-ID.'
            );
        }

        return (int) $value;
    }

    private function normalizeQuantity(
        string $value
    ): ?int {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (!ctype_digit($value)) {
            throw new InvalidArgumentException(
                'Die Anzahl Packungen muss '
                . 'eine ganze Zahl sein.'
            );
        }

        $quantity = (int) $value;

        if ($quantity === 0) {
            return null;
        }

        if ($quantity > 4_294_967_295) {
            throw new InvalidArgumentException(
                'Die eingegebene Anzahl Packungen '
                . 'ist zu gross.'
            );
        }

        return $quantity;
    }

    private function calculateLineTotalCents(
        string $unitPrice,
        int $quantity
    ): int {
        return $this->moneyToCents(
            $unitPrice
        ) * $quantity;
    }

    private function normalizeRoundingAmount(
        string $value
    ): string {
        $value = str_replace(
            ',',
            '.',
            trim($value)
        );

        if ($value === '') {
            return '0.00';
        }

        if (
            preg_match(
                '/^(?<sign>[+-]?)(?<whole>\d+)'
                . '(?:\.(?<decimal>\d{1,2}))?$/',
                $value,
                $matches
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Die Rundung muss ein gültiger Betrag '
                . 'mit maximal zwei Dezimalstellen sein.'
            );
        }

        $whole =
            ltrim(
                (string) $matches['whole'],
                '0'
            );

        if ($whole === '') {
            $whole = '0';
        }

        if (strlen($whole) > 8) {
            throw new InvalidArgumentException(
                'Der Rundungsbetrag ist zu gross.'
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

        if (
            $whole === '0'
            && $decimal === '00'
        ) {
            return '0.00';
        }

        $sign =
            ($matches['sign'] ?? '') === '-'
                ? '-'
                : '';

        return $sign
            . $whole
            . '.'
            . $decimal;
    }

    private function signedMoneyToCents(
        string $amount
    ): int {
        $normalized =
            $this->normalizeRoundingAmount(
                $amount
            );

        $negative =
            str_starts_with(
                $normalized,
                '-'
            );

        $unsigned =
            ltrim(
                $normalized,
                '-'
            );

        [$wholePart, $decimalPart] =
            explode(
                '.',
                $unsigned,
                2
            );

        $cents =
            ((int) $wholePart * 100)
            + (int) $decimalPart;

        return $negative
            ? -$cents
            : $cents;
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
                'Ungültiger Produktpreis.'
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
}