<?php

declare(strict_types=1);

use App\Database;
use App\Repository\ProductRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository = new ProductRepository($pdo);

$products = $productRepository->findAll();

$created = isset($_GET['created']);
$updated = isset($_GET['updated']);
$deleted = isset($_GET['deleted']);

$importedValue = (string) (
    $_GET['imported'] ?? ''
);

$imported = ctype_digit($importedValue)
    ? (int) $importedValue
    : 0;

require dirname(__DIR__, 2)
    . '/templates/admin/products.php';