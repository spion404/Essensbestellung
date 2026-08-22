<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository =
    new ProductRepository($pdo);

$categoryRepository =
    new CategoryRepository($pdo);

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
    $form['article_number'] =
        trim(
            (string) (
                $_POST[
                    'article_number'
                ]
                ?? ''
            )
        );

    $form['name'] =
        trim(
            (string) (
                $_POST['name']
                ?? ''
            )
        );

    $form['unit'] =
        trim(
            (string) (
                $_POST['unit']
                ?? ''
            )
        );

    $form['price'] =
        trim(
            (string) (
                $_POST['price']
                ?? ''
            )
        );

    $form['remark'] =
        trim(
            (string) (
                $_POST['remark']
                ?? ''
            )
        );

    /*
     * Artikelnummer validieren.
     *
     * Bei manuellen Produkten darf sie
     * leer bleiben.
     */
    if (
        mb_strlen(
            $form['article_number']
        ) > 100
    ) {
        $errors['article_number'] =
            'Die Artikelnummer ist '
            . 'zu lang.';
    } elseif (
        $form['article_number'] !== ''
        && $productRepository
            ->articleNumberExists(
                $form['article_number']
            )
    ) {
        $errors['article_number'] =
            'Diese Artikelnummer ist '
            . 'bereits vergeben.';
    }

    /*
     * Produktname.
     */
    if ($form['name'] === '') {
        $errors['name'] =
            'Bitte einen Produktnamen '
            . 'eingeben.';
    } elseif (
        mb_strlen(
            $form['name']
        ) > 200
    ) {
        $errors['name'] =
            'Der Produktname ist '
            . 'zu lang.';
    }

    /*
     * Einheit.
     */
    if (
        mb_strlen(
            $form['unit']
        ) > 50
    ) {
        $errors['unit'] =
            'Die Einheit ist zu lang.';
    }

    /*
     * Preis.
     */
    $priceForDatabase = null;

    if ($form['price'] === '') {
        $errors['price'] =
            'Bitte einen Preis eingeben.';
    } else {
        $normalizedPrice =
            str_replace(
                ',',
                '.',
                $form['price']
            );

        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $normalizedPrice
            ) !== 1
        ) {
            $errors['price'] =
                'Bitte einen gültigen '
                . 'Preis eingeben.';
        } else {
            [
                $wholePart,
                $decimalPart,
            ] = array_pad(
                explode(
                    '.',
                    $normalizedPrice,
                    2
                ),
                2,
                ''
            );

            $wholePart =
                ltrim(
                    $wholePart,
                    '0'
                );

            if ($wholePart === '') {
                $wholePart = '0';
            }

            if (
                strlen($wholePart) > 8
            ) {
                $errors['price'] =
                    'Der eingegebene Preis '
                    . 'ist zu gross.';
            } else {
                $decimalPart =
                    str_pad(
                        $decimalPart,
                        2,
                        '0'
                    );

                $priceForDatabase =
                    $wholePart
                    . '.'
                    . $decimalPart;
            }
        }
    }

    /*
     * Kategorien.
     */
    $submittedCategoryIds =
        $_POST['category_ids']
        ?? [];

    $form['category_ids'] = [];

    if (
        !is_array(
            $submittedCategoryIds
        )
    ) {
        $errors['categories'] =
            'Die Kategorien konnten '
            . 'nicht verarbeitet werden.';
    } else {
        $allowedCategoryIds = [];

        foreach (
            $categories
            as $category
        ) {
            $allowedCategoryIds[
                (int) $category['id']
            ] = true;
        }

        foreach (
            $submittedCategoryIds
            as $categoryId
        ) {
            $categoryId =
                filter_var(
                    $categoryId,
                    FILTER_VALIDATE_INT
                );

            if (
                $categoryId === false
                || $categoryId < 1
                || !isset(
                    $allowedCategoryIds[
                        $categoryId
                    ]
                )
            ) {
                $errors['categories'] =
                    'Mindestens eine '
                    . 'ausgewählte Kategorie '
                    . 'ist ungültig.';

                break;
            }

            $form[
                'category_ids'
            ][] = $categoryId;
        }

        $form['category_ids'] =
            array_values(
                array_unique(
                    $form[
                        'category_ids'
                    ]
                )
            );
    }

    /*
     * Produkt speichern.
     */
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
            $form[
                'article_number'
            ] !== ''
                ? $form[
                    'article_number'
                ]
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