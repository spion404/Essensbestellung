<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use App\Service\AdminSessionService;
use App\Service\DailyBudgetService;

require dirname(__DIR__, 2)
    . '/config/bootstrap.php';

$adminSession =
    new AdminSessionService();

$pdo = Database::connect();

$groupRepository =
    new GroupRepository($pdo);

$orderRepository =
    new OrderRepository($pdo);

$settingsRepository =
    new SettingsRepository($pdo);

$dailyBudgetService =
    new DailyBudgetService();

$settings =
    $settingsRepository->get();

$groups =
    $groupRepository->findAll();

$orders =
    $orderRepository->findAll();

$ordersByDateAndGroup = [];

foreach ($orders as $order) {
    $date =
        (string) $order['delivery_date'];

    $groupId =
        (int) $order['group_id'];

    $ordersByDateAndGroup[
        $date
    ][
        $groupId
    ] = $order;
}

$deliveryDays = [];

foreach ($groups as $group) {
    $calculation =
        $dailyBudgetService->calculate(
            $settings,
            $group
        );

    foreach ($calculation['days'] as $day) {
        $participantCount =
            (int) $day['full_participants']
            + (int) $day['half_participants']
            + (int) $day['visitor_participants'];

        if ($participantCount === 0) {
            continue;
        }

        $date =
            (string) $day['date'];

        $groupId =
            (int) $group['id'];

        if (!isset($deliveryDays[$date])) {
            $deliveryDays[$date] = [
                'date' => $date,
                'group_ids' => [],
                'expected_groups' => 0,
                'submitted_orders' => 0,
                'draft_orders' => 0,
                'missing_orders' => 0,
            ];
        }

        $deliveryDays[
            $date
        ][
            'group_ids'
        ][
            $groupId
        ] = true;
    }
}

foreach (
    $deliveryDays
    as $date => &$deliveryDay
) {
    $groupIds =
        array_keys(
            $deliveryDay['group_ids']
        );

    $deliveryDay['expected_groups'] =
        count($groupIds);

    foreach ($groupIds as $groupId) {
        $order =
            $ordersByDateAndGroup[
                $date
            ][
                $groupId
            ]
            ?? null;

        if ($order === null) {
            $deliveryDay['missing_orders']++;
            continue;
        }

        if ($order['status'] === 'submitted') {
            $deliveryDay['submitted_orders']++;
        } else {
            $deliveryDay['draft_orders']++;
        }
    }

    unset(
        $deliveryDay['group_ids']
    );
}

unset($deliveryDay);

ksort($deliveryDays);

$deliveryDays =
    array_values($deliveryDays);

$adminCsrfToken =
    $adminSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/admin/orders.php';