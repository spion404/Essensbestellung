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

$categories =
    $categoryRepository->findAll();

$errors = [];

$form = [
    'article_number' => '',
    'name' => '',
    'unit' => '',
    'price' => '',
    'remark' => '',
    'category_ids' => [],
];

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {
    $result =
        $productFormService->process(
            $_POST,
            $categories
        );

    $form = $result['form'];
    $errors = $result['errors'];
    $priceForDatabase =
        $result['price'];

    if ($errors === []) {
        $productRepository->create(
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
            . '?created=1'
        );

        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/create.php';