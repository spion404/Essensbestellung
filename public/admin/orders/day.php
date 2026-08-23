<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\OrderCutoffService;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$deliveryDate =
    trim(
        (string) ($_GET['date'] ?? '')
    );

$date =
    DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $deliveryDate
    );

if (
    $date === false
    || $date->format('Y-m-d') !== $deliveryDate
) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
}

$pdo = Database::connect();

$groupRepository =
    new GroupRepository($pdo);

$orderRepository =
    new OrderRepository($pdo);

$settingsRepository =
    new SettingsRepository($pdo);

$dailyBudgetService =
    new DailyBudgetService();

$orderCutoffService =
    new OrderCutoffService();

$settings =
    $settingsRepository->get();

$groups =
    $groupRepository->findAll();

$ordersForDate =
    $orderRepository->findByDeliveryDate(
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
        $dailyBudgetService->calculate(
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
    http_response_code(404);

    exit(
        'Für diesen Tag sind keine Gruppen '
        . 'mit Teilnehmern konfiguriert.'
    );
}

$aggregateItems =
    $orderRepository
        ->summarizeSubmittedItemsByDeliveryDate(
            $deliveryDate
        );

$cutoffStatus =
    $orderCutoffService->getStatus(
        $deliveryDate,
        (string) $settings['order_cutoff_time']
    );

require dirname(__DIR__, 3)
    . '/templates/admin/orders/day.php';