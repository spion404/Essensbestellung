<?php

declare(strict_types=1);

use App\Database;
use App\Repository\SettingsRepository;

require dirname(__DIR__) . '/config/bootstrap.php';

$campName = 'Essensbestellung';
$databaseError = null;

try {
    $pdo = Database::connect();

    $settingsRepository = new SettingsRepository($pdo);
    $settings = $settingsRepository->get();

    if (
        isset($settings['camp_name'])
        && trim((string) $settings['camp_name']) !== ''
    ) {
        $campName = (string) $settings['camp_name'];
    }
} catch (Throwable) {
    $databaseError =
        'Die Anwendung kann momentan nicht auf die Datenbank zugreifen. '
        . 'Bitte versuche es später erneut oder informiere die Administration.';
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

    <title><?= $escape($campName) ?> – Essensbestellung</title>

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="stylesheet" href="/assets/public.css">
</head>

<body class="home-page">

<main class="home-shell">

    <section class="home-hero">

        <div class="brand">
            <span class="brand__mark">EB</span>

            <span class="brand__text">
                <span class="brand__title">
                    <?= $escape($campName) ?>
                </span>

                <span class="brand__subtitle">
                    Essensbestellung
                </span>
            </span>
        </div>

        <p class="eyebrow">Lagerverpflegung</p>

        <h1>Essensbestellungen einfach verwalten</h1>

        <p class="lead">
            Gruppen erfassen ihre Bestellungen selbst. Die Administration
            behält Budgets, Liefertage und Sammelbestellungen im Überblick.
        </p>

    </section>

    <?php if ($databaseError !== null): ?>

        <div class="alert alert--danger">
            <strong>Datenbankverbindung nicht verfügbar.</strong>
            <p><?= $escape($databaseError) ?></p>
        </div>

    <?php endif; ?>

    <section class="entry-grid" aria-label="Bereich auswählen">

        <a class="entry-card" href="/group/login.php">
            <span class="entry-card__icon">G</span>

            <span class="entry-card__label">Gruppenbereich</span>

            <h2>Bestellung erfassen</h2>

            <p>
                Als Lagergruppe anmelden, Liefertag auswählen, Produkte
                bestellen und die Bestellung definitiv bestätigen.
            </p>

            <span class="entry-card__action">
                Zum Gruppenlogin →
            </span>
        </a>

        <a class="entry-card" href="/admin/login.php">
            <span class="entry-card__icon">A</span>

            <span class="entry-card__label">Administration</span>

            <h2>Lager verwalten</h2>

            <p>
                Gruppen, Produkte und Einstellungen pflegen, Bestellungen
                kontrollieren sowie Tagesauswertungen und Exporte erstellen.
            </p>

            <span class="entry-card__action">
                Zur Administration →
            </span>
        </a>

    </section>

</main>

</body>
</html>
