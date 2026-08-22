<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);

$groups = $groupRepository->findAll();

require dirname(__DIR__, 2)
    . '/templates/admin/groups.php';