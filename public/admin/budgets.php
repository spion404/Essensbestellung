<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);
$dailyBudgetService = new DailyBudgetService();

$settings = $settingsRepository->get();
$groups = $groupRepository->findAll();

$groupBudgets = [];
$calculationError = null;

try {
    foreach ($groups as $group) {
        $groupBudgets[] = [
            'group' => $group,
            'calculation' => $dailyBudgetService->calculate(
                $settings,
                $group
            ),
        ];
    }
} catch (InvalidArgumentException $exception) {
    $groupBudgets = [];
    $calculationError = $exception->getMessage();
}

$campTotalBudgetCents = 0;

foreach ($groupBudgets as $groupBudget) {
    $campTotalBudgetCents +=
        $groupBudget['calculation']['total_budget_cents'];
}

$selectedGroupId = null;

if (isset($_GET['group_id'])) {
    $selectedGroupId = filter_var(
        $_GET['group_id'],
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
            ],
        ]
    );

    if ($selectedGroupId === false) {
        $selectedGroupId = null;
    }
}

$selectedGroupBudget = null;

if ($groupBudgets !== []) {
    if ($selectedGroupId === null) {
        $selectedGroupBudget = $groupBudgets[0];
    } else {
        foreach ($groupBudgets as $groupBudget) {
            if ((int) $groupBudget['group']['id'] === $selectedGroupId) {
                $selectedGroupBudget = $groupBudget;
                break;
            }
        }
    }
}

require dirname(__DIR__, 2)
    . '/templates/admin/budgets.php';