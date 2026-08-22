<?php

declare(strict_types=1);

use App\Database;

require dirname(__DIR__) . '/config/bootstrap.php';

try {
    $pdo = Database::connect();

    $result = $pdo->query('SELECT VERSION() AS version')->fetch();

    $databaseStatus = 'Datenbankverbindung erfolgreich';
    $databaseVersion = $result['version'] ?? 'unbekannt';
} catch (Throwable $exception) {
    $databaseStatus = 'Datenbankverbindung fehlgeschlagen';
    $databaseVersion = null;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Essensbestellung</title>
</head>

<body>

<h1>Essensbestellung</h1>

<p>PHP-Anwendung läuft.</p>

<p>
    <?= htmlspecialchars($databaseStatus, ENT_QUOTES, 'UTF-8') ?>
</p>

<?php if ($databaseVersion !== null): ?>
    <p>
        MariaDB/MySQL:
        <?= htmlspecialchars($databaseVersion, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

</body>
</html>