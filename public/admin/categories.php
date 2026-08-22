<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$categoryRepository = new CategoryRepository($pdo);

$categories = $categoryRepository->findAll();

$created = isset($_GET['created']);

require dirname(__DIR__, 2)
    . '/templates/admin/categories.php';