<?php

declare(strict_types=1);

use App\Database;
use App\Repository\SettingsRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$settingsRepository = new SettingsRepository($pdo);

$settings = $settingsRepository->get();

require dirname(__DIR__, 2)
    . '/templates/admin/settings.php';