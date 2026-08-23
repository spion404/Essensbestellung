<?php

declare(strict_types=1);

use App\Database;
use App\Repository\SettingsRepository;

require dirname(__DIR__) . '/config/bootstrap.php';

$campName = 'Essensbestellung';

try {
    $pdo = Database::connect();

    $settingsRepository =
        new SettingsRepository($pdo);

    $settings =
        $settingsRepository->get();

    $campName =
        (string) $settings['camp_name'];
} catch (Throwable) {
    // Die Startseite bleibt auch bei einer noch nicht
    // vollständig eingerichteten Datenbank erreichbar.
}

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= $escape($campName) ?></title>
</head>

<body>

<h1><?= $escape($campName) ?></h1>

<h2>Essensbestellung</h2>

<p>
    <a href="/group/">
        Zur Gruppenbestellung
    </a>
</p>

<p>
    <a href="/admin/groups.php">
        Administration
    </a>
</p>

</body>
</html>