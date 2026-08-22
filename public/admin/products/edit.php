<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

$pdo = Database::connect();

$productRepository = new ProductRepository($pdo);
$categoryRepository = new CategoryRepository($pdo);

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

$categories = $categoryRepository->findAll();

$errors = [];

/*
 * Formular mit vorhandenen Produktdaten füllen
 */
$form = [
    'name' => (string) $product['name'],
    'unit' => (string) ($product['unit'] ?? ''),
    'price' => (string) $product['price'],
    'remark' => (string) ($product['remark'] ?? ''),
    'category_ids' =>
        $productRepository->findCategoryIds($productId),
];

/*
 * Formular wurde abgeschickt
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim(
        (string) ($_POST['name'] ?? '')
    );

    $form['unit'] = trim(
        (string) ($_POST['unit'] ?? '')
    );

    $form['price'] = trim(
        (string) ($_POST['price'] ?? '')
    );

    $form['remark'] = trim(
        (string) ($_POST['remark'] ?? '')
    );

    /*
     * Produktname validieren
     */
    if ($form['name'] === '') {
        $errors['name'] =
            'Bitte einen Produktnamen eingeben.';
    } elseif (mb_strlen($form['name']) > 200) {
        $errors['name'] =
            'Der Produktname ist zu lang.';
    }

    /*
     * Einheit validieren
     */
    if (mb_strlen($form['unit']) > 50) {
        $errors['unit'] =
            'Die Einheit ist zu lang.';
    }

    /*
     * Preis validieren
     */
    $priceForDatabase = null;

    if ($form['price'] === '') {
        $errors['price'] =
            'Bitte einen Preis eingeben.';
    } else {
        $normalizedPrice = str_replace(
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
                'Bitte einen gültigen Preis eingeben.';
        } else {
            [$wholePart, $decimalPart] = array_pad(
                explode('.', $normalizedPrice, 2),
                2,
                ''
            );

            $wholePart = ltrim($wholePart, '0');

            if ($wholePart === '') {
                $wholePart = '0';
            }

            if (strlen($wholePart) > 8) {
                $errors['price'] =
                    'Der eingegebene Preis ist zu gross.';
            } else {
                $decimalPart = str_pad(
                    $decimalPart,
                    2,
                    '0'
                );

                $priceForDatabase =
                    $wholePart . '.' . $decimalPart;
            }
        }
    }

    /*
     * Kategorien validieren
     */
    $submittedCategoryIds =
        $_POST['category_ids'] ?? [];

    $form['category_ids'] = [];

    if (!is_array($submittedCategoryIds)) {
        $errors['categories'] =
            'Die Kategorien konnten nicht verarbeitet werden.';
    } else {
        $allowedCategoryIds = [];

        foreach ($categories as $category) {
            $allowedCategoryIds[
                (int) $category['id']
            ] = true;
        }

        foreach ($submittedCategoryIds as $categoryId) {
            $categoryId = filter_var(
                $categoryId,
                FILTER_VALIDATE_INT
            );

            if (
                $categoryId === false
                || $categoryId < 1
                || !isset($allowedCategoryIds[$categoryId])
            ) {
                $errors['categories'] =
                    'Mindestens eine ausgewählte Kategorie ist ungültig.';

                break;
            }

            $form['category_ids'][] = $categoryId;
        }

        $form['category_ids'] = array_values(
            array_unique($form['category_ids'])
        );
    }

    /*
     * Produkt aktualisieren
     */
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
            $form['category_ids']
        );

        header(
            'Location: /admin/products.php?updated=1'
        );

        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/edit.php';