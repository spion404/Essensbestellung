<?php

declare(strict_types=1);

use App\Database;
use App\Repository\OrderRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$orderRepository = new OrderRepository($pdo);

$orders = $orderRepository->findAll();

require dirname(__DIR__, 2)
    . '/templates/admin/orders.php';