<?php

declare(strict_types=1);

use App\Database;
use App\Repository\ProductRepository;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository = new ProductRepository($pdo);

/*
 * Produkt-ID prüfen
 */
$idValue = (string) ($_GET['id'] ?? '');

if (
    $idValue === ''
    || !ctype_digit($idValue)
    || (int) $idValue < 1
) {
    http_response_code(404);
    exit('Produkt nicht gefunden.');
}

$productId = (int) $idValue;

/*
 * Produkt laden
 */
$product = $productRepository->findById($productId);

if ($product === null) {
    http_response_code(404);
    exit('Produkt nicht gefunden.');
}

/*
 * Erst ein POST löscht das Produkt.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productRepository->delete($productId);

    header(
        'Location: /admin/products.php?deleted=1'
    );

    exit;
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/delete.php';