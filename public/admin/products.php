<?php

declare(strict_types=1);

use App\Database;
use App\Repository\ProductRepository;

require dirname(__DIR__, 2)
    . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository =
    new ProductRepository($pdo);

$products =
    $productRepository->findAll();

$created =
    isset($_GET['created']);

$updated =
    isset($_GET['updated']);

$deleted =
    isset($_GET['deleted']);

$importCreatedValue = (string) (
    $_GET['import_created'] ?? ''
);

$importUpdatedValue = (string) (
    $_GET['import_updated'] ?? ''
);

$importDeletedValue = (string) (
    $_GET['import_deleted'] ?? ''
);

$importCreated =
    ctype_digit($importCreatedValue)
        ? (int) $importCreatedValue
        : null;

$importUpdated =
    ctype_digit($importUpdatedValue)
        ? (int) $importUpdatedValue
        : null;

$importDeleted =
    ctype_digit($importDeletedValue)
        ? (int) $importDeletedValue
        : null;

$importCompleted =
    $importCreated !== null
    || $importUpdated !== null
    || $importDeleted !== null;

require dirname(__DIR__, 2)
    . '/templates/admin/products.php';