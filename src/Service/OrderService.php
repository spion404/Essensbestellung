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
        private readonly DailyBudgetService $dailyBudgetService
    ) {
    }

    public function saveDraft(
        int $groupId,
        string $deliveryDate,
        array $rawQuantities
    ): array {
        $day = $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $items = [];
        $totalCents = 0;

        foreach ($rawQuantities as $productIdValue => $rawQuantity) {
            $productId = $this->normalizeProductId(
                $productIdValue
            );

            $quantity = $this->normalizeQuantity(
                (string) $rawQuantity
            );

            if ($quantity === null) {
                continue;
            }

            $product = $this->productRepository->findById(
                $productId
            );

            if ($product === null) {
                throw new InvalidArgumentException(
                    'Ein ausgewähltes Produkt existiert nicht mehr.'
                );
            }

            $unitPrice = (string) $product['price'];

            $items[] = [
                'product_id' => $productId,
                'article_number' => $product['article_number'],
                'product_name' => (string) $product['name'],
                'unit' => $product['unit'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
            ];

            $totalCents += $this->calculateLineTotalCents(
                $unitPrice,
                $quantity
            );
        }

        $orderId = $this->orderRepository->saveDraft(
            $groupId,
            $deliveryDate,
            $items
        );

        $budgetCents = (int) $day['budget_cents'];

        return [
            'order_id' => $orderId,
            'status' => 'draft',
            'budget_cents' => $budgetCents,
            'total_cents' => $totalCents,
            'remaining_budget_cents' => $budgetCents - $totalCents,
        ];
    }

    public function submit(
        int $groupId,
        string $deliveryDate
    ): array {
        $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $order = $this->orderRepository->findByGroupAndDate(
            $groupId,
            $deliveryDate
        );

        if ($order === null) {
            throw new RuntimeException(
                'Für diesen Liefertag existiert noch keine Bestellung.'
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

    public function getSummary(
        int $groupId,
        string $deliveryDate
    ): array {
        $day = $this->findBudgetDay(
            $groupId,
            $deliveryDate
        );

        $order = $this->orderRepository->findByGroupAndDate(
            $groupId,
            $deliveryDate
        );

        if ($order === null) {
            throw new RuntimeException(
                'Für diesen Liefertag existiert noch keine Bestellung.'
            );
        }

        $items = $this->orderRepository->findItems(
            (int) $order['id']
        );

        $totalCents = 0;

        foreach ($items as $item) {
            $totalCents += $this->calculateLineTotalCents(
                (string) $item['unit_price'],
                (int) $item['quantity']
            );
        }

        $budgetCents = (int) $day['budget_cents'];

        return [
            'order' => $order,
            'items' => $items,
            'budget_day' => $day,
            'budget_cents' => $budgetCents,
            'total_cents' => $totalCents,
            'remaining_budget_cents' => $budgetCents - $totalCents,
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

        $deliveryDate = $this->normalizeDate(
            $deliveryDate
        );

        $group = $this->groupRepository->findById(
            $groupId
        );

        if ($group === null) {
            throw new InvalidArgumentException(
                'Die gewählte Gruppe wurde nicht gefunden.'
            );
        }

        $settings = $this->settingsRepository->get();

        $calculation = $this->dailyBudgetService->calculate(
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

    private function normalizeDate(string $value): string
    {
        $value = trim($value);

        $date = DateTimeImmutable::createFromFormat(
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
                'Die Anzahl Packungen muss eine ganze Zahl sein.'
            );
        }

        $quantity = (int) $value;

        if ($quantity === 0) {
            return null;
        }

        if ($quantity > 4_294_967_295) {
            throw new InvalidArgumentException(
                'Die eingegebene Anzahl Packungen ist zu gross.'
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

    private function moneyToCents(string $amount): int
    {
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