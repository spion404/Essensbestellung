<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingsRepository;
use App\Service\BudgetBalanceService;
use App\Service\DailyBudgetService;
use App\Service\GroupSessionService;
use App\Service\OrderCutoffService;
use App\Service\OrderService;
use App\Service\PdfService;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$groupSession = new GroupSessionService();
$groupId = $groupSession->groupId();

if ($groupId === null) {
    header('Location: /group/login.php');
    exit;
}

$deliveryDate = trim(
    (string) ($_GET['date'] ?? '')
);

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);
$productRepository = new ProductRepository($pdo);
$orderRepository = new OrderRepository($pdo);
$dailyBudgetService = new DailyBudgetService();
$orderCutoffService = new OrderCutoffService();

$orderService = new OrderService(
    $groupRepository,
    $settingsRepository,
    $productRepository,
    $orderRepository,
    $dailyBudgetService,
    $orderCutoffService
);

$group = $groupRepository->findById($groupId);

if ($group === null) {
    $groupSession->logout();
    header('Location: /group/login.php');
    exit;
}

$settings = $settingsRepository->get();

try {
    $summary = $orderService->getSummary(
        $groupId,
        $deliveryDate
    );
} catch (InvalidArgumentException) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
} catch (RuntimeException) {
    http_response_code(404);
    exit('Bestellung nicht gefunden.');
}

if ($summary['order']['status'] !== 'submitted') {
    http_response_code(409);
    exit(
        'Ein PDF kann erst nach der definitiven Bestätigung '
        . 'der Bestellung erstellt werden.'
    );
}

$budgetBalanceService = new BudgetBalanceService(
    $orderRepository,
    $dailyBudgetService
);

$budgetBalance = $budgetBalanceService->forDeliveryDate(
    $settings,
    $group,
    $deliveryDate
);

$timezone = new DateTimeZone(
    (string) (
        $_ENV['APP_TIMEZONE']
        ?? 'Europe/Zurich'
    )
);

$generatedAt = new DateTimeImmutable(
    'now',
    $timezone
);

ob_start();
require dirname(__DIR__, 2)
    . '/templates/pdf/group-order.php';
$html = (string) ob_get_clean();

$pdfService = new PdfService();
$pdf = $pdfService->render($html);

$groupFilename = preg_replace(
    '/[^A-Za-z0-9_-]+/',
    '_',
    (string) $group['name']
);

$groupFilename = trim(
    (string) $groupFilename,
    '_'
);

if ($groupFilename === '') {
    $groupFilename = 'gruppe';
}

$filename = sprintf(
    'bestellung_%s_%s.pdf',
    $groupFilename,
    $deliveryDate
);

header('Content-Type: application/pdf');
header(
    'Content-Disposition: attachment; filename="'
    . $filename
    . '"'
);
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');

echo $pdf;
exit;
