<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\GroupSessionService;
use App\Service\OrderService;

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
$productRepository = new ProductRepository($pdo);
$orderRepository = new OrderRepository($pdo);

$dailyBudgetService = new DailyBudgetService();

$orderService = new OrderService(
    $groupRepository,
    $settingsRepository,
    $productRepository,
    $orderRepository,
    $dailyBudgetService
);

$group = $groupRepository->findById(
    $groupId
);

if ($group === null) {
    $groupSession->logout();

    header('Location: /group/login.php');
    exit;
}

$settings = $settingsRepository->get();

$deliveryDate = trim(
    (string) (
        $_GET['date']
        ?? $_POST['delivery_date']
        ?? ''
    )
);

try {
    $summary = $orderService->getSummary(
        $groupId,
        $deliveryDate
    );
} catch (InvalidArgumentException) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
} catch (RuntimeException) {
    header(
        'Location: /group/order.php?date='
        . rawurlencode($deliveryDate)
    );
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $action = (string) (
        $_POST['action']
        ?? ''
    );

    if (
        !$groupSession->verifyCsrfToken(
            $csrfToken
        )
    ) {
        http_response_code(400);

        $error =
            'Die Sitzung ist abgelaufen. '
            . 'Bitte versuche es erneut.';
    } elseif ($action !== 'submit') {
        $error = 'Ungültige Aktion.';
    } else {
        try {
            $orderService->submit(
                $groupId,
                $deliveryDate
            );

            header(
                'Location: /group/review.php?date='
                . rawurlencode($deliveryDate)
                . '&submitted=1'
            );
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();

            $summary = $orderService->getSummary(
                $groupId,
                $deliveryDate
            );
        }
    }
}

$submitted =
    (string) ($_GET['submitted'] ?? '') === '1';

$csrfToken = $groupSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/group/review.php';