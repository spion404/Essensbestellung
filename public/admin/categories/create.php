<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

$pdo = Database::connect();

$categoryRepository = new CategoryRepository($pdo);

$errors = [];

$form = [
    'name' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim(
        (string) ($_POST['name'] ?? '')
    );

    if ($form['name'] === '') {
        $errors['name'] =
            'Bitte einen Kategorienamen eingeben.';
    } elseif (mb_strlen($form['name']) > 100) {
        $errors['name'] =
            'Der Kategoriename ist zu lang.';
    } elseif ($categoryRepository->nameExists($form['name'])) {
        $errors['name'] =
            'Eine Kategorie mit diesem Namen existiert bereits.';
    }

    if ($errors === []) {
        $categoryRepository->create(
            $form['name']
        );

        header(
            'Location: /admin/categories.php?created=1'
        );
        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/categories/create.php';