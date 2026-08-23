<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class OrderDayReportService
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly OrderRepository $orderRepository,
        private readonly SettingsRepository $settingsRepository,
        private readonly DailyBudgetService $dailyBudgetService,
        private readonly OrderCutoffService $orderCutoffService
    ) {
    }

    public function build(string $deliveryDate): array
    {
        $deliveryDate = $this->normalizeDate(
            $deliveryDate
        );

        $settings = $this->settingsRepository->get();
        $groups = $this->groupRepository->findAll();

        $ordersForDate =
            $this->orderRepository->findByDeliveryDate(
                $deliveryDate
            );

        $ordersByGroup = [];

        foreach ($ordersForDate as $order) {
            $ordersByGroup[
                (int) $order['group_id']
            ] = $order;
        }

        $groupEntries = [];
        $dayBudgetCents = 0;
        $submittedCount = 0;
        $draftCount = 0;
        $missingCount = 0;
        $submittedAmount = 0.0;

        foreach ($groups as $group) {
            $calculation =
                $this->dailyBudgetService->calculate(
                    $settings,
                    $group
                );

            $budgetDay = null;

            foreach ($calculation['days'] as $day) {
                if (
                    (string) $day['date']
                    === $deliveryDate
                ) {
                    $budgetDay = $day;
                    break;
                }
            }

            if ($budgetDay === null) {
                continue;
            }

            $participantCount =
                (int) $budgetDay['full_participants']
                + (int) $budgetDay['half_participants']
                + (int) $budgetDay['visitor_participants'];

            if ($participantCount === 0) {
                continue;
            }

            $order =
                $ordersByGroup[
                    (int) $group['id']
                ]
                ?? null;

            if ($order === null) {
                $status = 'missing';
                $missingCount++;
            } elseif (
                $order['status'] === 'submitted'
            ) {
                $status = 'submitted';
                $submittedCount++;

                $submittedAmount +=
                    (float) $order['total_amount'];
            } else {
                $status = 'draft';
                $draftCount++;
            }

            $dayBudgetCents +=
                (int) $budgetDay['budget_cents'];

            $groupEntries[] = [
                'group' => $group,
                'budget_day' => $budgetDay,
                'order' => $order,
                'status' => $status,
            ];
        }

        if ($groupEntries === []) {
            throw new RuntimeException(
                'Für diesen Tag sind keine Gruppen '
                . 'mit Teilnehmern konfiguriert.'
            );
        }

        $aggregateItems =
            $this->orderRepository
                ->summarizeSubmittedItemsByDeliveryDate(
                    $deliveryDate
                );

        $cutoffStatus =
            $this->orderCutoffService->getStatus(
                $deliveryDate,
                (string) $settings['order_cutoff_time']
            );

        return [
            'delivery_date' => $deliveryDate,
            'settings' => $settings,
            'group_entries' => $groupEntries,
            'day_budget_cents' => $dayBudgetCents,
            'submitted_count' => $submittedCount,
            'draft_count' => $draftCount,
            'missing_count' => $missingCount,
            'submitted_amount' => $submittedAmount,
            'aggregate_items' => $aggregateItems,
            'cutoff_status' => $cutoffStatus,
        ];
    }

    public function buildExport(string $deliveryDate): array
    {
        $report = $this->build(
            $deliveryDate
        );

        $submittedOrders = [];

        foreach ($report['group_entries'] as $entry) {
            if (
                $entry['status'] !== 'submitted'
                || $entry['order'] === null
            ) {
                continue;
            }

            $submittedOrders[] = [
                'group' => $entry['group'],
                'order' => $entry['order'],
                'items' => $this->orderRepository->findItems(
                    (int) $entry['order']['id']
                ),
            ];
        }

        $report['submitted_orders'] =
            $submittedOrders;

        return $report;
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
                'Ungültiger Liefertag.'
            );
        }

        return $value;
    }
}