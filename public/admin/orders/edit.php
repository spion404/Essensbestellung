<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingsRepository;
use App\Service\AdminSessionService;
use App\Service\DailyBudgetService;
use App\Service\OrderCutoffService;
use App\Service\OrderService;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$adminSession =
    new AdminSessionService();

$groupIdValue =
    trim(
        (string) (
            $_GET['group_id']
            ?? $_POST['group_id']
            ?? ''
        )
    );

$deliveryDate =
    trim(
        (string) (
            $_GET['date']
            ?? $_POST['delivery_date']
            ?? ''
        )
    );

if (
    $groupIdValue === ''
    || !ctype_digit($groupIdValue)
    || (int) $groupIdValue < 1
) {
    http_response_code(404);
    exit('Gruppe nicht gefunden.');
}

$parsedDate =
    DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $deliveryDate
    );

if (
    $parsedDate === false
    || $parsedDate->format('Y-m-d')
        !== $deliveryDate
) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
}

$groupId =
    (int) $groupIdValue;

$pdo = Database::connect();

$groupRepository =
    new GroupRepository($pdo);

$settingsRepository =
    new SettingsRepository($pdo);

$productRepository =
    new ProductRepository($pdo);

$orderRepository =
    new OrderRepository($pdo);

$dailyBudgetService =
    new DailyBudgetService();

$orderCutoffService =
    new OrderCutoffService();

$orderService =
    new OrderService(
        $groupRepository,
        $settingsRepository,
        $productRepository,
        $orderRepository,
        $dailyBudgetService,
        $orderCutoffService
    );

$group =
    $groupRepository->findById(
        $groupId
    );

if ($group === null) {
    http_response_code(404);
    exit('Gruppe nicht gefunden.');
}

$settings =
    $settingsRepository->get();

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
        'Für diese Gruppe ist an diesem Tag '
        . 'keine Bestellung vorgesehen.'
    );
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken =
        (string) (
            $_POST['csrf_token']
            ?? ''
        );

    $action =
        (string) (
            $_POST['action']
            ?? 'save'
        );

    $rawQuantities =
        $_POST['quantities']
        ?? [];

    if (
        !$adminSession->verifyCsrfToken(
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
            ['save', 'submit'],
            true
        )
    ) {
        $error =
            'Ungültige Aktion.';
    } elseif (
        !is_array($rawQuantities)
    ) {
        $error =
            'Ungültige Mengenangaben.';
    } else {
        $quantities = [];
        $hasInvalidValue = false;

        foreach (
            $rawQuantities
            as $productId => $rawQuantity
        ) {
            if (!is_scalar($rawQuantity)) {
                $hasInvalidValue = true;
                break;
            }

            $quantities[
                (string) $productId
            ] = (string) $rawQuantity;
        }

        if ($hasInvalidValue) {
            $error =
                'Ungültige Mengenangaben.';
        } else {
            try {
                $orderService->saveAsAdmin(
                    $groupId,
                    $deliveryDate,
                    $quantities,
                    $action === 'submit'
                );

                $query =
                    http_build_query([
                        'group_id' => $groupId,
                        'date' => $deliveryDate,

                        $action === 'submit'
                            ? 'submitted'
                            : 'saved'
                            => 1,
                    ]);

                header(
                    'Location: /admin/orders/edit.php?'
                    . $query
                );
                exit;
            } catch (Throwable $exception) {
                $error =
                    $exception->getMessage();
            }
        }
    }
}

$existingOrder =
    $orderRepository->findByGroupAndDate(
        $groupId,
        $deliveryDate
    );

$existingItems = [];
$existingItemsByProductId = [];
$orphanItems = [];

if ($existingOrder !== null) {
    $existingItems =
        $orderRepository->findItems(
            (int) $existingOrder['id']
        );

    foreach ($existingItems as $item) {
        if ($item['product_id'] === null) {
            $orphanItems[] = $item;
            continue;
        }

        $existingItemsByProductId[
            (int) $item['product_id']
        ] = $item;
    }
}

if (!isset($quantities)) {
    $quantities = [];

    foreach (
        $existingItemsByProductId
        as $productId => $item
    ) {
        $quantities[
            (string) $productId
        ] =
            (string) (int) $item['quantity'];
    }
}

$products =
    $productRepository->findAll();

$cutoffStatus =
    $orderCutoffService->getStatus(
        $deliveryDate,
        (string) $settings['order_cutoff_time']
    );

$saved =
    (string) ($_GET['saved'] ?? '')
    === '1';

$submitted =
    (string) ($_GET['submitted'] ?? '')
    === '1';

$adminCsrfToken =
    $adminSession->csrfToken();

require dirname(__DIR__, 3)
    . '/templates/admin/orders/edit.php';