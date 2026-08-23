<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\GroupSessionService;
use App\Service\OrderCutoffService;
use App\Service\OrderService;
use App\Service\BudgetBalanceService;

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
$categoryRepository = new CategoryRepository($pdo);
$orderRepository = new OrderRepository($pdo);

$dailyBudgetService =
    new DailyBudgetService();

$budgetBalanceService =
    new BudgetBalanceService(
        $orderRepository,
        $dailyBudgetService
    );

$orderCutoffService =
    new OrderCutoffService();

$orderService = new OrderService(
    $groupRepository,
    $settingsRepository,
    $productRepository,
    $orderRepository,
    $dailyBudgetService,
    $orderCutoffService
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

$calculation = $dailyBudgetService->calculate(
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
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
}

$participantCount =
    (int) $budgetDay['full_participants']
    + (int) $budgetDay['half_participants']
    + (int) $budgetDay['visitor_participants'];

if ($participantCount === 0) {
    http_response_code(404);

    exit(
        'Für diesen Tag ist keine Bestellung vorgesehen.'
    );
}

$budgetBalance =
    $budgetBalanceService->forDeliveryDate(
        $settings,
        $group,
        $deliveryDate
    );

$existingOrder =
    $orderRepository->findByGroupAndDate(
        $groupId,
        $deliveryDate
    );

if (
    $existingOrder !== null
    && $existingOrder['status'] === 'submitted'
) {
    header(
        'Location: /group/review.php?date='
        . rawurlencode($deliveryDate)
    );
    exit;
}

$cutoffStatus =
    $orderCutoffService->getStatus(
        $deliveryDate,
        (string) $settings['order_cutoff_time']
    );

if (!$cutoffStatus['is_open']) {
    if ($existingOrder !== null) {
        header(
            'Location: /group/review.php?date='
            . rawurlencode($deliveryDate)
        );
        exit;
    }

    header('Location: /group/');
    exit;
}

$quantities = [];

if ($existingOrder !== null) {
    foreach (
        $orderRepository->findItems(
            (int) $existingOrder['id']
        ) as $item
    ) {
        if ($item['product_id'] === null) {
            continue;
        }

        $quantities[
            (string) (int) $item['product_id']
        ] = (string) $item['quantity'];
    }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $action = (string) (
        $_POST['action']
        ?? 'save'
    );

    $rawQuantities =
        $_POST['quantities']
        ?? [];

    if (
        !$groupSession->verifyCsrfToken(
            $csrfToken
        )
    ) {
        http_response_code(400);

        $error =
            'Die Sitzung ist abgelaufen. '
            . 'Bitte versuche es erneut.';
    } elseif (
        !in_array(
            $action,
            ['save', 'review'],
            true
        )
    ) {
        $error = 'Ungültige Aktion.';
    } elseif (!is_array($rawQuantities)) {
        $error = 'Ungültige Mengenangaben.';
    } else {
        $quantities = [];
        $hasInvalidQuantityValue = false;

        foreach (
            $rawQuantities
            as $productId => $rawQuantity
        ) {
            if (!is_scalar($rawQuantity)) {
                $hasInvalidQuantityValue = true;
                break;
            }

            $quantities[
                (string) $productId
            ] = (string) $rawQuantity;
        }

        if ($hasInvalidQuantityValue) {
            $error = 'Ungültige Mengenangaben.';
        } else {
            try {
                $orderService->saveDraft(
                    $groupId,
                    $deliveryDate,
                    $quantities
                );

                if ($action === 'review') {
                    header(
                        'Location: /group/review.php?date='
                        . rawurlencode($deliveryDate)
                    );
                    exit;
                }

                header(
                    'Location: /group/order.php?date='
                    . rawurlencode($deliveryDate)
                    . '&saved=1'
                );
                exit;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}

$products = $productRepository->findAll();

$categories =
    $categoryRepository->findAll();

$productCategoryIds =
    $categoryRepository->findProductCategoryIds();

$saved =
    (string) ($_GET['saved'] ?? '') === '1';

$csrfToken = $groupSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/group/order.php';