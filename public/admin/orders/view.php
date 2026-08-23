<?php

declare(strict_types=1);

use App\Database;
use App\Repository\OrderRepository;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

$pdo = Database::connect();

$orderRepository = new OrderRepository($pdo);

$idValue = (string) ($_GET['id'] ?? '');

if (
    $idValue === ''
    || !ctype_digit($idValue)
    || (int) $idValue < 1
) {
    http_response_code(404);
    exit('Bestellung nicht gefunden.');
}

$orderId = (int) $idValue;

$order = $orderRepository->findById($orderId);

if ($order === null) {
    http_response_code(404);
    exit('Bestellung nicht gefunden.');
}

$items = $orderRepository->findItems($orderId);

require dirname(__DIR__, 3)
    . '/templates/admin/orders/view.php';