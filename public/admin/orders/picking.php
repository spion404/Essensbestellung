<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\OrderCutoffService;
use App\Service\OrderDayReportService;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$deliveryDate = trim(
    (string) ($_GET['date'] ?? '')
);

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

$reportService =
    new OrderDayReportService(
        $groupRepository,
        $orderRepository,
        $settingsRepository,
        $dailyBudgetService,
        $orderCutoffService
    );

try {
    $report = $reportService->buildExport(
        $deliveryDate
    );
} catch (InvalidArgumentException) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
} catch (RuntimeException $exception) {
    http_response_code(404);
    exit($exception->getMessage());
}

$settings = $report['settings'];
$groupEntries = $report['group_entries'];
$aggregateItems = $report['aggregate_items'];
$submittedOrders = $report['submitted_orders'];
$submittedCount = $report['submitted_count'];
$draftCount = $report['draft_count'];
$missingCount = $report['missing_count'];
$cutoffStatus = $report['cutoff_status'];

$draftGroups = [];
$missingGroups = [];

foreach ($groupEntries as $entry) {
    if ($entry['status'] === 'draft') {
        $draftGroups[] = (string) $entry['group']['name'];
    } elseif ($entry['status'] === 'missing') {
        $missingGroups[] = (string) $entry['group']['name'];
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/orders/picking.php';