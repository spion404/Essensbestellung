<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\GroupSessionService;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$groupSession = new GroupSessionService();

$groupId = $groupSession->groupId();

if ($groupId === null) {
    header('Location: /group/login.php');
    exit;
}

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);
$orderRepository = new OrderRepository($pdo);

$group = $groupRepository->findById(
    $groupId
);

if ($group === null) {
    $groupSession->logout();

    header('Location: /group/login.php');
    exit;
}

$settings = $settingsRepository->get();

$dailyBudgetService = new DailyBudgetService();

$calculation = $dailyBudgetService->calculate(
    $settings,
    $group
);

$days = [];

foreach ($calculation['days'] as $day) {
    $participantCount =
        (int) $day['full_participants']
        + (int) $day['half_participants']
        + (int) $day['visitor_participants'];

    if ($participantCount === 0) {
        continue;
    }

    $days[] = [
        'budget_day' => $day,
        'order' => $orderRepository->findByGroupAndDate(
            $groupId,
            (string) $day['date']
        ),
    ];
}

$csrfToken = $groupSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/group/index.php';