<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductFormService;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository =
    new ProductRepository($pdo);

$categoryRepository =
    new CategoryRepository($pdo);

$productFormService =
    new ProductFormService(
        $productRepository
    );

/*
 * Produkt-ID prüfen.
 */
$idValue =
    (string) (
        $_GET['id'] ?? ''
    );

if (
    $idValue === ''
    || !ctype_digit($idValue)
    || (int) $idValue < 1
) {
    http_response_code(404);

    exit(
        'Produkt nicht gefunden.'
    );
}

$productId =
    (int) $idValue;

/*
 * Produkt laden.
 */
$product =
    $productRepository
        ->findById(
            $productId
        );

if ($product === null) {
    http_response_code(404);

    exit(
        'Produkt nicht gefunden.'
    );
}

$categories =
    $categoryRepository->findAll();

$errors = [];

$form = [
    'article_number' =>
        (string) (
            $product[
                'article_number'
            ]
            ?? ''
        ),

    'name' =>
        (string) $product['name'],

    'unit' =>
        (string) (
            $product['unit']
            ?? ''
        ),

    'price' =>
        (string) $product['price'],

    'remark' =>
        (string) (
            $product['remark']
            ?? ''
        ),

    'category_ids' =>
        $productRepository
            ->findCategoryIds(
                $productId
            ),
];

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {
    $result =
        $productFormService->process(
            $_POST,
            $categories,
            $productId
        );

    $form = $result['form'];
    $errors = $result['errors'];
    $priceForDatabase =
        $result['price'];

    if ($errors === []) {
        $productRepository->update(
            $productId,
            $form['name'],
            $form['unit'] !== ''
                ? $form['unit']
                : null,
            $priceForDatabase,
            $form['remark'] !== ''
                ? $form['remark']
                : null,
            $form['category_ids'],
            $form['article_number'] !== ''
                ? $form['article_number']
                : null
        );

        header(
            'Location: '
            . '/admin/products.php'
            . '?updated=1'
        );

        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/edit.php';